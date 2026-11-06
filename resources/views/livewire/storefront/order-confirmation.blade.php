<section class="content-card">
    <p class="hero-eyebrow">{{ \App\Models\StoreSetting::current()->store_name ?: config('app.name') }}</p><h1 class="page-title">{{ __('Order request received.') }}</h1>
    <p>{{ __('Order number') }}: <strong>{{ $order->order_number }}</strong></p>
    <p>{{ $settings->localized('order_confirmation') }} <strong dir="ltr">{{ $order->phone }}</strong></p>
    <div class="cart-total" style="max-width:520px">
        @foreach ($order->items as $item)<div><span>{{ $item->product_name }} × {{ $item->quantity }}</span><strong>{{ number_format((float) $item->line_total, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div>@endforeach
        <hr><div><span>{{ __('Shipping') }}</span><strong>{{ number_format((float) $order->shipping_fee, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div><div><strong>{{ __('Total') }}</strong><strong>{{ number_format((float) $order->total, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div>
    </div>
    <p>{{ __('Cash on delivery') }}</p><a class="button" href="{{ route('shop') }}" wire:navigate>{{ __('Continue shopping') }}</a>
</section>
