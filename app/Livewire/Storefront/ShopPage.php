<?php

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ShopPage extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'featured')]
    public string $sort = 'featured';

    #[Url(as: 'highlight', except: '')]
    public string $highlight = '';

    #[Url(as: 'min_price', except: '')]
    public string $minPrice = '';

    #[Url(as: 'max_price', except: '')]
    public string $maxPrice = '';

    public ?string $categorySlug = null;

    public function mount(?Category $category = null): void
    {
        $queryValidator = Validator::make(request()->query(), [
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:featured,price_asc,price_desc'],
            'highlight' => ['nullable', 'in:bestseller'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);
        abort_if($queryValidator->fails(), 422);
        $this->highlight = (string) request()->query('highlight', '');
        if ($category) {
            abort_unless($category->is_active && ! $category->archived_at, 404);
            $this->categorySlug = $category->slug;
        }
    }

    public function updatedSearch(): void
    {
        $this->validateOnly('search', ['search' => ['nullable', 'string', 'max:100']]);
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->validateOnly('sort', ['sort' => ['required', 'in:featured,price_asc,price_desc']]);
        $this->resetPage();
    }

    public function updatedHighlight(): void
    {
        $this->validateOnly('highlight', ['highlight' => ['nullable', 'in:bestseller']]);
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->validateOnly('minPrice', ['minPrice' => ['nullable', 'numeric', 'min:0', 'max:99999999']]);
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->validateOnly('maxPrice', ['maxPrice' => ['nullable', 'numeric', 'min:0', 'max:99999999']]);
        $this->resetPage();
    }

    public function clearPriceRange(): void
    {
        $this->minPrice = '';
        $this->maxPrice = '';
        $this->resetPage();
    }

    public function render(): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))
            ->when($this->highlight === 'bestseller', fn ($query) => $query->where('is_bestseller', true))
            ->when($this->categorySlug, fn ($query) => $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $this->categorySlug)))
            ->when(is_numeric($this->minPrice) && (float) $this->minPrice >= 0, fn ($query) => $query->where('price', '>=', (float) $this->minPrice))
            ->when(is_numeric($this->maxPrice) && (float) $this->maxPrice >= 0, fn ($query) => $query->where('price', '<=', (float) $this->maxPrice))
            ->when(trim($this->search) !== '', fn ($query) => $query->where(function ($builder): void {
                $term = '%'.trim($this->search).'%';
                $builder->where('name', 'like', $term)->orWhere('name_ar', 'like', $term)->orWhere('description', 'like', $term)->orWhere('description_ar', 'like', $term);
            }));

        match ($this->sort) {
            'price_asc' => $products->orderBy('price'),
            'price_desc' => $products->orderByDesc('price'),
            default => $products->latest(),
        };

        return view('livewire.storefront.shop-page', [
            'products' => $products->paginate(12),
            'categories' => Category::query()->where('is_active', true)->whereNull('archived_at')->with(['products' => fn ($query) => $query->where('is_active', true)->whereNull('archived_at')->whereNotNull('image_path')->limit(1)])->orderBy('sort_order')->get(),
            'selectedCategory' => $this->categorySlug ? Category::query()->where('slug', $this->categorySlug)->whereNull('archived_at')->first() : null,
            'invalidPriceRange' => is_numeric($this->minPrice) && is_numeric($this->maxPrice) && (float) $this->minPrice > (float) $this->maxPrice,
            'trackStock' => (bool) StoreSetting::current()->track_stock,
        ])->layout('layouts.storefront', ['title' => __('Shop')]);
    }

    public function addToCart(int $productId): void
    {
        $product = Product::query()->where('is_active', true)->whereNull('archived_at')->findOrFail($productId);
        abort_unless($product->category?->is_active && ! $product->category?->archived_at, 404);
        abort_if($product->hasOptions(), 409);
        $trackStock = (bool) StoreSetting::current()->track_stock;
        abort_if($trackStock && $product->stock < 1, 409);

        $cart = session('cart', []);
        $maximum = min(10, $trackStock ? $product->stock : 99);
        $cart[$product->id] = min((int) ($cart[$product->id] ?? 0) + 1, $maximum);
        session()->put('cart', $cart);
        $this->dispatch('cart-updated', count: (int) collect($cart)->sum(), cart: $cart);
    }
}
