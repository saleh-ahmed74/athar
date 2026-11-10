<section>
    <div class="product-detail">
        <div class="detail-gallery">
            @if ($productImages !== [])
                <div class="detail-gallery-track" aria-label="{{ __('Product photos') }}" @if (count($productImages) > 1) tabindex="0" @endif
                    x-data="{ touchStartX: 0, touchStartY: 0 }"
                    x-on:touchstart.passive="touchStartX = $event.changedTouches[0].clientX; touchStartY = $event.changedTouches[0].clientY"
                    x-on:touchend="let deltaX = $event.changedTouches[0].clientX - touchStartX; let deltaY = $event.changedTouches[0].clientY - touchStartY; if (Math.abs(deltaX) > 40 && Math.abs(deltaX) > Math.abs(deltaY)) { $wire.call(deltaX < 0 ? 'nextImage' : 'previousImage') }"
                    x-on:keydown.left.prevent="$wire.previousImage()" x-on:keydown.right.prevent="$wire.nextImage()">
                    @foreach ($productImages as $index => $imagePath)
                        <figure @class(['detail-image', 'is-hidden' => $activeImageIndex !== $index]) id="product-photo-{{ $product->id }}-{{ $index }}" aria-hidden="{{ $activeImageIndex !== $index ? 'true' : 'false' }}"><img src="{{ '/storage/'.ltrim($imagePath, '/') }}" alt="{{ app()->isLocale('ar') ? ($product->name_ar ?: $product->name) : $product->name }} — {{ $index + 1 }}"></figure>
                    @endforeach
                    @if (count($productImages) > 1)
                        <button class="gallery-arrow gallery-arrow-previous" type="button" wire:click="previousImage" aria-label="{{ __('Previous photo') }}">‹</button>
                        <button class="gallery-arrow gallery-arrow-next" type="button" wire:click="nextImage" aria-label="{{ __('Next photo') }}">›</button>
                        <span class="gallery-count">{{ $activeImageIndex + 1 }} / {{ count($productImages) }}</span>
                    @endif
                </div>
                @if (count($productImages) > 1)
                    <nav class="gallery-thumbnails" aria-label="{{ __('Choose a product photo') }}">
                        @foreach ($productImages as $index => $imagePath)
                            <button type="button" wire:click="selectImage({{ $index }})" aria-label="{{ __('Show photo') }} {{ $index + 1 }}" @class(['is-selected' => $activeImageIndex === $index])><img src="{{ '/storage/'.ltrim($imagePath, '/') }}" alt="{{ app()->isLocale('ar') ? ($product->name_ar ?: $product->name) : $product->name }} — {{ $index + 1 }}"></button>
                        @endforeach
                    </nav>
                @endif
            @else
                <div class="detail-image"><span class="image-placeholder">{{ \App\Models\StoreSetting::current()->store_name ?: config('app.name') }}</span></div>
            @endif
        </div>
        <div class="detail-copy">
            <a class="text-link" href="{{ route('category.show', $product->category->slug) }}" wire:navigate>{{ app()->isLocale('ar') ? ($product->category->name_ar ?: $product->category->name) : $product->category->name }}</a>
            <h1>{{ app()->isLocale('ar') ? ($product->name_ar ?: $product->name) : $product->name }}</h1>
            <div class="detail-price">{{ number_format((float) $product->price, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }} @if ($product->compare_at_price && $product->compare_at_price > $product->price)<s class="note">{{ number_format((float) $product->compare_at_price, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</s>@endif</div>
            <p>{{ app()->isLocale('ar') ? ($product->description_ar ?: $product->description) : $product->description }}</p>
            @if (! $trackStock || $product->stock > 0)
                <label class="field" style="max-width:120px"><span>{{ __('Quantity') }}</span><input type="number" min="1" max="{{ $trackStock ? $product->stock : 99 }}" data-offline-add-quantity wire:model="quantity"></label>
                <button class="button" data-offline-add-to-cart="{{ $product->id }}" x-on:click="window.captureCartOrigin($el)" wire:click="addToCart">{{ __('Add to cart') }}</button>
                @if ($trackStock)<span class="note">{{ __('In stock') }}: {{ $product->stock }}</span>@endif
            @else
                <span class="note">{{ __('Out of stock') }}</span>
            @endif
        </div>
    </div>
    @if ($relatedProducts->isNotEmpty())
        <div class="section-head"><h2>{{ __('You may also like') }}</h2><a class="text-link" href="{{ route('shop') }}" wire:navigate>{{ __('Shop all') }}</a></div>
        <div class="product-grid">@foreach ($relatedProducts as $related)<x-storefront.product-card :product="$related" />@endforeach</div>
    @endif
</section>

