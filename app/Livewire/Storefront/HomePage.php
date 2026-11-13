<?php

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class HomePage extends Component
{
    public bool $showAllCategories = false;

    public function boot(): void
    {
        $this->enableBackButtonCache();
    }

    public function addToCart(int $productId): void
    {
        $product = Product::query()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))
            ->findOrFail($productId);
        abort_if($product->hasOptions(), 409);

        $trackStock = (bool) StoreSetting::current()->track_stock;
        abort_if($trackStock && $product->stock < 1, 409);

        $maximum = min(10, $trackStock ? $product->stock : 99);
        $cart = session('cart', []);
        $cart[$product->id] = min((int) ($cart[$product->id] ?? 0) + 1, $maximum);
        session()->put('cart', $cart);

        $cart = session('cart', []);
        $this->dispatch('cart-updated', count: (int) collect($cart)->sum(), cart: $cart);
    }

    public function toggleCategories(): void
    {
        $this->showAllCategories = ! $this->showAllCategories;
    }

    public function render(): View
    {
        $bestSellerProducts = Product::query()->where('is_active', true)->whereNull('archived_at')->where('is_bestseller', true)->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))->latest('created_at')->latest('id')->limit(8)->get();
        $homeProducts = $bestSellerProducts;
        if ($homeProducts->isEmpty()) {
            $homeProducts = Product::query()->where('is_active', true)->whereNull('archived_at')->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))->latest('created_at')->latest('id')->limit(8)->get();
        }

        return view('livewire.storefront.home-page', [
            'categories' => Category::query()->where('is_active', true)->whereNull('archived_at')->with(['products' => fn ($query) => $query->where('is_active', true)->whereNull('archived_at')->whereNotNull('image_path')->limit(1)])->withCount(['products' => fn ($query) => $query->where('is_active', true)->whereNull('archived_at')])->orderBy('sort_order')->when(! $this->showAllCategories, fn ($query) => $query->limit(6))->get(),
            'settings' => StoreSetting::current(),
            'bestSellerProducts' => $bestSellerProducts,
            'homeProducts' => $homeProducts,
        ])->layout('layouts.storefront', ['title' => __('Home')]);
    }
}
