<section class="admin-order-details">
    <div class="admin-heading">
        <div>
            <p class="hero-eyebrow">{{ __('Order details') }}</p>
            <h1>{{ $order->order_number }}</h1>
            <span class="note">{{ $order->created_at->format('d M Y · H:i') }}</span>
        </div>
        <a class="button secondary small" href="{{ route('admin.orders') }}" wire:navigate>{{ __('Back to orders') }}</a>
    </div>

    <div class="order-detail-grid">
        <article class="order-detail-card">
            <h2>{{ __('Customer details') }}</h2>
            <dl>
                <div><dt>{{ __('Name') }}</dt><dd>{{ $order->customer_name }}</dd></div>
                <div><dt>{{ __('Email') }}</dt><dd dir="ltr">{{ $order->email ?: '—' }}</dd></div>
                <div><dt>{{ __('Phone') }}</dt><dd dir="ltr">{{ $order->phone }}</dd></div>
            </dl>
        </article>

        <article class="order-detail-card">
            <h2>{{ __('Shipping details') }}</h2>
            <dl>
                <div><dt>{{ __('City') }}</dt><dd>{{ $order->city }}</dd></div>
                <div><dt>{{ __('Address') }}</dt><dd>{{ $order->address }}</dd></div>
                <div><dt>{{ __('Customer notes') }}</dt><dd>{{ $order->notes ?: '—' }}</dd></div>
            </dl>
        </article>

        <article class="order-detail-card">
            <h2>{{ __('Payment and status') }}</h2>
            <dl>
                <div><dt>{{ __('Order status') }}</dt><dd><span class="order-status">{{ __(ucfirst($order->status === 'processing' ? 'confirmed' : $order->status)) }}</span></dd></div>
                <div><dt>{{ __('Payment status') }}</dt><dd>{{ __(ucfirst($order->payment_status)) }}</dd></div>
                <div><dt>{{ __('Payment method') }}</dt><dd>{{ __(ucfirst($order->payment_method)) }}</dd></div>
                @if ($order->return_reason)<div><dt>{{ __('Return reason') }}</dt><dd>{{ $order->return_reason }}</dd></div>@endif
            </dl>
        </article>
    </div>

    <article class="order-detail-card order-detail-items-card">
        <h2>{{ __('Items') }}</h2>
        <div class="admin-table-wrap">
            <table class="admin-table order-detail-items">
                <thead><tr><th>{{ __('Product') }}</th><th>{{ __('SKU') }}</th><th>{{ __('Unit price') }}</th><th>{{ __('Quantity') }}</th><th>{{ __('Line total') }}</th></tr></thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td data-label="{{ __('Product') }}">{{ $item->product_name }}@if($item->variant_snapshot)<span class="note">@if($item->variant_snapshot['color_en'] ?? false){{ __('Color') }}: {{ app()->isLocale('ar') ? ($item->variant_snapshot['color_ar'] ?: $item->variant_snapshot['color_en']) : $item->variant_snapshot['color_en'] }} @endif @if($item->variant_snapshot['size_en'] ?? false){{ __('Size') }}: {{ app()->isLocale('ar') ? ($item->variant_snapshot['size_ar'] ?: $item->variant_snapshot['size_en']) : $item->variant_snapshot['size_en'] }}@endif</span>@endif</td>
                            <td data-label="{{ __('SKU') }}">{{ $item->sku ?: '—' }}</td>
                            <td data-label="{{ __('Unit price') }}">{{ number_format((float) $item->unit_price, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</td>
                            <td data-label="{{ __('Quantity') }}">{{ $item->quantity }}</td>
                            <td data-label="{{ __('Line total') }}">{{ number_format((float) $item->line_total, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <dl class="order-detail-totals">
            <div><dt>{{ __('Subtotal') }}</dt><dd>{{ number_format((float) $order->subtotal, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</dd></div>
            <div><dt>{{ __('Shipping') }}</dt><dd>{{ number_format((float) $order->shipping_fee, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</dd></div>
            <div class="order-grand-total"><dt>{{ __('Total') }}</dt><dd>{{ number_format((float) $order->total, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</dd></div>
        </dl>
    </article>
</section>
