<?php

namespace App\Livewire\Storefront;

use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProductPage extends Component
{
    public Product $product;

    public int $quantity = 1;

    public int $activeImageIndex = 0;

    public function mount(Product $product): void
    {
        $this->product = Product::query()->where('is_active', true)->whereNull('archived_at')->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))->findOrFail($product->id);
    }

    public function addToCart(): void
    {
        $this->product = Product::query()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))
            ->findOrFail($this->product->id);
        $trackStock = (bool) StoreSetting::current()->track_stock;
        abort_if($trackStock && $this->product->stock < 1, 409);
        $maximum = $trackStock ? $this->product->stock : 99;
        $quantity = max(1, min((int) $this->quantity, $maximum));
        $cart = session('cart', []);
        $cart[$this->product->id] = min((int) ($cart[$this->product->id] ?? 0) + $quantity, $maximum);
        session()->put('cart', $cart);
        $this->dispatch('cart-updated', count: (int) collect($cart)->sum(), cart: $cart);
        $this->quantity = 1;
    }

    public function selectImage(int $index): void
    {
        $imageCount = count($this->productImages());
        abort_unless($index >= 0 && $index < $imageCount, 404);
        $this->activeImageIndex = $index;
    }

    public function previousImage(): void
    {
        $imageCount = count($this->productImages());
        if ($imageCount > 1) {
            $this->activeImageIndex = ($this->activeImageIndex - 1 + $imageCount) % $imageCount;
        }
    }

    public function nextImage(): void
    {
        $imageCount = count($this->productImages());
        if ($imageCount > 1) {
            $this->activeImageIndex = ($this->activeImageIndex + 1) % $imageCount;
        }
    }

    public function render(): View
    {
        return view('livewire.storefront.product-page', [
            'trackStock' => (bool) StoreSetting::current()->track_stock,
            'productImages' => $this->productImages(),
            'activeImageIndex' => $this->activeImageIndex,
            'relatedProducts' => Product::query()->where('category_id', $this->product->category_id)->where('is_active', true)->whereNull('archived_at')->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))->where('id', '<>', $this->product->id)->latest()->limit(4)->get(),
        ])->layout('layouts.storefront', ['title' => $this->product->name]);
    }

    /** @return list<string> */
    private function productImages(): array
    {
        return array_values(array_filter(array_merge([$this->product->image_path], $this->product->gallery_paths ?? [])));
    }
}
