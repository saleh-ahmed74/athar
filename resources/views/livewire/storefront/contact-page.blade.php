<section class="content-card">
    <p class="hero-eyebrow">{{ \App\Models\StoreSetting::current()->store_name ?: config('app.name') }}</p><h1 class="page-title">{{ __('Contact us') }}</h1>
    <p class="note">{{ __('Questions about an order or a product? Send us a message.') }}</p>
    @if (session('status'))<p class="status-message">{{ session('status') }}</p>@endif
    @error('form')<p class="alert">{{ $message }}</p>@enderror
    <form class="checkout-form" wire:submit="send" style="max-width:650px;margin-top:20px">
        <div class="field"><label for="contact-name">{{ __('Name') }}</label><input id="contact-name" autocomplete="name" wire:model="name">@error('name')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="contact-email">{{ __('Email') }}</label><input id="contact-email" type="email" autocomplete="email" wire:model="email">@error('email')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="contact-phone">{{ __('Phone') }} ({{ __('optional') }})</label><input id="contact-phone" type="tel" dir="ltr" wire:model="phone">@error('phone')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="contact-subject">{{ __('Subject') }}</label><input id="contact-subject" wire:model="subject">@error('subject')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="contact-message">{{ __('Message') }}</label><textarea id="contact-message" rows="5" wire:model="message"></textarea>@error('message')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field" aria-hidden="true" style="position:absolute;left:-10000px"><label for="website">Website</label><input id="website" tabindex="-1" autocomplete="off" wire:model="website"></div>
        <button class="button" type="submit" wire:loading.attr="disabled" wire:target="send">{{ __('Send message') }}</button>
    </form>
</section>
