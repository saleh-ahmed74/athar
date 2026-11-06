<section>
    <div class="admin-heading">
        <h1>{{ __('Orders') }}</h1>
        <select aria-label="{{ __('Filter by status') }}" wire:model.live="statusFilter">
            <option value="">{{ __('All statuses') }}</option>
            @foreach ($statusOptions as $status)<option value="{{ $status }}">{{ __(ucfirst($status)) }}</option>@endforeach
        </select>
    </div>
    @if (session('status'))<p class="status-message">{{ session('status') }}</p>@endif
    @error('status')<p class="alert">{{ $message }}</p>@enderror
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr><th>{{ __('Order number') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Date') }}</th><th>{{ __('Items') }}</th><th>{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
        @forelse ($orders as $order)
            <tr>
                <td data-label="{{ __('Order number') }}"><div class="admin-order-cell">
                    @php($firstProductImage = $order->items->first()?->product?->image_path)
                    <a class="admin-order-image-link" href="{{ route('admin.orders.show', $order) }}" wire:navigate aria-label="{{ __('View order details for :number', ['number' => $order->order_number]) }}">@if ($firstProductImage)<img class="admin-thumb" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($firstProductImage) }}" alt="">@else<span class="admin-thumb image-placeholder">A</span>@endif</a>
                    <div><strong>{{ $order->order_number }}</strong><span class="note">{{ $order->created_at->format('d M Y') }}</span></div>
                </div></td>
                <td data-label="{{ __('Customer') }}"><div class="order-customer-cell"><strong>{{ $order->customer_name }}</strong><span class="note">{{ $order->city }}</span></div></td>
                <td data-label="{{ __('WhatsApp') }}" dir="ltr">
                    @if($this->hasWhatsAppTemplate($order))
                        <a class="whatsapp-order-link" href="{{ $this->whatsappConfirmationLink($order) }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Open WhatsApp message for :name', ['name' => $order->customer_name]) }}" title="{{ __('Opens WhatsApp with the current status message. Review before sending.') }}"><svg viewBox="0 0 448 512" aria-hidden="true"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32 101.5 32 1.9 131.6 1.9 254c0 39.1 10.2 77.3 29.6 111L0 480l118-30.9c32.5 17.7 69.1 27 105.8 27h.1c122.4 0 222-99.6 222-222 0-59.3-23.1-115.1-65-157zM223.9 438c-32.8 0-65-8.8-93-25.4l-6.7-4-70 18.3 18.7-68.2-4.4-7C50 322.2 40 288.6 40 254 40 152.6 122.5 70 223.9 70c49.1 0 95.2 19.1 129.9 53.9 34.7 34.7 53.8 80.9 53.8 130 0 101.4-82.5 184.1-183.7 184.1zm101-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.7-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.4-65.9-5.7-9.8 5.7-9.1 16.3-30.4 1.8-3.7.9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.8 22.6 57.5c2.8 3.7 39.2 59.8 95 83.9 35.3 15.2 49.1 16.5 66.7 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.4-2.3-5.1-3.7-10.6-6.5z"></path></svg><span>{{ __('WhatsApp') }}</span></a>
                    @endif
                    <a class="whatsapp-template-link" href="{{ route('admin.whatsapp-templates') }}">{{ __('Manage messages') }}</a>
                    <span class="note"><bdi>{{ $order->phone }}</bdi></span>
                </td>
                <td data-label="{{ __('Date') }}">{{ $order->created_at->format('d M Y') }}</td>
                <td data-label="{{ __('Items') }}"><div class="order-items-preview">@foreach ($order->items->take(2) as $item)<span>@if($item->product?->image_path)<img class="mini-thumb" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->product->image_path) }}" alt="">@endif{{ $item->product_name }} × {{ $item->quantity }}</span>@endforeach@if($order->items_count > 2)<span class="note">+{{ $order->items_count - 2 }} {{ __('more') }}</span>@endif</div></td>
                <td data-label="{{ __('Total') }}"><strong>{{ number_format((float) $order->total, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></td>
                <td data-label="{{ __('Status') }}" @class(['status-returning' => ($statusChoices[$order->id] ?? $order->status) === 'returned'])>
                    @php($currentStatus = $order->status === 'processing' ? 'confirmed' : $order->status)
                    <select class="order-status" aria-label="{{ __('Status for order :number', ['number' => $order->order_number]) }}" wire:change="changeStatus({{ $order->id }}, $event.target.value)">
                        @foreach ($statusOptions as $status)<option value="{{ $status }}" @selected($currentStatus === $status)>{{ __(ucfirst($status)) }}</option>@endforeach
                    </select>
                </td>
            </tr>
            @if (($statusChoices[$order->id] ?? null) === 'returned')
                <tr class="return-reason-row" wire:key="return-reason-{{ $order->id }}">
                    <td colspan="7"><div class="return-reason-editor">
                        <label class="return-reason-label" for="return-reason-{{ $order->id }}">{{ __('Return reason') }}</label>
                        <textarea id="return-reason-{{ $order->id }}" class="return-reason" rows="4" wire:model="returnReasons.{{ $order->id }}" placeholder="{{ __('Describe the reason for the return') }}"></textarea>
                        @error('returnReasons.'.$order->id)<span class="error">{{ $message }}</span>@enderror
                        <button class="button small" type="button" wire:click="updateStatusFor({{ $order->id }})" wire:loading.attr="disabled" wire:target="updateStatusFor({{ $order->id }})">{{ __('Save return status') }}</button>
                    </div></td>
                </tr>
            @elseif ($order->return_reason)
                <tr class="return-reason-summary"><td colspan="7"><strong>{{ __('Return reason') }}:</strong> {{ $order->return_reason }}</td></tr>
            @endif
        @empty
            <tr><td colspan="7">{{ __('No orders yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="margin-top:14px">{{ $orders->links() }}</div>
</section>
