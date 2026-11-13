<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartRecoveryController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart' => $request->boolean('offline_sync')
                ? ['present', 'array', 'max:50']
                : ['sometimes', 'array', 'max:50'],
            'cart.*' => ['integer', 'min:1', 'max:99'],
            'checkout_token' => ['nullable', 'uuid'],
            'offline_sync' => ['sometimes', 'boolean'],
        ]);

        $clientToken = $validated['checkout_token'] ?? null;
        if (! $request->boolean('offline_sync') && $clientToken && ($order = Order::query()->where('checkout_token', $clientToken)->first())) {
            session()->forget(['cart', 'checkout_token']);
            session()->put('last_order_number', $order->order_number);

            return response()->json([
                'order_url' => route('orders.confirmation', ['orderNumber' => $order->order_number]),
                'cart' => [],
                'count' => 0,
                'checkout_token' => $clientToken,
            ]);
        }

        $cart = session('cart', []);
        if ($request->boolean('offline_sync') || ($cart === [] && ($validated['cart'] ?? []) !== [])) {
            $settings = StoreSetting::current();
            $browserCart = $validated['cart'] ?? [];
            $productIds = array_values(array_unique(array_map(fn ($key): int => $this->parseCartKey((string) $key)[0], array_keys($browserCart))));
            $products = Product::query()
                ->where('is_active', true)
                ->whereNull('archived_at')
                ->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))
                ->whereIn('id', $productIds)
                ->get(['id', 'stock', 'colors', 'sizes'])
                ->keyBy('id');

            $restoredCart = [];
            foreach ($browserCart as $id => $quantity) {
                [$productId, $colorId, $sizeId] = $this->parseCartKey((string) $id);
                $product = $products->get($productId);
                if (! $product || ($settings->track_stock && $product->stock < 1)) {
                    continue;
                }
                $hasOptions = ($product->colors ?? []) !== [] || ($product->sizes ?? []) !== [];
                $option = $hasOptions ? $product->optionCombination($colorId, $sizeId) : null;
                if (($hasOptions && ! $option) || (! $hasOptions && ($colorId !== null || $sizeId !== null))) {
                    continue;
                }
                $restoredCart[(string) $id] = $settings->track_stock ? min((int) $quantity, $product->stock) : (int) $quantity;
            }
            $cart = $restoredCart;
            session()->put('cart', $cart);
        }

        $token = session('checkout_token');
        if (! $token || ($clientToken && ! hash_equals((string) $token, $clientToken))) {
            $token = $clientToken ?: (string) Str::uuid();
            session()->put('checkout_token', $token);
        }

        return response()->json([
            'cart' => $cart,
            'count' => (int) collect($cart)->sum(),
            'checkout_token' => $token,
        ]);
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
