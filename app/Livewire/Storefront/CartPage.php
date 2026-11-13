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

    public function updateQuantity(string $cartKey, int $quantity): void
    {
        $cart = session('cart', []);
        [$productId, $colorId, $sizeId] = $this->parseCartKey($cartKey);
        $product = Product::query()->where('is_active', true)->whereNull('archived_at')->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))->find($productId);

        $trackStock = (bool) StoreSetting::current()->track_stock;
        $option = $product?->hasOptions() ? $product->optionCombination($colorId, $sizeId) : null;
        $optionIsValid = $product && ($product->hasOptions() ? $option !== null : $colorId === null && $sizeId === null);
        if (! $product || ! $optionIsValid || $quantity < 1 || ($trackStock && $product->stock < 1)) {
            unset($cart[$cartKey]);
        } else {
            $maximum = min(10, $trackStock ? $product->stock : 99);
            $cart[$cartKey] = min($quantity, $maximum);
        }

        session()->put('cart', $cart);
        $this->dispatch('cart-updated', count: (int) collect($cart)->sum(), cart: $cart);
    }

    public function remove(string $cartKey): void
    {
        $cart = session('cart', []);
        unset($cart[$cartKey]);
        session()->put('cart', $cart);
        $this->dispatch('cart-updated', count: (int) collect($cart)->sum(), cart: $cart);
    }

    public function render(): View
    {
        $cart = session('cart', []);
        $productIds = array_values(array_unique(array_map(fn ($key): int => $this->parseCartKey((string) $key)[0], array_keys($cart))));
        $products = Product::query()->where('is_active', true)->whereNull('archived_at')->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))->whereIn('id', $productIds)->get()->keyBy('id');
        $lines = [];
        $subtotalCents = 0;

        foreach ($cart as $id => $quantity) {
            [$productId, $colorId, $sizeId] = $this->parseCartKey((string) $id);
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }
            $variant = $product->hasOptions() ? $product->optionCombination($colorId, $sizeId) : null;
            if (($product->hasOptions() && ! $variant) || (! $product->hasOptions() && ($colorId !== null || $sizeId !== null))) {
                continue;
            }
            $maximum = min(10, StoreSetting::current()->track_stock ? max(1, $product->stock) : 99);
            $quantity = max(1, min((int) $quantity, $maximum));
            $unitPrice = (float) $product->price;
            $subtotalCents += (int) round($unitPrice * 100) * $quantity;
            $lines[] = ['product' => $product, 'quantity' => $quantity, 'key' => (string) $id, 'variant' => $variant, 'unit_price' => $unitPrice];
        }

        return view('livewire.storefront.cart-page', [
            'lines' => $lines,
            'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
        ])->layout('layouts.storefront', ['title' => __('Cart')]);
    }

    /** @return array{int, ?string, ?string} */
    private function parseCartKey(string $key): array
    {
        if (preg_match('/^(\d+)(?::([0-9a-f-]{36}|-):([0-9a-f-]{36}|-))?$/i', $key, $matches) === 1) {
            return [(int) $matches[1], ($matches[2] ?? '-') !== '-' ? $matches[2] : null, ($matches[3] ?? '-') !== '-' ? $matches[3] : null];
        }

        if (preg_match('/^(\d+)$/', $key, $matches) === 1) {
            return [(int) $matches[1], null, null];
        }

        return [0, null, null];
    }
}
