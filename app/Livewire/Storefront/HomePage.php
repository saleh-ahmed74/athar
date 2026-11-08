<?php

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class HomePage extends Component
{
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

        $trackStock = (bool) StoreSetting::current()->track_stock;
        abort_if($trackStock && $product->stock < 1, 409);

        $maximum = $trackStock ? $product->stock : 99;
        $cart = session('cart', []);
        $cart[$product->id] = min((int) ($cart[$product->id] ?? 0) + 1, $maximum);
        session()->put('cart', $cart);

        $cart = session('cart', []);
        $this->dispatch('cart-updated', count: (int) collect($cart)->sum(), cart: $cart);
    }

    public function render(): View
    {
        return view('livewire.storefront.home-page', [
            'categories' => Category::query()->where('is_active', true)->whereNull('archived_at')->with(['products' => fn ($query) => $query->where('is_active', true)->whereNull('archived_at')->whereNotNull('image_path')->limit(1)])->withCount(['products' => fn ($query) => $query->where('is_active', true)->whereNull('archived_at')])->orderBy('sort_order')->limit(6)->get(),
            'settings' => StoreSetting::current(),
            'featuredProducts' => Product::query()->where('is_active', true)->whereNull('archived_at')->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))->latest('created_at')->latest('id')->limit(8)->get(),
        ])->layout('layouts.storefront', ['title' => __('Home')]);
    }
}
