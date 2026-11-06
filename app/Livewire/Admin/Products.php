<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\ImageUploadProcessor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Products extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'archive', except: false)]
    public bool $showArchived = false;

    public ?int $productId = null;

    public int $categoryId = 0;

    public string $name = '';

    public string $nameAr = '';

    public string $sku = '';

    public string $description = '';

    public string $descriptionAr = '';

    public string $price = '';

    public string $compareAtPrice = '';

    public int $stock = 0;

    public bool $isActive = true;

    public bool $isFeatured = false;

    /** @var list<string> */
    public array $existingImages = [];

    /** @var list<TemporaryUploadedFile> */
    public array $newImages = [];

    public bool $showForm = false;

    public function createProduct(): void
    {
        Gate::authorize('create', Product::class);
        abort_if($this->showArchived, 404);
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('product-form-opened');
    }

    public function editProduct(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        abort_if($product->archived_at, 404);
        Gate::authorize('update', $product);
        $this->resetForm();
        $this->productId = $product->id;
        $this->categoryId = $product->category_id;
        $this->name = $product->name;
        $this->nameAr = $product->name_ar ?? '';
        $this->sku = $product->sku ?? '';
        $this->description = $product->description ?? '';
        $this->descriptionAr = $product->description_ar ?? '';
        $this->price = (string) $product->price;
        $this->compareAtPrice = (string) ($product->compare_at_price ?? '');
        $this->stock = $product->stock;
        $this->isActive = $product->is_active;
        $this->isFeatured = $product->is_featured;
        $this->existingImages = array_values(array_filter(array_merge([$product->image_path], $product->gallery_paths ?? [])));
        $this->showForm = true;
        $this->dispatch('product-form-opened');
    }

    public function saveProduct(): void
    {
        $product = $this->productId ? Product::query()->findOrFail($this->productId) : new Product;
        abort_if($product->archived_at, 404);
        Gate::authorize($this->productId ? 'update' : 'create', $product);
        $slug = Str::slug($this->name);
        if ($slug === '') {
            $slug = 'product-'.Str::lower(Str::random(8));
        }

        $validated = $this->validate([
            'categoryId' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)->whereNull('archived_at')],
            'name' => ['required', 'string', 'max:160'],
            'nameAr' => ['nullable', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($this->productId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'descriptionAr' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'compareAtPrice' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'isActive' => ['boolean'],
            'isFeatured' => ['boolean'],
            'existingImages' => ['array', 'max:10'],
            'existingImages.*' => ['string', 'max:255'],
            'newImages' => ['array', 'max:10'],
            'newImages.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        if (count($this->existingImages) + count($this->newImages) > 10) {
            throw ValidationException::withMessages(['newImages' => __('Products can have up to ten photos.')]);
        }
        validator(['slug' => $slug], ['slug' => [Rule::unique('products', 'slug')->ignore($this->productId)]])->validate();

        $oldImages = array_values(array_filter(array_merge([$product->image_path], $product->gallery_paths ?? [])));
        $retainedImages = array_values(array_intersect($this->existingImages, $oldImages));
        $uploadedImages = array_map(fn ($image): string => ImageUploadProcessor::store($image, 'products', 1600, 1600, 82), $this->newImages);
        $imagePaths = array_values(array_merge($retainedImages, $uploadedImages));
        $product->fill([
            'category_id' => $validated['categoryId'],
            'name' => trim($validated['name']),
            'name_ar' => $validated['nameAr'] ?: null,
            'slug' => $slug,
            'sku' => $validated['sku'] ?: null,
            'description' => $validated['description'] ?: null,
            'description_ar' => $validated['descriptionAr'] ?: null,
            'price' => $validated['price'],
            'compare_at_price' => $validated['compareAtPrice'] ?: null,
            'stock' => $validated['stock'],
            'is_active' => $validated['isActive'],
            'is_featured' => $validated['isFeatured'],
            'image_path' => $imagePaths[0] ?? null,
            'gallery_paths' => array_slice($imagePaths, 1),
        ])->save();

        Storage::disk('public')->delete(array_values(array_diff($oldImages, $imagePaths)));

        $this->resetForm();
        session()->flash('status', __('Product saved.'));
    }

    public function deleteProduct(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        Gate::authorize('delete', $product);
        abort_unless($product->archived_at, 404);
        $images = array_values(array_filter(array_merge([$product->image_path], $product->gallery_paths ?? [])));
        DB::transaction(fn () => $product->delete());
        Storage::disk('public')->delete($images);
        session()->flash('status', __('Product permanently deleted.'));
    }

    public function archiveProduct(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        Gate::authorize('update', $product);
        abort_if($product->archived_at, 404);
        $product->forceFill(['archived_at' => now(), 'archived_with_category' => false])->save();
        session()->flash('status', __('Product archived.'));
    }

    public function restoreProduct(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        Gate::authorize('update', $product);
        abort_unless($product->archived_at, 404);
        abort_if($product->category?->archived_at, 409);
        $product->forceFill(['archived_at' => null, 'archived_with_category' => false])->save();
        session()->flash('status', __('Product restored.'));
    }

    public function toggleArchiveView(): void
    {
        $this->showArchived = ! $this->showArchived;
        $this->resetPage();
        $this->resetForm();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function removeExistingImage(int $index): void
    {
        unset($this->existingImages[$index]);
        $this->existingImages = array_values($this->existingImages);
    }

    public function removeNewImage(int $index): void
    {
        unset($this->newImages[$index]);
        $this->newImages = array_values($this->newImages);
    }

    public function render(): View
    {
        return view('livewire.admin.products', [
            'trackStock' => (bool) StoreSetting::current()->track_stock,
            'products' => Product::query()->with('category')->when($this->showArchived, fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))->when(trim($this->search) !== '', fn ($query) => $query->where(function ($builder): void {
                $term = '%'.trim($this->search).'%';
                $builder->where('name', 'like', $term)->orWhere('name_ar', 'like', $term)->orWhere('sku', 'like', $term);
            }))->latest()->paginate(12),
            'archivedProductCount' => Product::query()->whereNotNull('archived_at')->count(),
            'categories' => Category::query()->whereNull('archived_at')->where('is_active', true)->orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => __('Products')]);
    }

    private function resetForm(): void
    {
        $this->reset(['productId', 'categoryId', 'name', 'nameAr', 'sku', 'description', 'descriptionAr', 'price', 'compareAtPrice', 'stock', 'isActive', 'isFeatured', 'existingImages', 'newImages']);
        $this->categoryId = (int) Category::query()->where('is_active', true)->whereNull('archived_at')->orderBy('sort_order')->value('id');
        $this->isActive = true;
        $this->isFeatured = false;
        $this->resetValidation();
        $this->showForm = false;
    }
}
