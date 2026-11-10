@php($storeSetting = \App\Models\StoreSetting::current())
@php($storeName = $storeSetting->store_name ?: config('app.name'))
@php($storeLogo = $storeSetting->imageUrl('logo_url') ?: asset('images/athar-logo.png'))
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ $storeLogo }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? __('Store administration')).' — '.$storeName }}</title>
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="admin-body">
    <header class="admin-top"><div class="wrap admin-top-row"><a class="brand" href="{{ route('home', [], false) }}" wire:navigate aria-label="{{ $storeName }}"><img class="brand-logo" src="{{ $storeLogo }}" alt="{{ $storeName }}"></a><span class="admin-label">{{ __('Store administration') }}</span><a class="back-to-store" href="{{ route('home', [], false) }}" wire:navigate>{{ __('Back to store') }}</a></div></header>
    <div class="admin-toast" x-data="{ message: '', visible: false, timeout: null }" x-on:admin-toast.window="message = $event.detail.message; visible = true; clearTimeout(timeout); timeout = setTimeout(() => visible = false, 3500)" x-show="visible" x-transition role="status" aria-live="polite" x-text="message" style="display:none"></div>
    <div class="wrap admin-shell">
        <nav class="admin-nav" aria-label="{{ __('Store administration') }}">
            <a href="{{ route('admin.dashboard') }}" wire:navigate @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>{{ __('Overview') }}</a>
            <a href="{{ route('admin.products') }}" wire:navigate @if(request()->routeIs('admin.products')) aria-current="page" @endif>{{ __('Products') }}</a>
            <a href="{{ route('admin.categories') }}" wire:navigate @if(request()->routeIs('admin.categories')) aria-current="page" @endif>{{ __('Categories') }}</a>
            <a href="{{ route('admin.orders') }}" wire:navigate @if(request()->routeIs('admin.orders*')) aria-current="page" @endif>{{ __('Orders') }}</a>
            <a href="{{ route('admin.messages') }}" wire:navigate @if(request()->routeIs('admin.messages')) aria-current="page" @endif>{{ __('Messages') }}</a>
            <a href="{{ route('admin.whatsapp-templates') }}" wire:navigate @if(request()->routeIs('admin.whatsapp-templates')) aria-current="page" @endif>{{ __('WhatsApp messages') }}</a>
            <a href="{{ route('admin.settings') }}" wire:navigate @if(request()->routeIs('admin.settings')) aria-current="page" @endif>{{ __('Store settings') }}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-button admin-signout-button">{{ __('Sign out') }}</button></form>
        </nav>
        <main class="admin-main">{{ $slot }}</main>
    </div>
    @livewireScripts
</body>
</html>

