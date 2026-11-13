@php($storeSetting = \App\Models\StoreSetting::current())
@php($storeName = $storeSetting->store_name ?: config('app.name'))
@php($storeLogo = asset('images/athar-logo.webp?v=20261113'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ $storeLogo }}">
        <title>{{ $title ?? __('My account') }} — {{ $storeName }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:500,700,800|cairo:400,500,600,700,800|figtree:400,500,600,700" rel="stylesheet">
        @vite('resources/css/app.css')
        @livewireStyles
    </head>
    <body class="account-app-shell">
        <header class="account-app-header">
            <div class="wrap account-app-header-inner">
                <a class="account-app-brand" href="{{ route('home', [], false) }}" wire:navigate aria-label="{{ $storeName }}"><img src="{{ $storeLogo }}" alt="{{ $storeName }}"></a>
                <nav class="account-app-nav" aria-label="{{ __('Account navigation') }}">
                    <a href="{{ route('settings.profile') }}" wire:navigate @if(request()->routeIs('settings.profile', 'settings.password', 'settings.appearance')) aria-current="page" @endif>{{ __('My account') }}</a>
                    <a href="{{ route('account.orders') }}" wire:navigate @if(request()->routeIs('account.orders')) aria-current="page" @endif>{{ __('My orders') }}</a>
                    @if (auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" wire:navigate @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>{{ __('Admin dashboard') }}</a>@endif
                </nav>
                <div class="account-app-actions"><span class="account-app-user">{{ auth()->user()->name }}</span><a class="button secondary small" href="{{ route('home', [], false) }}" wire:navigate>{{ __('Back to store') }}</a><a class="cart-link account-cart-link" href="{{ route('cart') }}" wire:navigate><span class="cart-label">{{ __('Cart') }}</span><span>{{ collect(session('cart', []))->sum() }}</span></a><form method="POST" action="{{ route('logout') }}">@csrf<button class="account-sign-out" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"></path><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"></path></svg><span>{{ __('Log out') }}</span></button></form></div>
            </div>
        </header>
        <main class="wrap account-app-main">{{ $slot }}</main>
        <footer class="site-footer"><div class="wrap footer-grid"><div class="footer-brand"><p class="footer-invitation">{{ $storeSetting->localized('tagline') }}</p><div class="social-links">@if($storeSetting->instagram_url)<a href="{{ $storeSetting->instagram_url }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none"></circle></svg></a>@endif @if($storeSetting->tiktok_url)<a href="{{ $storeSetting->tiktok_url }}" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.37v13.67a2.9 2.9 0 1 1-2.9-2.9c.32 0 .64.05.94.15v-3.45a6.28 6.28 0 1 0 5.33 6.2V10.7a8.16 8.16 0 0 0 4.76 1.53V8.87a4.85 4.85 0 0 1-.99-.1Z"></path></svg></a>@endif @if($storeSetting->whatsapp_url)<a class="social-whatsapp-link" href="{{ $storeSetting->whatsapp_url }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true"><path class="whatsapp-bubble" d="M12 2a9.5 9.5 0 0 0-8.2 14.3L2.5 21.5l5.4-1.4A9.5 9.5 0 1 0 12 2Z"/><path class="whatsapp-phone" d="M16.7 14.4c-.3-.2-1.7-.9-2-.9-.3-.1-.5-.2-.7.2l-.9 1.1c-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.2-1.4-.8-.7-1.4-1.6-1.6-1.9-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.2-.5.1-.2 0-.4 0-.5L8.6 7.4c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.5.1-.7.3-.3.3-.9.9-.9 2.1s.9 2.5 1 2.7c.1.2 1.8 2.8 4.5 3.9.6.3 1.1.4 1.5.5.6.2 1.2.1 1.6.1.5-.1 1.6-.7 1.8-1.3.2-.6.2-1.1.1-1.3-.1-.1-.3-.2-.6-.4Z"/></svg></a>@endif @if($storeSetting->facebook_url)<a href="{{ $storeSetting->facebook_url }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 21v-8h3l.5-3H14V8c0-.9.3-1.5 1.6-1.5h2V3.8c-.4-.1-1.6-.2-2.8-.2-2.8 0-4.7 1.7-4.7 4.8V10H7v3h3v8h4Z"></path></svg></a>@endif</div></div><div><h2>{{ __('Help') }}</h2><a href="{{ route('contact') }}" wire:navigate>{{ __('Contact us') }}</a><a href="{{ route('policy') }}" wire:navigate>{{ __('Terms and policies') }}</a></div><p class="copyright">© {{ date('Y') }} {{ $storeName }}</p></div></footer>
        @if($storeSetting->whatsapp_url)<a class="whatsapp-float" href="{{ $storeSetting->whatsapp_url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Contact us on WhatsApp') }}" title="{{ __('Contact us on WhatsApp') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path class="whatsapp-bubble" d="M12 2a9.5 9.5 0 0 0-8.2 14.3L2.5 21.5l5.4-1.4A9.5 9.5 0 1 0 12 2Z"/><path class="whatsapp-phone" d="M16.7 14.4c-.3-.2-1.7-.9-2-.9-.3-.1-.5-.2-.7.2l-.9 1.1c-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.2-1.4-.8-.7-1.4-1.6-1.6-1.9-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.2-.5.1-.2 0-.4 0-.5L8.6 7.4c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.5.1-.7.3-.3.3-.9.9-.9 2.1s.9 2.5 1 2.7c.1.2 1.8 2.8 4.5 3.9.6.3 1.1.4 1.5.5.6.2 1.2.1 1.6.1.5-.1 1.6-.7 1.8-1.3.2-.6.2-1.1.1-1.3-.1-.1-.3-.2-.6-.4Z"/></svg></a>@endif
        @livewireScripts
        @fluxScripts
    </body>
</html>

