<div class="account-settings-layout">
    <header class="account-settings-heading"><p class="hero-eyebrow">{{ __('ATHAR ACCOUNT') }}</p><h1>{{ $heading ?? __('My account') }}</h1><p>{{ $subheading ?? __('Manage your account details and orders.') }}</p></header>
    <div class="account-settings-content">
        <nav class="account-settings-tabs" aria-label="{{ __('Account settings') }}">
            <a @class(['is-current' => request()->routeIs('settings.profile')]) href="{{ route('settings.profile') }}" wire:navigate>{{ __('My details') }}</a>
            <a @class(['is-current' => request()->routeIs('settings.password')]) href="{{ route('settings.password') }}" wire:navigate>{{ __('Change password') }}</a>
            <a @class(['is-current' => request()->routeIs('account.orders')]) href="{{ route('account.orders') }}" wire:navigate>{{ __('My orders') }}</a>
        </nav>
        <div class="account-settings-panel">{{ $slot }}</div>
    </div>
</div>
