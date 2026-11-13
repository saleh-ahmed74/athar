<section class="content-card">
    <p class="hero-eyebrow">{{ \App\Models\StoreSetting::current()->store_name ?: config('app.name') }}</p><h1 class="page-title">{{ __('Order request received.') }}</h1>
    <p>{{ __('Order number') }}: <strong>{{ $order->order_number }}</strong></p>
    <p>{{ $settings->localized('order_confirmation') }} <strong dir="ltr">{{ $order->phone }}</strong></p>
    <div class="cart-total" style="max-width:520px">
        @foreach ($order->items as $item)<div><span>{{ $item->product_name }}@if($item->variant_snapshot) — @if($item->variant_snapshot['color_en'] ?? false){{ app()->isLocale('ar') ? ($item->variant_snapshot['color_ar'] ?: $item->variant_snapshot['color_en']) : $item->variant_snapshot['color_en'] }} @endif @if($item->variant_snapshot['size_en'] ?? false){{ app()->isLocale('ar') ? ($item->variant_snapshot['size_ar'] ?: $item->variant_snapshot['size_en']) : $item->variant_snapshot['size_en'] }}@endif @endif × {{ $item->quantity }}</span><strong>{{ number_format((float) $item->line_total, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div>@endforeach
        <hr><div><span>{{ __('Shipping') }}</span><strong>{{ number_format((float) $order->shipping_fee, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div><div><strong>{{ __('Total') }}</strong><strong>{{ number_format((float) $order->total, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div>
    </div>
    <p>{{ __('Cash on delivery') }}</p><a class="button" href="{{ route('shop') }}" wire:navigate>{{ __('Continue shopping') }}</a>
</section>
