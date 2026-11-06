<section>
    <h1 class="page-title">{{ __('Checkout') }}</h1>
    @if (count($lines))<div class="checkout-layout" style="margin-top:18px">
        <form class="form-card checkout-form" wire:submit="placeOrder">
            @error('checkout')<div class="alert">{{ $message }}</div>@enderror
            @guest
                <p class="note">{{ __('Have an account?') }} <a href="{{ route('login') }}">{{ __('Log in') }}</a></p>
            @endguest
            <div class="form-grid">
                <div class="field"><label for="name">{{ __('Name') }}</label><input id="name" autocomplete="name" wire:model="name">@error('name')<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label for="phone">{{ __('Phone') }}</label><div class="phone-input-row" dir="ltr">
                    <select id="phone-country" aria-label="{{ __('Country and calling code') }}" wire:model.live="phoneCountry">
                        @foreach ($phoneCountries as $countryCode => $country)
                            <option value="{{ $countryCode }}">{{ app()->isLocale('ar') ? $country['name_ar'] : $country['name_en'] }} ({{ $country['dial_code'] }})</option>
                        @endforeach
                    </select>
                    <input id="phone" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="10 1234 5678" wire:model="phone">
                </div>@error('phone')<span class="error">{{ $message }}</span>@enderror @error('phoneCountry')<span class="error">{{ $message }}</span>@enderror</div>
            </div>
            <div class="field"><label for="email">{{ __('Email') }} ({{ __('optional') }})</label><input id="email" type="email" autocomplete="email" wire:model="email">@error('email')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="city">{{ __('City / shipping area') }}</label>
                <select id="city" autocomplete="address-level2" wire:model.live="city">
                    @foreach ($shippingZones as $zone)
                        <option value="{{ $zone['id'] }}">{{ app()->isLocale('ar') ? $zone['name_ar'] : $zone['name_en'] }}@if ((string) $zone['id'] === (string) $defaultCityId) — {{ __('Default') }}@endif — {{ number_format((float) $zone['fee'], 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</option>
                    @endforeach
                    @unless ($hasConfiguredShippingZones)
                        <option value="__other__">{{ __('Other city') }}</option>
                    @endunless
                </select>
                @if ($city === '__other__' && ! $hasConfiguredShippingZones)
                    <input id="custom-city" aria-label="{{ __('Other city') }}" autocomplete="address-level2" wire:model="customCity" placeholder="{{ __('Enter your city') }}">
                    @error('customCity')<span class="error">{{ $message }}</span>@enderror
                @endif
                <span class="note">{{ $hasConfiguredShippingZones ? __('Cairo is selected by default. The shipping fee is shown beside each area.') : __('Cairo is the default city. The flat shipping fee applies until shipping areas are configured.') }}</span>
                @error('city')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field"><label for="address">{{ __('Address') }}</label><textarea id="address" rows="3" autocomplete="street-address" wire:model="address"></textarea>@error('address')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="notes">{{ __('Notes') }} ({{ __('optional') }})</label><textarea id="notes" rows="2" wire:model="notes"></textarea>@error('notes')<span class="error">{{ $message }}</span>@enderror</div>
            <p class="note">{{ $orderConfirmation }}</p><p class="note">{{ $paymentLabel }}</p>
            <details class="checkout-policy">
                <summary>{{ __('Terms and policies') }}</summary>
                @if ($policySections !== [])
                    @foreach ($policySections as $section)
                        <article><h3>{{ app()->isLocale('ar') ? ($section['title_ar'] ?? $section['title_en']) : ($section['title_en'] ?? $section['title_ar']) }}</h3><p>{{ app()->isLocale('ar') ? ($section['body_ar'] ?? $section['body_en']) : ($section['body_en'] ?? $section['body_ar']) }}</p></article>
                    @endforeach
                @elseif ($policyText)
                    <p>{{ $policyText }}</p>
                @else
                    <p><a href="{{ route('policy') }}" wire:navigate>{{ __('View terms and policies') }}</a></p>
                @endif
            </details>
            <button class="button" type="submit" wire:loading.attr="disabled" wire:target="placeOrder">{{ __('Place order') }}</button>
        </form>
        <aside class="cart-total cart-box"><h2>{{ __('Order summary') }}</h2>
            @foreach ($lines as $line)<div><span>{{ app()->isLocale('ar') ? ($line['product']->name_ar ?: $line['product']->name) : $line['product']->name }} × {{ $line['quantity'] }}</span><strong>{{ number_format((float) $line['product']->price * $line['quantity'], 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div>@endforeach
            <hr><div><span>{{ __('Subtotal') }}</span><strong>{{ $subtotal }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div><div class="shipping-fee-row"><span>{{ __('Shipping fee for selected area') }}</span><strong>{{ $shippingFee }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div><div class="checkout-total"><strong>{{ __('Total') }}</strong><strong>{{ $total }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div>
        </aside>
    </div>@else<div class="empty-state"><h2>{{ __('Your cart is empty.') }}</h2><a class="button" href="{{ route('shop') }}" wire:navigate>{{ __('Continue shopping') }}</a></div>@endif
</section>
