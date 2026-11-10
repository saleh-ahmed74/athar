<section>
    <div class="admin-heading"><div><h1>{{ __('Store settings') }}</h1><p class="note">{{ __('All customer-facing store content is managed here and saved to the database.') }}</p></div></div>
    @if (session('status'))<p class="status-message">{{ session('status') }}</p>@endif
    <form class="admin-card admin-form" wire:submit="save">
        @php
        $groups = [
            __('Brand and contact') => [
                ['store_name', __('Store name'), 'text'], ['logo_url', __('Logo image URL or file'), 'text'], ['currency_symbol', __('Currency symbol'), 'text'],
                ['support_email', __('Support email'), 'email'], ['support_phone', __('Support phone'), 'text'], ['address', __('Business address'), 'text'], ['shipping_fee', __('Flat shipping fee'), 'number'],
                ['instagram_url', __('Instagram URL'), 'url'], ['facebook_url', __('Facebook URL'), 'url'], ['tiktok_url', __('TikTok URL'), 'url'], ['whatsapp_url', __('WhatsApp URL'), 'url'],
            ],
            __('Announcement bar') => [
                ['announcement_en', __('Announcement (EN)'), 'text'], ['announcement_ar', __('Announcement (AR)'), 'text'],
            ],
            __('Homepage hero') => [
                ['hero_eyebrow_en', __('Hero eyebrow (EN)'), 'text'], ['hero_eyebrow_ar', __('Hero eyebrow (AR)'), 'text'],
                ['hero_title_en', __('Hero title (EN)'), 'textarea'], ['hero_title_ar', __('Hero title (AR)'), 'textarea'],
                ['hero_text_en', __('Hero text (EN)'), 'textarea'], ['hero_text_ar', __('Hero text (AR)'), 'textarea'],
                ['hero_cta_en', __('Hero button (EN)'), 'text'], ['hero_cta_ar', __('Hero button (AR)'), 'text'],
            ],
            __('Checkout') => [
                ['payment_label_en', __('Payment method label (EN)'), 'text'], ['payment_label_ar', __('Payment method label (AR)'), 'text'],
            ],
            __('Inventory') => [
                ['track_stock', __('Track stock quantities'), 'checkbox'],
            ],
            __('Order follow-up') => [
                ['order_confirmation_en', __('Customer service confirmation (EN)'), 'textarea'], ['order_confirmation_ar', __('Customer service confirmation (AR)'), 'textarea'],
            ],
            __('Homepage sections') => [
                ['categories_heading_en', __('Categories heading (EN)'), 'text'], ['categories_heading_ar', __('Categories heading (AR)'), 'text'],
                ['featured_heading_en', __('Featured heading (EN)'), 'text'], ['featured_heading_ar', __('Featured heading (AR)'), 'text'],
            ],
            __('Footer and search preview') => [
                ['tagline_en', __('Footer tagline (EN)'), 'textarea'], ['tagline_ar', __('Footer tagline (AR)'), 'textarea'],
                ['seo_description_en', __('Search description (EN)'), 'textarea'], ['seo_description_ar', __('Search description (AR)'), 'textarea'],
            ],
            __('About page') => [
                ['about_en', __('About (EN)'), 'textarea'], ['about_ar', __('About (AR)'), 'textarea'],
            ],
            __('Policies') => [
                ['policy_en', __('Policy (EN)'), 'textarea'], ['policy_ar', __('Policy (AR)'), 'textarea'],
            ],
        ];
        @endphp
        <h2>{{ __('Shipping zones') }}</h2>
        <p class="note">{{ __('Set the delivery areas and fee customers see at checkout. Leave the list empty to use the flat fee below.') }}</p>
        @foreach ($form['shipping_zones'] as $index => $zone)
            <div class="form-grid" wire:key="shipping-zone-{{ $zone['id'] }}">
                <div class="field"><label>{{ __('Zone name (English)') }}</label><input wire:model="form.shipping_zones.{{ $index }}.name_en">@error("form.shipping_zones.$index.name_en")<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label>{{ __('Zone name (Arabic)') }}</label><input dir="rtl" wire:model="form.shipping_zones.{{ $index }}.name_ar">@error("form.shipping_zones.$index.name_ar")<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label>{{ __('Shipping fee (EGP)') }}</label><input type="number" min="0" step="0.01" wire:model="form.shipping_zones.{{ $index }}.fee">@error("form.shipping_zones.$index.fee")<span class="error">{{ $message }}</span>@enderror</div>
                <div><button class="button secondary small" type="button" wire:click="removeShippingZone({{ $index }})">{{ __('Remove zone') }}</button></div>
            </div>
        @endforeach
        @error('form.shipping_zones')<span class="error">{{ $message }}</span>@enderror
        <div><button class="button secondary small" type="button" wire:click="addShippingZone">{{ __('Add zone') }}</button></div>
        <h2>{{ __('Policy page sections') }}</h2>
        @foreach ($form['policy_sections'] as $index => $policySection)
            <div class="form-grid" wire:key="policy-section-{{ $policySection['id'] }}">
                <div class="field"><label>{{ __('Section title (English)') }}</label><input wire:model="form.policy_sections.{{ $index }}.title_en">@error("form.policy_sections.$index.title_en")<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label>{{ __('Section title (Arabic)') }}</label><input dir="rtl" wire:model="form.policy_sections.{{ $index }}.title_ar">@error("form.policy_sections.$index.title_ar")<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label>{{ __('Text (English)') }}</label><textarea rows="4" wire:model="form.policy_sections.{{ $index }}.body_en"></textarea>@error("form.policy_sections.$index.body_en")<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label>{{ __('Text (Arabic)') }}</label><textarea rows="4" dir="rtl" wire:model="form.policy_sections.{{ $index }}.body_ar"></textarea>@error("form.policy_sections.$index.body_ar")<span class="error">{{ $message }}</span>@enderror</div>
                <div><button class="button secondary small" type="button" wire:click="removePolicySection({{ $index }})">{{ __('Remove section') }}</button></div>
            </div>
        @endforeach
        @error('form.policy_sections')<span class="error">{{ $message }}</span>@enderror
        <div><button class="button secondary small" type="button" wire:click="addPolicySection">{{ __('Add section') }}</button></div>
        <label class="checkbox-row"><input type="checkbox" wire:model="form.show_announcement">{{ __('Show announcement bar') }}</label>
        @error('form.show_announcement')<span class="error">{{ $message }}</span>@enderror
        @foreach ($groups as $heading => $fields)
            <h2>{{ $heading }}</h2>
            <div class="form-grid">
                @foreach ($fields as [$key, $label, $type])
                    <div class="field">
                        <label for="setting-{{ $key }}">{{ $label }}</label>
                        @if ($type === 'checkbox')<label class="checkbox-row"><input id="setting-{{ $key }}" type="checkbox" wire:model="form.{{ $key }}">{{ $label }}</label>@if($key === 'track_stock')<p class="note">{{ __('When disabled, products can be ordered without stock counts and orders wait for customer service confirmation.') }}</p>@endif
                        @elseif ($type === 'textarea')<textarea id="setting-{{ $key }}" rows="4" dir="{{ str_ends_with($key, '_ar') ? 'rtl' : 'auto' }}" wire:model="form.{{ $key }}"></textarea>
                        @else<input id="setting-{{ $key }}" type="{{ $type }}" @if($type === 'number') min="0" step="0.01" @endif dir="{{ str_ends_with($key, '_ar') ? 'rtl' : 'auto' }}" wire:model="form.{{ $key }}">@endif
                        @if ($key === 'logo_url')
                            <label class="upload-button" for="logo-file">{{ __('Upload logo image') }}</label><input class="visually-hidden" id="logo-file" type="file" accept="image/jpeg,image/png,image/webp" wire:model="logoFile">
                            @if ($logoFile && $logoFile->isPreviewable())<img class="settings-image-preview" src="{{ $logoFile->temporaryUrl() }}" alt="{{ __('Logo preview') }}">@elseif(\App\Models\StoreSetting::current()->imageUrl('logo_url'))<img class="settings-image-preview" src="{{ \App\Models\StoreSetting::current()->imageUrl('logo_url') }}" alt="{{ __('Current logo') }}">@endif
                            <span class="note">{{ __('Uploaded site images are stored under storage/app/public/site and referenced by the database.') }}</span>
                        @endif
                        @error('form.'.$key)<span class="error">{{ $message }}</span>@enderror
                    </div>
                @endforeach
            </div>
            @if ($heading === __('Homepage hero'))
                <div class="field hero-gallery-setting">
                    <label>{{ __('Homepage gallery images') }}</label>
                    <label class="upload-button" for="hero-images">{{ __('Upload homepage images') }}</label>
                    <input class="visually-hidden" id="hero-images" type="file" accept="image/jpeg,image/png,image/webp" multiple wire:model="heroImageFiles">
                    @error('heroImageFiles')<span class="error">{{ $message }}</span>@enderror @error('heroImageFiles.*')<span class="error">{{ $message }}</span>@enderror
                    <div wire:loading wire:target="heroImageFiles">{{ __('Uploading image...') }}</div>
                    <div class="hero-gallery-preview">
                        @foreach ($form['hero_images'] as $index => $image)
                            @php($previewUrl = filter_var($image, FILTER_VALIDATE_URL) ? $image : '/storage/'.ltrim($image, '/'))
                            <div class="hero-gallery-preview-item" wire:key="saved-hero-image-{{ $index }}"><img src="{{ $previewUrl }}" alt="{{ __('Homepage image preview') }}"><button type="button" class="button secondary small" wire:click="removeHeroImage({{ $index }})">{{ __('Remove image') }}</button></div>
                        @endforeach
                        @foreach ($heroImageFiles as $index => $image)
                            <div class="hero-gallery-preview-item" wire:key="new-hero-image-{{ $index }}">@if ($image->isPreviewable())<img src="{{ $image->temporaryUrl() }}" alt="{{ __('Homepage image preview') }}">@endif<button type="button" class="button secondary small" wire:click="removeNewHeroImage({{ $index }})">{{ __('Remove image') }}</button></div>
                        @endforeach
                    </div>
                    <span class="note">{{ __('Upload up to ten images. They rotate automatically on the homepage; swipe or use the arrow keys to move between them.') }}</span>
                </div>
            @endif
        @endforeach
        <button class="button" type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('Save settings') }}</button>
    </form>
</section>
