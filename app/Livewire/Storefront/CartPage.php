<?php

namespace App\Livewire\Storefront;

use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartPage extends Component
{
    #[On('cart-session-restored')]
    public function refreshRecoveredSession(): void {}

    public function updateQuantity(int $productId, int $quantity): void
    {
        $cart = session('cart', []);
        $product = Product::query()->where('is_active', true)->whereNull('archived_at')->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))->find($productId);

        $trackStock = (bool) StoreSetting::current()->track_stock;
        if (! $product || $quantity < 1 || ($trackStock && $product->stock < 1)) {
            unset($cart[$productId]);
        } else {
            $maximum = $trackStock ? $product->stock : 99;
            $cart[$productId] = min($quantity, $maximum);
        }

        session()->put('cart', $cart);
        $this->dispatch('cart-updated', count: (int) collect($cart)->sum(), cart: $cart);
    }

    public function remove(int $productId): void
    {
        $cart = session('cart', []);
        unset($cart[$productId]);
        session()->put('cart', $cart);
        $this->dispatch('cart-updated', count: (int) collect($cart)->sum(), cart: $cart);
    }

    public function render(): View
    {
        $cart = session('cart', []);
        $products = Product::query()->where('is_active', true)->whereNull('archived_at')->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))->whereIn('id', array_map('intval', array_keys($cart)))->get()->keyBy('id');
        $lines = [];
        $subtotalCents = 0;

        foreach ($cart as $id => $quantity) {
            $product = $products->get((int) $id);
            if (! $product) {
                continue;
            }
            $maximum = StoreSetting::current()->track_stock ? max(1, $product->stock) : 99;
            $quantity = max(1, min((int) $quantity, $maximum));
            $subtotalCents += (int) round((float) $product->price * 100) * $quantity;
            $lines[] = ['product' => $product, 'quantity' => $quantity];
        }

        return view('livewire.storefront.cart-page', [
            'lines' => $lines,
            'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
        ])->layout('layouts.storefront', ['title' => __('Cart')]);
    }
}
