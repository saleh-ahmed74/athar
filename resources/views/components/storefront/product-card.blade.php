@props(['product', 'priority' => false])
@php
@endphp
<article class="product-card">
    <a class="product-image" href="{{ route('product.show', $product->slug) }}" wire:navigate>
        @if ($product->image_path)
            <img src="{{ '/storage/'.ltrim($product->image_path, '/') }}"
                alt="{{ app()->isLocale('ar') ? ($product->name_ar ?: $product->name) : $product->name }}" @if ($priority) fetchpriority="high" loading="eager" @else loading="lazy" @endif>
        @else
            <span
                class="image-placeholder">{{ \App\Models\StoreSetting::current()->store_name ?: config('app.name') }}</span>
        @endif
        @if ($product->compare_at_price && $product->compare_at_price > $product->price)
            <span class="product-badge">{{ __('Special price') }}</span>
        @endif
    </a>
    <div class="product-info">
        <a class="product-name" href="{{ route('product.show', $product->slug) }}"
            wire:navigate>{{ app()->isLocale('ar') ? ($product->name_ar ?: $product->name) : $product->name }}</a>
        <div class="product-price"><strong>{{ number_format((float) $product->price, 2) }}
                {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong>
            @if ($product->compare_at_price && $product->compare_at_price > $product->price)<s>{{ number_format((float) $product->compare_at_price, 2) }}
            {{ \App\Models\StoreSetting::current()->currency_symbol }}</s>@endif
        </div>
    </div>
</article>
