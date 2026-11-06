<?php

use App\Models\Order;
use App\Models\User;
use App\Support\EgyptianPhoneNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    /** @return Collection<int, Order> */
    #[Computed]
    public function recentOrders(): Collection
    {
        return Auth::user()->orders()->with('items')->latest()->limit(4)->get();
    }

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $latestOrder = (! $user->phone || ! $user->address)
            ? $user->orders()->latest()->first(['phone', 'address'])
            : null;
        $this->phone = $user->phone ?: (string) ($latestOrder?->phone ?? '');
        $this->address = $user->address ?: (string) ($latestOrder?->address ?? '');
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $this->phone = EgyptianPhoneNumber::normalize($this->phone);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^01[0125][0-9]{8}$/'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $user->fill($validated);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        $this->dispatch('profile-updated', name: $user->name);
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();
        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('home', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="account-settings-page">
    <x-settings.layout :heading="__('My account')" :subheading="__('Manage your contact details and account preferences')">
        <form wire:submit="updateProfileInformation" class="account-settings-card">
            <div class="account-settings-card-heading">
                <div><h2>{{ __('My details') }}</h2><p>{{ __('Update the details we use to contact you about your orders.') }}</p></div>
            </div>
            <div class="account-form-grid">
                <label class="field"><span>{{ __('Full name') }}</span><input wire:model="name" type="text" autocomplete="name" required>@error('name')<span class="error">{{ $message }}</span>@enderror</label>
                <label class="field"><span>{{ __('Phone number') }}</span><input wire:model="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX">@error('phone')<span class="error">{{ __('Enter a valid Egyptian mobile number.') }}</span>@enderror</label>
                <label class="field"><span>{{ __('Email') }}</span><input wire:model="email" type="email" autocomplete="email" required>@error('email')<span class="error">{{ $message }}</span>@enderror</label>
                <label class="field account-address-field"><span>{{ __('Address') }}</span><textarea wire:model="address" rows="3" autocomplete="street-address" maxlength="500"></textarea>@error('address')<span class="error">{{ $message }}</span>@enderror</label>
            </div>
            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="account-notice"><span>{{ __('Your email address is unverified.') }}</span><button class="text-link" type="button" wire:click="resendVerificationNotification">{{ __('Resend verification email') }}</button>@if (session('status') === 'verification-link-sent')<span role="status">{{ __('A new verification link has been sent to your email address.') }}</span>@endif</div>
            @endif
            <div class="account-settings-actions"><button class="button" type="submit">{{ __('Save changes') }}</button><x-action-message class="account-saved-message" on="profile-updated">{{ __('Saved.') }}</x-action-message></div>
        </form>

        <section class="account-settings-card account-orders-card">
            <div class="account-settings-card-heading"><div><h2>{{ __('My orders') }}</h2><p>{{ __('Review your orders and their delivery status.') }}</p></div><a class="text-link" href="{{ route('account.orders') }}" wire:navigate>{{ __('View all') }}</a></div>
            <div class="account-order-list">
                @forelse ($this->recentOrders as $order)
                    <article class="account-order-preview"><div class="account-order-preview-heading"><strong>{{ $order->order_number }}</strong><span class="order-status">{{ __(ucfirst($order->status)) }}</span></div><p>{{ $order->items->pluck('product_name')->join(', ') }}</p><div class="account-order-preview-heading"><span class="note">{{ $order->created_at->format('d M Y') }}</span><strong>{{ number_format((float) $order->total, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div>@if($order->status === 'returned' && $order->return_reason)<p class="note">{{ __('Return reason') }}: {{ $order->return_reason }}</p>@endif</article>
                @empty
                    <p>{{ __('No orders yet.') }} <a class="text-link" href="{{ route('shop') }}" wire:navigate>{{ __('Start shopping') }}</a></p>
                @endforelse
            </div>
        </section>

        <div class="account-settings-card account-delete-card"><h2>{{ __('Delete my data') }}</h2><p>{{ __('This removes your account and saved details. Your past orders stay with the store.') }}</p><livewire:settings.delete-user-form /></div>
    </x-settings.layout>
</section>
