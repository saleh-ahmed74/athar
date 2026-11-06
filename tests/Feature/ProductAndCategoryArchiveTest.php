<?php

use App\Livewire\Admin\Categories as AdminCategories;
use App\Livewire\Admin\Products as AdminProducts;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function archiveTestAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();

    return $admin;
}

test('archived products disappear from the store and can be restored or permanently deleted', function () {
    $admin = archiveTestAdmin();
    $product = Product::factory()->create(['name' => 'Archive Me']);

    Livewire::actingAs($admin)->test(AdminProducts::class)
        ->call('archiveProduct', $product->id)
        ->assertHasNoErrors();

    expect($product->fresh()->archived_at)->not->toBeNull();
    $this->get(route('product.show', $product->slug))->assertNotFound();
    Livewire::actingAs($admin)->test(AdminProducts::class)->set('showArchived', true)->assertSee('Archive Me')->assertSee('Restore');

    Livewire::actingAs($admin)->test(AdminProducts::class)
        ->call('restoreProduct', $product->id)
        ->assertHasNoErrors();

    expect($product->fresh()->archived_at)->toBeNull();
    $this->get(route('product.show', $product->slug))->assertOk();

    Livewire::actingAs($admin)->test(AdminProducts::class)
        ->call('archiveProduct', $product->id)
        ->call('deleteProduct', $product->id)
        ->assertHasNoErrors();

    expect(Product::query()->find($product->id))->toBeNull();
});

test('archiving a category archives its products and restores only products archived with that category', function () {
    $admin = archiveTestAdmin();
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);
    $alreadyArchived = Product::factory()->create(['category_id' => $category->id, 'archived_at' => now()]);

    $categoryPage = Livewire::actingAs($admin)->test(AdminCategories::class)
        ->call('archiveCategory', $category->id)
        ->assertHasNoErrors()
        ->call('toggleArchiveView')
        ->assertSet('showArchived', true)
        ->assertHasNoErrors()
        ->assertSee('Restore');

    expect($category->fresh()->archived_at)->not->toBeNull()
        ->and($product->fresh()->archived_at)->not->toBeNull()
        ->and($product->fresh()->archived_with_category)->toBeTrue();

    $categoryPage
        ->call('restoreCategory', $category->id)
        ->assertHasNoErrors();

    expect($category->fresh()->archived_at)->toBeNull()
        ->and($product->fresh()->archived_at)->toBeNull()
        ->and($alreadyArchived->fresh()->archived_at)->not->toBeNull();

    $categoryPage
        ->call('toggleArchiveView')
        ->assertSet('showArchived', false)
        ->assertHasNoErrors();
});

test('permanently deleting an archived category removes its products and preserves order snapshots', function () {
    Storage::fake('public');
    $admin = archiveTestAdmin();
    $category = Category::factory()->create(['image_path' => 'categories/category.jpg']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'image_path' => 'products/front.jpg',
        'gallery_paths' => ['products/side.jpg'],
    ]);
    $item = OrderItem::factory()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'sku' => $product->sku,
    ]);

    Livewire::actingAs($admin)->test(AdminCategories::class)
        ->call('archiveCategory', $category->id)
        ->call('deleteCategory', $category->id)
        ->assertHasNoErrors();

    expect(Category::query()->find($category->id))->toBeNull()
        ->and(Product::query()->find($product->id))->toBeNull()
        ->and($item->fresh()->product_id)->toBeNull()
        ->and($item->fresh()->product_name)->toBe($product->name)
        ->and($item->fresh()->sku)->toBe($product->sku);
});
