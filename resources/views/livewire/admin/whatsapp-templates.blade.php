<section>
    <div class="whatsapp-template-topbar">
        <a class="button secondary small" href="{{ route('admin.orders') }}" wire:navigate>{{ __('Back to orders') }}</a>
    </div>
    <div class="whatsapp-template-list">
        @foreach ($statuses as $status)
            <article class="admin-card whatsapp-template-card" wire:key="whatsapp-template-{{ $status }}">
                <div class="whatsapp-template-card-heading">
                    <h2>{{ __(ucfirst($status)) }}</h2>
                    @if (filled($templates[$status] ?? ''))
                        <button class="danger-button" type="button" wire:click="deleteTemplate('{{ $status }}')" wire:confirm="{{ __('Remove this WhatsApp message template?') }}">{{ __('Delete message') }}</button>
                    @else
                        <span class="note">{{ __('No message saved for this status') }}</span>
                    @endif
                </div>
                <label class="field">
                    <span>{{ __('Message in Egyptian Arabic') }}</span>
                    <textarea rows="7" dir="rtl" lang="ar" wire:model.blur="templates.{{ $status }}" wire:loading.attr="disabled" wire:target="templates.{{ $status }}" placeholder="{{ __('Write the WhatsApp message for this status') }}"></textarea>
                </label>
                @error('templates.'.$status)<span class="error">{{ $message }}</span>@enderror
                <div class="whatsapp-template-save-row">
                    <button class="button small" type="button" wire:click="saveTemplate('{{ $status }}')" wire:loading.attr="disabled" wire:target="saveTemplate('{{ $status }}')">{{ __('Save changes') }}</button>
                </div>
                <div class="whatsapp-template-feedback" role="status" aria-live="polite">
                    <span wire:loading wire:target="templates.{{ $status }}">{{ __('Saving message…') }}</span>
                </div>
            </article>
        @endforeach
    </div>
</section>
