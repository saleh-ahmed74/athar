<section>
    <div class="admin-heading"><h1>{{ __('Messages') }}</h1><span class="note">{{ __('Customer enquiries') }}</span></div>
    @if (session('status'))<p class="status-message" x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 2000)" x-show="visible"><span>{{ session('status') }}</span><button type="button" class="admin-toast-close" x-on:click="visible = false" aria-label="{{ __('Close') }}">×</button></p>@endif
    <div class="admin-table-wrap messages-table-wrap"><table class="admin-table messages-table"><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Contact') }}</th><th>{{ __('Subject') }}</th><th>{{ __('Received') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
    @forelse ($messages as $message)
        @php($whatsappPhone = preg_replace('/\D+/', '', $message->phone ?? '') ?? '')
        @if(str_starts_with($whatsappPhone, '0')) @php($whatsappPhone = '20'.ltrim($whatsappPhone, '0')) @endif
        <tr>
            <td data-label="{{ __('Name') }}"><strong>{{ $message->name }}</strong><details wire:key="message-details-{{ $message->id }}" wire:ignore.self @if(!$message->read_at) x-on:toggle="$el.open && $wire.markRead({{ $message->id }})" @endif><summary>{{ __('Read message') }}</summary><p style="white-space:pre-wrap;max-width:400px">{{ $message->message }}</p></details></td>
            <td data-label="{{ __('Contact') }}"><div class="contact-actions">
                <a class="contact-action contact-email" href="https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to={{ urlencode($message->email) }}" target="_blank" rel="noopener noreferrer" title="{{ __('Email customer in Gmail') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h13A2.5 2.5 0 0 1 21 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5v-11Zm2 .1v.2l7 5.1 7-5.1v-.2a.5.5 0 0 0-.5-.5h-13a.5.5 0 0 0-.5.5Zm14 2.7-6.4 4.7a1 1 0 0 1-1.2 0L5 9.3v8.2a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5V9.3Z"></path></svg><span>Gmail</span><bdi>{{ $message->email }}</bdi></a>
                @if($message->phone)<a class="contact-action contact-whatsapp" href="https://wa.me/{{ $whatsappPhone }}" target="_blank" rel="noopener noreferrer" dir="ltr" title="{{ __('Contact customer on WhatsApp') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.8a8.5 8.5 0 0 1-12.6 7.4L3 20.5l1.4-4.7a8.5 8.5 0 1 1 16.1-4Zm-12.1-3c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3.2 5 4.3 2.5 1 3 .8 3.5.7.5-.1 1.7-.7 1.9-1.4.2-.7.2-1.3.1-1.4-.1-.1-.3-.2-.7-.4l-2-.9c-.3-.1-.5-.2-.7.2-.2.3-.8 1-.9 1.2-.2.2-.3.2-.7.1-.4-.2-1.4-.5-2.6-1.6-1-.9-1.6-2-1.8-2.3-.2-.4 0-.5.1-.7l.5-.6c.2-.2.2-.4.3-.6.1-.2 0-.5 0-.6l-.9-2Z"></path></svg><span>WhatsApp</span><bdi>{{ $message->phone }}</bdi></a>@endif
            </div></td>
            <td data-label="{{ __('Subject') }}">{{ $message->subject }}</td>
            <td data-label="{{ __('Received') }}">{{ $message->created_at->format('d M Y H:i') }}</td>
            <td data-label="{{ __('Status') }}">{{ $message->read_at ? __('Read') : __('Unread') }}</td>
            <td data-label="{{ __('Actions') }}"><div class="table-actions"><button class="danger-button" wire:click="deleteMessage({{ $message->id }})" wire:confirm="{{ __('Delete this message?') }}">{{ __('Delete') }}</button></div></td>
        </tr>
    @empty<tr><td colspan="6">{{ __('No messages yet.') }}</td></tr>@endforelse
    </tbody></table></div><div style="margin-top:14px">{{ $messages->links() }}</div>
</section>
