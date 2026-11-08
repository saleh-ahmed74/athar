@php($storeSetting = \App\Models\StoreSetting::current())
@php($storeName = $storeSetting->store_name ?: config('app.name'))
@php($storeLogo = $storeSetting->imageUrl('logo_url') ?: asset('images/athar-logo.png'))
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="offline-cacheable" content="{{ auth()->guest() ? 'public' : 'private' }}">
    <title>{{ ($title ?? __('Home')).' — '.$storeName }}</title>
    <meta name="description" content="{{ $storeSetting->localized('seo_description') ?: $storeName }}">
    <link rel="icon" href="{{ $storeLogo }}">
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:title" content="{{ ($title ?? __('Home')).' — '.$storeName }}">
    <meta property="og:description" content="{{ $storeSetting->localized('seo_description') ?: $storeName }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ url($storeLogo) }}">
    <meta property="og:image:alt" content="{{ $storeName }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ ($title ?? __('Home')).' — '.$storeName }}">
    <meta name="twitter:description" content="{{ $storeSetting->localized('seo_description') ?: $storeName }}">
    <meta name="twitter:image" content="{{ url($storeLogo) }}">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    @if (request()->routeIs('home') && ($heroImagePreload = $storeSetting->imageUrl('hero_image_url')))<link rel="preload" as="image" href="{{ $heroImagePreload }}" fetchpriority="high">@endif
    <link rel="preload" as="style" href="https://fonts.bunny.net/css?family=bricolage-grotesque:500,700,800|cairo:400,500,600,700,800|figtree:400,500,600,700&display=swap" onload="this.onload=null;this.rel='stylesheet'"><noscript><link href="https://fonts.bunny.net/css?family=bricolage-grotesque:500,700,800|cairo:400,500,600,700,800|figtree:400,500,600,700&display=swap" rel="stylesheet"></noscript>
    @vite(['resources/css/storefront.css', 'resources/js/storefront.js'])
    @livewireStyles
</head>
<body>
    @if ($storeSetting->show_announcement && $storeSetting->localized('announcement'))<div class="top-note">{{ $storeSetting->localized('announcement') }}</div>@endif
    <header class="site-header">
        <div class="wrap nav-row" x-data="{ cartCount: @js((int) collect(session('cart', []))->sum()), added: false }" x-on:cart-updated.window="cartCount = $event.detail.count; added = true; setTimeout(() => added = false, 650)">
            <a class="brand" href="{{ route('home') }}" wire:navigate aria-label="{{ $storeName }}"><img class="brand-logo" src="{{ $storeLogo }}" alt="{{ $storeName }}"></a>
            <nav class="main-nav" aria-label="{{ __('Main navigation') }}">
                <a href="{{ route('home') }}" wire:navigate @if(request()->routeIs('home')) aria-current="page" @endif>{{ __('Home') }}</a>
                <a href="{{ route('shop') }}" wire:navigate @if(request()->routeIs('shop', 'category.show', 'product.show')) aria-current="page" @endif>{{ __('Shop') }}</a>
                <a href="{{ route('about') }}" wire:navigate @if(request()->routeIs('about')) aria-current="page" @endif>{{ __('Our story') }}</a>
                <a href="{{ route('contact') }}" wire:navigate @if(request()->routeIs('contact')) aria-current="page" @endif>{{ __('Contact') }}</a>
            </nav>
            <div class="nav-actions">
                <a class="nav-link" href="{{ route('language.switch', app()->isLocale('ar') ? 'en' : 'ar') }}" wire:navigate>{{ app()->isLocale('ar') ? 'EN' : 'عربي' }}</a>
                @auth
                    <a class="profile-link" href="{{ route('account') }}" wire:navigate @if(request()->routeIs('account', 'account.orders', 'settings.profile', 'settings.password', 'settings.appearance')) aria-current="page" @endif aria-label="{{ __('My account') }}" title="{{ __('My account') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"></circle><path d="M4.5 20c.5-4 3.2-6 7.5-6s7 2 7.5 6"></path></svg><span>{{ __('My account') }}</span></a>
                @else
                    <a class="nav-link auth-link" href="{{ route('login') }}" wire:navigate @if(request()->routeIs('login')) aria-current="page" @endif>{{ __('Sign in') }}</a>
                    <a class="nav-link auth-link" href="{{ route('register') }}" wire:navigate @if(request()->routeIs('register')) aria-current="page" @endif>{{ __('Create account') }}</a>
                @endauth
                <a class="cart-link" href="{{ route('cart') }}" wire:navigate @if(request()->routeIs('cart', 'checkout', 'orders.confirmation')) aria-current="page" @endif x-bind:class="{ 'cart-bump': added }"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.1 11.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 1.9-1.4L22 9H7"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle></svg><span class="cart-label">{{ __('Cart') }}</span><span class="cart-count" x-text="cartCount">{{ collect(session('cart', []))->sum() }}</span></a>
            </div>
            <div class="mobile-nav-actions">
                <a class="mobile-language-link" href="{{ route('language.switch', app()->isLocale('ar') ? 'en' : 'ar') }}" wire:navigate aria-label="{{ app()->isLocale('ar') ? 'English' : 'العربية' }}">{{ app()->isLocale('ar') ? 'EN' : 'عربي' }}</a>
                @auth
                    <a class="mobile-profile-link" href="{{ route('account') }}" wire:navigate><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"></circle><path d="M4.5 20c.5-4 3.2-6 7.5-6s7 2 7.5 6"></path></svg>{{ __('My account') }}</a>
                @else
                    <a class="mobile-login-link" href="{{ route('login') }}" wire:navigate @if(request()->routeIs('login')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"></path><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"></path></svg>{{ __('Sign in') }}</a>
                    <a class="mobile-signup-link" href="{{ route('register') }}" wire:navigate @if(request()->routeIs('register')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.5"></circle><path d="M2.5 20c.5-4 3-6 6.5-6M19 8v8M15 12h8"></path></svg>{{ __('Create account') }}</a>
                @endauth
                <a class="mobile-cart-link" href="{{ route('cart') }}" wire:navigate @if(request()->routeIs('cart', 'checkout', 'orders.confirmation')) aria-current="page" @endif aria-label="{{ __('Cart') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.1 11.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 1.9-1.4L22 9H7"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle></svg><span x-text="cartCount">{{ collect(session('cart', []))->sum() }}</span></a>
            </div>
        </div>
    </header>
    <p id="offline-status" class="offline-status" role="status" aria-live="polite" hidden></p>
    <div class="navigate-progress" wire:loading></div>
    <main class="wrap page-wrap">{{ $slot }}</main>
    <footer class="site-footer">
        <div class="wrap footer-grid">
            <div class="footer-brand"><a class="brand" href="{{ route('home') }}" wire:navigate><img class="brand-logo" src="{{ $storeLogo }}" alt="{{ $storeName }}"></a><p class="footer-invitation">{{ $storeSetting->localized('tagline') }}</p>@if($storeSetting->address)<p>{{ $storeSetting->address }}</p>@endif
                <div class="social-links">@if($storeSetting->instagram_url)<a href="{{ $storeSetting->instagram_url }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none"></circle></svg></a>@endif @if($storeSetting->tiktok_url)<a href="{{ $storeSetting->tiktok_url }}" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.37v13.67a2.9 2.9 0 1 1-2.9-2.9c.32 0 .64.05.94.15v-3.45a6.28 6.28 0 1 0 5.33 6.2V10.7a8.16 8.16 0 0 0 4.76 1.53V8.87a4.85 4.85 0 0 1-.99-.1Z"></path></svg></a>@endif @if($storeSetting->facebook_url)<a href="{{ $storeSetting->facebook_url }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 21v-8h3l.5-3H14V8c0-.9.3-1.5 1.6-1.5h2V3.8c-.4-.1-1.6-.2-2.8-.2-2.8 0-4.7 1.7-4.7 4.8V10H7v3h3v8h4Z"></path></svg></a>@endif @if($storeSetting->whatsapp_url)<a class="social-whatsapp-link" href="{{ $storeSetting->whatsapp_url }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true"><path class="whatsapp-bubble" d="M12 2a9.5 9.5 0 0 0-8.2 14.3L2.5 21.5l5.4-1.4A9.5 9.5 0 1 0 12 2Z"/><path class="whatsapp-phone" d="M16.7 14.4c-.3-.2-1.7-.9-2-.9-.3-.1-.5-.2-.7.2l-.9 1.1c-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.2-1.4-.8-.7-1.4-1.6-1.6-1.9-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.2-.5.1-.2 0-.4 0-.5L8.6 7.4c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.5.1-.7.3-.3.3-.9.9-.9 2.1s.9 2.5 1 2.7c.1.2 1.8 2.8 4.5 3.9.6.3 1.1.4 1.5.5.6.2 1.2.1 1.6.1.5-.1 1.6-.7 1.8-1.3.2-.6.2-1.1.1-1.3-.1-.1-.3-.2-.6-.4Z"/></svg></a>@endif</div>
            </div>
            <div><h2>{{ __('Help') }}</h2><a href="{{ route('contact') }}" wire:navigate>{{ __('Contact us') }}</a><a href="{{ route('policy') }}" wire:navigate>{{ __('Terms and policies') }}</a>@if($storeSetting->support_email)<a href="mailto:{{ $storeSetting->support_email }}">{{ $storeSetting->support_email }}</a>@endif</div>
            <p class="copyright">© {{ date('Y') }} {{ $storeName }}</p>
        </div>
    </footer>
    @if($storeSetting->whatsapp_url)
        <a class="whatsapp-float" href="{{ $storeSetting->whatsapp_url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Contact us on WhatsApp') }}" title="{{ __('Contact us on WhatsApp') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path class="whatsapp-bubble" d="M12 2a9.5 9.5 0 0 0-8.2 14.3L2.5 21.5l5.4-1.4A9.5 9.5 0 1 0 12 2Z"/><path class="whatsapp-phone" d="M16.7 14.4c-.3-.2-1.7-.9-2-.9-.3-.1-.5-.2-.7.2l-.9 1.1c-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.2-1.4-.8-.7-1.4-1.6-1.6-1.9-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.2-.5.1-.2 0-.4 0-.5L8.6 7.4c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.5.1-.7.3-.3.3-.9.9-.9 2.1s.9 2.5 1 2.7c.1.2 1.8 2.8 4.5 3.9.6.3 1.1.4 1.5.5.6.2 1.2.1 1.6.1.5-.1 1.6-.7 1.8-1.3.2-.6.2-1.1.1-1.3-.1-.1-.3-.2-.6-.4Z"/></svg></a>
    @endif
    <div class="cart-flight" aria-hidden="true" x-data="{ flying: false }" x-show="flying" x-on:cart-updated.window="window.flyCartFromOrigin($el, () => { flying = true; setTimeout(() => flying = false, 900) })"><svg viewBox="0 0 24 24"><path d="M3 4h2l2.1 11.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 1.9-1.4L22 9H7"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle></svg></div>
    <script id="storefront-runtime" type="application/json"><?php echo json_encode([
        'scope' => auth()->check() ? 'user-'.auth()->id() : 'guest',
        'initialServerCart' => session('cart', []),
        'cartPath' => route('cart'),
        'checkoutPath' => route('checkout'),
        'recoveryPath' => route('cart.recover'),
        'serviceWorkerPath' => asset('service-worker.js'),
        'messages' => [
            'offline' => app()->isLocale('ar') ? 'أنت غير متصل بالإنترنت. الصفحات التي زرتها والسلة المحفوظة متاحتان.' : 'You are offline. Previously opened pages and your saved cart are available.',
            'pending' => app()->isLocale('ar') ? 'تعديلات السلة محفوظة؛ وسيتحدّث المخزون والإجمالي عند عودة الاتصال.' : 'Cart changes are saved; stock and totals will update when you reconnect.',
            'synced' => app()->isLocale('ar') ? 'تمت مزامنة السلة.' : 'Your cart has been synced.',
            'checkout' => app()->isLocale('ar') ? 'بيانات الدفع محفوظة. إتمام الطلب يحتاج اتصالاً بالإنترنت.' : 'Checkout details are saved. Placing an order requires an internet connection.',
            'storageError' => app()->isLocale('ar') ? 'تعذر حفظ بياناتك على هذا الجهاز. اترك الصفحة مفتوحة.' : 'This device could not save your data. Keep the page open.',
        ],
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?></script>    @livewireScripts
</body>
</html>



