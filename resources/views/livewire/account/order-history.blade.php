<section>
    <div class="admin-heading"><h1>{{ __('My orders') }}</h1><a class="button secondary small" href="{{ route('account') }}" wire:navigate>{{ __('My account') }}</a></div>
    @forelse ($orders as $order)
        <article class="content-card" style="margin-bottom:12px">
            <div class="section-head" style="margin-top:0"><div><strong>{{ $order->order_number }}</strong><p class="note">{{ $order->created_at->format('d M Y') }}</p></div><span class="order-status">{{ __(ucfirst($order->status)) }}</span></div>
            <p>{{ $order->items->map(fn ($item) => $item->product_name.($item->variant_snapshot ? ' — '.implode(' / ', array_filter([app()->isLocale('ar') ? ($item->variant_snapshot['color_ar'] ?: $item->variant_snapshot['color_en']) : $item->variant_snapshot['color_en'], app()->isLocale('ar') ? ($item->variant_snapshot['size_ar'] ?: $item->variant_snapshot['size_en']) : $item->variant_snapshot['size_en']])) : ''))->join(', ') }}</p><strong>{{ number_format((float) $order->total, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong>
            @if ($order->status === 'pending')
                <button class="danger-button" wire:click="cancelOrder({{ $order->id }})" wire:confirm="{{ __('Cancel this order?') }}">{{ __('Cancel order') }}</button>
            @endif
            @error('order')<p class="alert">{{ $message }}</p>@enderror
        </article>
    @empty
        <div class="empty-state"><h2>{{ __('No orders yet.') }}</h2><a class="button" href="{{ route('shop') }}" wire:navigate>{{ __('Continue shopping') }}</a></div>
    @endforelse
    {{ $orders->links() }}
</section>
