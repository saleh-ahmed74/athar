<?php

use App\Livewire\Admin\Products as AdminProducts;
use App\Livewire\Storefront\CheckoutPage;
use App\Livewire\Storefront\ProductPage;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('products with options require a valid combination and add a separate cart line', function () {
    Cache::flush();
    StoreSetting::factory()->create(['track_stock' => false]);
    $colorId = (string) Str::uuid();
    $secondColorId = (string) Str::uuid();
    $sizeId = (string) Str::uuid();
    $secondSizeId = (string) Str::uuid();
    $product = Product::factory()->create(['colors' => [
        ['id' => $colorId, 'color_en' => 'Olive', 'color_ar' => 'زيتي', 'color_hex' => '#718061'],
        ['id' => $secondColorId, 'color_en' => 'White', 'color_ar' => 'أبيض', 'color_hex' => '#ffffff'],
    ], 'sizes' => [
        ['id' => $sizeId, 'size_en' => 'Large', 'size_ar' => 'كبير'],
        ['id' => $secondSizeId, 'size_en' => 'Small', 'size_ar' => 'صغير'],
    ]]);

    Livewire::test(ProductPage::class, ['product' => $product])
        ->assertSeeHtml('name="product-color"')
        ->assertSeeHtml('variant-choice-list')
        ->assertSee('Large')
        ->assertSee('Small')
        ->set('variantQuantities.'.$colorId.'__'.$sizeId, 2)
        ->set('variantQuantities.'.$secondColorId.'__'.$secondSizeId, 1)
        ->call('addToCart')
        ->assertHasNoErrors();

    expect(session('cart'))->toBe([$product->id.':'.$colorId.':'.$sizeId => 2, $product->id.':'.$secondColorId.':'.$secondSizeId => 1]);
});

test('checkout snapshots server priced variant options onto the order item', function () {
    Cache::flush();
    StoreSetting::factory()->create(['track_stock' => false, 'shipping_fee' => 0, 'shipping_zones' => []]);
    $colorId = (string) Str::uuid();
    $sizeId = (string) Str::uuid();
    $product = Product::factory()->create([
        'price' => '100.00',
        'colors' => [['id' => $colorId, 'color_en' => 'Olive', 'color_ar' => 'زيتي', 'color_hex' => '#718061']],
        'sizes' => [['id' => $sizeId, 'size_en' => 'Large', 'size_ar' => 'كبير']],
    ]);
    $this->withSession(['cart' => [$product->id.':'.$colorId.':'.$sizeId => 2], 'checkout_token' => (string) Str::uuid()]);

    Livewire::test(CheckoutPage::class)
        ->set('name', 'Variant Customer')
        ->set('phone', '01012345678')
        ->set('city', 'Cairo')
        ->set('address', '12 Test Street, Cairo')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $item = Order::query()->with('items')->sole()->items->sole();
    expect((float) $item->unit_price)->toBe(100.0)
        ->and($item->variant_snapshot['color_id'])->toBe($colorId)
        ->and($item->variant_snapshot['size_id'])->toBe($sizeId)
        ->and($item->variant_snapshot['color_ar'])->toBe('زيتي')
        ->and($item->variant_snapshot['size_en'])->toBe('Large');
});

test('checkout rejects a variant that was removed after it entered the cart', function () {
    Cache::flush();
    StoreSetting::factory()->create(['track_stock' => false]);
    $colorId = (string) Str::uuid();
    $sizeId = (string) Str::uuid();
    $product = Product::factory()->create(['colors' => [], 'sizes' => []]);
    $this->withSession(['cart' => [$product->id.':'.$colorId.':'.$sizeId => 1], 'checkout_token' => (string) Str::uuid()]);

    Livewire::test(CheckoutPage::class)
        ->set('name', 'Variant Customer')
        ->set('phone', '01012345678')
        ->set('city', 'Cairo')
        ->set('address', '12 Test Street, Cairo')
        ->call('placeOrder')
        ->assertHasErrors('checkout');

    expect(Order::query()->count())->toBe(0);
});

test('products without options still use their legacy cart key', function () {
    Cache::flush();
    StoreSetting::factory()->create(['track_stock' => false]);
    $product = Product::factory()->create(['colors' => null, 'sizes' => null]);

    Livewire::test(ProductPage::class, ['product' => $product])
        ->call('addToCart')
        ->assertHasNoErrors();

    expect(session('cart'))->toBe([$product->id => 1]);
});

test('admin saves colors and sizes separately so every color can use every size', function () {
    Cache::flush();
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::factory()->create();
    $this->actingAs($admin);

    Livewire::test(AdminProducts::class)
        ->set('categoryId', $category->id)
        ->set('name', 'Configured variant item')
        ->set('nameAr', 'منتج بمواصفات')
        ->set('price', '100')
        ->set('colorOptions', [['color_en' => 'Olive', 'color_ar' => 'زيتي', 'color_hex' => '#718061', 'image_path' => ''], ['color_en' => 'White', 'color_ar' => 'أبيض', 'color_hex' => '#ffffff', 'image_path' => '']])
        ->set('sizeOptions', [['size_en' => 'Large', 'size_ar' => 'كبير'], ['size_en' => 'Small', 'size_ar' => 'صغير']])
        ->call('saveProduct')
        ->assertHasNoErrors();

    $savedProduct = Product::query()->where('name', 'Configured variant item')->sole();
    expect($savedProduct->colors)->toHaveCount(2)
        ->and($savedProduct->sizes)->toHaveCount(2)
        ->and($savedProduct->optionCombination($savedProduct->colors[0]['id'], $savedProduct->sizes[1]['id']))->toMatchArray(['color_en' => 'Olive', 'size_en' => 'Small']);
});

test('admin can save color only or size only options and gets feedback beside the form', function () {
    Cache::flush();
    StoreSetting::factory()->create(['track_stock' => false]);
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::factory()->create();
    $this->actingAs($admin);

    $component = Livewire::test(AdminProducts::class)
        ->call('createProduct')
        ->set('categoryId', $category->id)
        ->set('name', 'Color only product')
        ->set('price', '100')
        ->set('colorOptions', [[
            'id' => (string) Str::uuid(),
            'color_en' => 'Olive',
            'color_ar' => 'زيتي',
            'color_hex' => '#718061',
            'image_path' => '',
        ]])
        ->call('saveProduct')
        ->assertHasNoErrors()
        ->assertSet('showForm', true)
        ->assertDispatched('admin-toast');

    $product = Product::query()->where('name', 'Color only product')->sole();
    expect($product->colors)->toHaveCount(1)
        ->and($product->sizes)->toBeNull();

    $component->call('editProduct', $product->id)
        ->assertSet('showForm', true)
        ->assertSee('Olive');

    $component->set('colorOptions', [])
        ->set('sizeOptions', [['size_en' => 'Large', 'size_ar' => 'كبير']])
        ->call('saveProduct')
        ->assertHasNoErrors()
        ->assertDispatched('admin-toast');

    expect($product->fresh()->colors)->toBeNull()
        ->and($product->fresh()->sizes[0]['size_en'])->toBe('Large');

    Livewire::test(ProductPage::class, ['product' => $product->fresh()])
        ->assertSee('Large')
        ->set('variantQuantities.none__'.$product->fresh()->sizes[0]['id'], 1)
        ->call('addToCart')
        ->assertHasNoErrors();

    expect(session('cart'))->toBe([$product->id.':-:'.$product->fresh()->sizes[0]['id'] => 1]);
});

test('offline cart notice does not mention stock when stock tracking is disabled', function () {
    Cache::flush();
    StoreSetting::factory()->create(['track_stock' => false]);
    $product = Product::factory()->create();

    $this->get(route('product.show', $product->slug))
        ->assertOk()
        ->assertSee('Cart changes are saved temporarily; totals will update when you reconnect.')
        ->assertDontSee('stock and totals will update when you reconnect.');
});

test('offline cart recovery preserves valid variant cart lines and ignores removed options', function () {
    Cache::flush();
    StoreSetting::factory()->create(['track_stock' => false]);
    $colorId = (string) Str::uuid();
    $sizeId = (string) Str::uuid();
    $product = Product::factory()->create(['colors' => [['id' => $colorId, 'color_en' => 'Olive']], 'sizes' => [['id' => $sizeId, 'size_en' => 'Large']]]);

    $this->postJson(route('cart.recover'), [
        'offline_sync' => true,
        'cart' => [
            $product->id.':'.$colorId.':'.$sizeId => 2,
            $product->id.':'.Str::uuid().':'.Str::uuid() => 3,
        ],
    ])->assertOk()->assertJsonPath('cart.'.$product->id.':'.$colorId.':'.$sizeId, 2);

    expect(session('cart'))->toBe([$product->id.':'.$colorId.':'.$sizeId => 2]);
});
