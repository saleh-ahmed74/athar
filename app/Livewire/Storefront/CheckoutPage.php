<?php

namespace App\Livewire\Storefront;

use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class CheckoutPage extends Component
{
    #[Locked]
    public string $checkoutToken;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $phoneCountry = 'EG';

    public string $city = '';

    public string $customCity = '';

    public string $address = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->checkoutToken = session()->get('checkout_token', (string) Str::uuid());
        session()->put('checkout_token', $this->checkoutToken);

        if (auth()->check()) {
            $this->name = auth()->user()->name;
            $this->email = auth()->user()->email;
        }

        $settings = StoreSetting::current();
        $zones = $this->checkoutShippingZones($settings);
        if ($this->city === '') {
            $this->city = $this->defaultShippingZone($zones) ?? 'Cairo';
        }
    }

    #[On('cart-session-restored')]
    public function refreshRecoveredSession(?string $checkoutToken = null): void
    {
        if ($checkoutToken && Str::isUuid($checkoutToken)) {
            $this->checkoutToken = $checkoutToken;
        }
    }

    public function placeOrder(): mixed
    {
        if ($existingOrder = Order::query()->where('checkout_token', $this->checkoutToken)->first()) {
            session()->forget(['cart', 'checkout_token']);
            session()->put('last_order_number', $existingOrder->order_number);

            return $this->redirectRoute('orders.confirmation', ['orderNumber' => $existingOrder->order_number], navigate: true);
        }

        $key = 'checkout:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['checkout' => __('Please wait before placing another order.')]);
        }
        RateLimiter::hit($key, 60);

        $settings = StoreSetting::current();
        $hasConfiguredShippingZones = ($settings->shipping_zones ?? []) !== [];
        $zones = $this->checkoutShippingZones($settings);
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\\+?[0-9٠-٩۰-۹\\s().-]+$/u'],
            'phoneCountry' => ['required', Rule::in(array_keys(config('phone-countries', [])))],
            'city' => ['required', 'string', Rule::in([...array_column($zones, 'id'), ...($hasConfiguredShippingZones ? [] : ['__other__'])])],
            'customCity' => $this->city === '__other__' && ! $hasConfiguredShippingZones
                ? ['required', 'string', 'max:100']
                : ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'min:8', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $parsedPhone = $phoneUtil->parse($validated['phone'], $validated['phoneCountry']);
        } catch (NumberParseException) {
            throw ValidationException::withMessages(['phone' => __('Enter a valid phone number for the selected country.')]);
        }

        if (! $phoneUtil->isValidNumberForRegion($parsedPhone, $validated['phoneCountry'])) {
            throw ValidationException::withMessages(['phone' => __('Enter a valid phone number for the selected country.')]);
        }

        $validated['phone'] = $phoneUtil->format($parsedPhone, PhoneNumberFormat::E164);
        unset($validated['phoneCountry']);

        $cart = session('cart', []);
        if ($cart === []) {
            throw ValidationException::withMessages(['checkout' => __('Your cart is empty.')]);
        }

        $trackStock = (bool) $settings->track_stock;
        $selectedZone = collect($zones)->firstWhere('id', $validated['city']);
        $shippingFee = $selectedZone['fee'] ?? $settings->shipping_fee;
        $deliveryCity = $validated['city'] === '__other__'
            ? $validated['customCity']
            : ($selectedZone ? (app()->isLocale('ar') ? $selectedZone['name_ar'] : $selectedZone['name_en']) : $validated['city']);
        $shippingFeeCents = (int) round((float) $shippingFee * 100);
        try {
            $order = DB::transaction(function () use ($validated, $deliveryCity, $cart, $shippingFeeCents, $trackStock): Order {
                $productIds = array_values(array_unique(array_map(fn ($key): int => $this->parseCartKey((string) $key)[0], array_keys($cart))));
                $products = Product::query()->with('category')->whereNull('archived_at')->whereHas('category', fn ($query) => $query->whereNull('archived_at'))->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $subtotalCents = 0;
                $orderLines = [];

                foreach ($cart as $cartKey => $quantity) {
                    [$productId, $colorId, $sizeId] = $this->parseCartKey((string) $cartKey);
                    $product = $products->get($productId);
                    $quantity = (int) $quantity;
                    if (! $product || ! $product->is_active || ! $product->category?->is_active || $product->archived_at || $product->category?->archived_at || $quantity < 1 || $quantity > 10 || ($trackStock && $quantity > $product->stock)) {
                        throw ValidationException::withMessages(['checkout' => __('One or more items are no longer available. Please update your cart.')]);
                    }
                    $variant = $product->hasOptions() ? $product->optionCombination($colorId, $sizeId) : null;
                    if (($product->hasOptions() && ! $variant) || (! $product->hasOptions() && ($colorId !== null || $sizeId !== null))) {
                        throw ValidationException::withMessages(['checkout' => __('One or more item options are no longer available. Please update your cart.')]);
                    }
                    $unitCents = (int) round((float) $product->price * 100);
                    $lineCents = $unitCents * $quantity;
                    $subtotalCents += $lineCents;
                    $snapshot = $variant ? [
                        'color_id' => $variant['color_id'],
                        'size_id' => $variant['size_id'],
                        'color_en' => $variant['color_en'] ?? null,
                        'color_ar' => $variant['color_ar'] ?? null,
                        'color_hex' => $variant['color_hex'] ?? null,
                        'size_en' => $variant['size_en'] ?? null,
                        'size_ar' => $variant['size_ar'] ?? null,
                        'unit_price' => number_format($unitCents / 100, 2, '.', ''),
                    ] : null;
                    $orderLines[] = [$product, $quantity, $unitCents, $lineCents, $snapshot];
                }

                $order = Order::query()->create([
                    'order_number' => 'ATH-'.Str::upper(Str::random(10)),
                    'checkout_token' => $this->checkoutToken,
                    'user_id' => auth()->id(),
                    'customer_name' => $validated['name'],
                    'email' => $validated['email'] ?: null,
                    'phone' => $validated['phone'],
                    'city' => $deliveryCity,
                    'address' => $validated['address'],
                    'notes' => $validated['notes'] ?: null,
                    'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
                    'shipping_fee' => number_format($shippingFeeCents / 100, 2, '.', ''),
                    'total' => number_format(($subtotalCents + $shippingFeeCents) / 100, 2, '.', ''),
                    'status' => 'pending',
                    'payment_method' => 'cod',
                    'payment_status' => 'unpaid',
                    'inventory_tracked' => $trackStock,
                ]);

                foreach ($orderLines as [$product, $quantity, $unitCents, $lineCents, $snapshot]) {
                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'unit_price' => number_format($unitCents / 100, 2, '.', ''),
                        'quantity' => $quantity,
                        'line_total' => number_format($lineCents / 100, 2, '.', ''),
                        'variant_snapshot' => $snapshot,
                    ]);
                    if ($trackStock) {
                        $product->decrement('stock', $quantity);
                    }
                }

                return $order;
            }, 3);
        } catch (QueryException $exception) {
            $existingOrder = Order::query()->where('checkout_token', $this->checkoutToken)->first();
            if (! $existingOrder) {
                throw $exception;
            }

            session()->forget(['cart', 'checkout_token']);
            session()->put('last_order_number', $existingOrder->order_number);

            return $this->redirectRoute('orders.confirmation', ['orderNumber' => $existingOrder->order_number], navigate: true);
        }

        session()->forget(['cart', 'checkout_token']);
        session()->put('last_order_number', $order->order_number);

        return $this->redirectRoute('orders.confirmation', ['orderNumber' => $order->order_number], navigate: true);
    }

    public function render(): View
    {
        $cart = session('cart', []);
        $settings = StoreSetting::current();
        $zones = $this->checkoutShippingZones($settings);
        $selectedZone = collect($zones)->firstWhere('id', $this->city);
        $shippingFee = $selectedZone['fee'] ?? $settings->shipping_fee;
        $productIds = array_values(array_unique(array_map(fn ($key): int => $this->parseCartKey((string) $key)[0], array_keys($cart))));
        $products = Product::query()->with('category')->whereNull('archived_at')->whereHas('category', fn ($query) => $query->whereNull('archived_at'))->whereIn('id', $productIds)->get()->keyBy('id');
        $lines = [];
        $subtotalCents = 0;
        foreach ($cart as $id => $quantity) {
            [$productId, $colorId, $sizeId] = $this->parseCartKey((string) $id);
            $product = $products->get($productId);
            if ($product) {
                $variant = $product->hasOptions() ? $product->optionCombination($colorId, $sizeId) : null;
                if (($product->hasOptions() && ! $variant) || (! $product->hasOptions() && ($colorId !== null || $sizeId !== null))) {
                    continue;
                }
                $maximum = min(10, $settings->track_stock ? max(1, $product->stock) : 99);
                $quantity = max(1, min((int) $quantity, $maximum));
                $unitPrice = (float) $product->price;
                $subtotalCents += (int) round($unitPrice * 100) * $quantity;
                $lines[] = ['product' => $product, 'quantity' => $quantity, 'variant' => $variant, 'unit_price' => $unitPrice];
            }
        }

        return view('livewire.storefront.checkout-page', [
            'lines' => $lines,
            'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
            'shippingZones' => $zones,
            'hasConfiguredShippingZones' => ($settings->shipping_zones ?? []) !== [],
            'defaultCityId' => $this->defaultShippingZone($zones),
            'phoneCountries' => config('phone-countries', []),
            'policySections' => $settings->policy_sections ?? [],
            'policyText' => $settings->localized('policy'),
            'shippingFee' => number_format((float) $shippingFee, 2, '.', ''),
            'paymentLabel' => $settings->localized('payment_label'),
            'orderConfirmation' => $settings->localized('order_confirmation'),
            'total' => number_format(($subtotalCents / 100) + (float) $shippingFee, 2, '.', ''),
        ])->layout('layouts.storefront', ['title' => __('Checkout')]);
    }

    /** @param array<int, array<string, mixed>> $zones */
    private function defaultShippingZone(array $zones): ?string
    {
        foreach ($zones as $zone) {
            $englishName = mb_strtolower(trim((string) ($zone['name_en'] ?? '')));
            $id = mb_strtolower(trim((string) ($zone['id'] ?? '')));
            $arabicName = trim((string) ($zone['name_ar'] ?? ''));

            if (in_array($englishName, ['cairo', 'cairo governorate'], true)
                || in_array($id, ['cairo', 'cairo-governorate'], true)
                || $arabicName === 'القاهرة') {
                return (string) ($zone['id'] ?? '');
            }
        }

        return isset($zones[0]['id']) ? (string) $zones[0]['id'] : null;
    }

    /** @return array<int, array<string, mixed>> */
    private function checkoutShippingZones(StoreSetting $settings): array
    {
        $zones = array_values($settings->shipping_zones ?? []);

        if ($this->defaultShippingZone($zones) === null) {
            $zones[] = [
                'id' => $zones === [] ? 'Cairo' : 'cairo',
                'name_en' => 'Cairo',
                'name_ar' => 'القاهرة',
                'fee' => $settings->shipping_fee ?? 0,
            ];
        }

        return $zones;
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
