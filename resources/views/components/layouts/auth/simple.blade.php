@php($storeSetting = \App\Models\StoreSetting::current())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
    <head>
        @include('partials.head')
        <link rel="icon" href="{{ $storeSetting->imageUrl('logo_url') ?: asset('images/athar-logo.png') }}">
    </head>
    <body class="auth-page min-h-screen antialiased">
        <div class="auth-shell flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="auth-card flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home', [], false) }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <img class="brand-logo" src="{{ $storeSetting->imageUrl('logo_url') ?: asset('images/athar-logo.png') }}" alt="{{ $storeSetting->store_name ?: config('app.name') }}">
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="auth-card-content flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>

