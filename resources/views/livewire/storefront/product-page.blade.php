<section>
    <div class="product-detail">
        <div class="detail-gallery" x-data="{ zoomedImage: null }" x-on:keydown.escape.window="zoomedImage = null">
            @if ($productImages !== [])
                <div class="detail-gallery-track" aria-label="{{ __('Product photos') }}" @if (count($productImages) > 1) tabindex="0" @endif
                    x-data="{ pointerStartX: null, pointerStartY: null, touchStartX: null, touchStartY: null, arrowTimer: null, revealArrows() { $el.classList.add('is-interacting'); clearTimeout(this.arrowTimer); this.arrowTimer = setTimeout(() => $el.classList.remove('is-interacting'), 1800) }, startSwipe(event) { if (event.target.closest('button, a')) return; this.pointerStartX = event.clientX; this.pointerStartY = event.clientY; event.currentTarget.setPointerCapture(event.pointerId) }, finishSwipe(event) { if (this.pointerStartX === null) return; const deltaX = event.clientX - this.pointerStartX; const deltaY = event.clientY - this.pointerStartY; if (Math.abs(deltaX) > 40 && Math.abs(deltaX) > Math.abs(deltaY)) { this.navigateBySwipe(deltaX) } this.pointerStartX = null; this.pointerStartY = null }, startTouch(event) { if (event.target.closest('button, a')) return; this.touchStartX = event.touches[0].clientX; this.touchStartY = event.touches[0].clientY }, finishTouch(event) { if (this.touchStartX === null) return; const deltaX = event.changedTouches[0].clientX - this.touchStartX; const deltaY = event.changedTouches[0].clientY - this.touchStartY; if (Math.abs(deltaX) > 40 && Math.abs(deltaX) > Math.abs(deltaY)) this.navigateBySwipe(deltaX); this.touchStartX = null; this.touchStartY = null }, navigateBySwipe(deltaX) { this.revealArrows(); $wire.call(deltaX < 0 ? 'nextImage' : 'previousImage') } }"
                    x-on:pointerdown="startSwipe($event)" x-on:pointerup="finishSwipe($event)" x-on:pointercancel="pointerStartX = null; pointerStartY = null"
                    x-on:touchstart.passive="startTouch($event)" x-on:touchend="finishTouch($event)" x-on:touchcancel="touchStartX = null; touchStartY = null"
                    x-on:keydown.left.prevent="$wire.previousImage()" x-on:keydown.right.prevent="$wire.nextImage()">
                    @foreach ($productImages as $index => $imagePath)
                        <figure @class(['detail-image', 'is-hidden' => $activeImageIndex !== $index]) id="product-photo-{{ $product->id }}-{{ $index }}" aria-hidden="{{ $activeImageIndex !== $index ? 'true' : 'false' }}"><button class="product-photo-zoom" type="button" x-on:click="zoomedImage = $el.querySelector('img').currentSrc" aria-label="{{ __('Zoom image') }}"><img src="{{ '/storage/'.ltrim($imagePath, '/') }}" alt="{{ app()->isLocale('ar') ? ($product->name_ar ?: $product->name) : $product->name }} — {{ $index + 1 }}"></button></figure>
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
            <div class="product-image-lightbox" x-show="zoomedImage" x-transition x-on:click.self="zoomedImage = null" role="dialog" aria-modal="true" aria-label="{{ __('Zoom image') }}" style="display:none">
                <button class="product-image-lightbox-close" type="button" x-on:click="zoomedImage = null" aria-label="{{ __('Close image') }}">×</button>
                <img :src="zoomedImage" alt="{{ app()->isLocale('ar') ? ($product->name_ar ?: $product->name) : $product->name }}">
            </div>
        </div>
        <div class="detail-copy" x-data="{ get selectedOptionTotal() { return Object.values($wire.variantQuantities || {}).reduce((sum, quantity) => sum + Number(quantity || 0), 0) } }">
            <a class="text-link" href="{{ route('category.show', $product->category->slug) }}" wire:navigate>{{ app()->isLocale('ar') ? ($product->category->name_ar ?: $product->category->name) : $product->category->name }}</a>
            <h1>{{ app()->isLocale('ar') ? ($product->name_ar ?: $product->name) : $product->name }}</h1>
            <div class="detail-price">{{ number_format((float) $product->price, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }} @if ($product->compare_at_price && $product->compare_at_price > $product->price)<s class="note">{{ number_format((float) $product->compare_at_price, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</s>@endif</div>
            <p>{{ app()->isLocale('ar') ? ($product->description_ar ?: $product->description) : $product->description }}</p>
            @if ($combinations !== [])
                <fieldset class="variant-picker"><legend>{{ __('Choose quantities by color and size') }}</legend>
                    @php($matrixColors = $colors ?: [['id' => 'none']])
                    @php($matrixSizes = $sizes ?: [['id' => 'none']])
                    <div class="variant-matrix" style="--matrix-columns: {{ count($matrixSizes) }}" x-data="{ maximum: {{ $maximumVariantQuantity }}, get total() { return Object.values($wire.variantQuantities || {}).reduce((sum, quantity) => sum + Number(quantity || 0), 0) } }">
                        <div class="variant-matrix-header"><span>{{ $colors ? __('Color') : __('Size') }}</span>@foreach ($matrixSizes as $size)<span>{{ $size['id'] === 'none' ? __('Quantity') : (app()->isLocale('ar') ? (($size['size_ar'] ?? null) ?: $size['size_en']) : $size['size_en']) }}</span>@endforeach</div>
                        @foreach ($matrixColors as $color)
                            <div class="variant-matrix-row" wire:key="variant-matrix-{{ $color['id'] }}">
                                <div class="variant-matrix-color">
                                    @if (filled($color['color_hex'] ?? null))<i class="variant-color-swatch" style="--variant-swatch:{{ $color['color_hex'] }}" aria-hidden="true"></i>@endif
                                    <strong>{{ $color['id'] === 'none' ? __('Size') : (app()->isLocale('ar') ? (($color['color_ar'] ?? null) ?: $color['color_en']) : $color['color_en']) }}</strong>
                                </div>
                                @foreach ($matrixSizes as $size)
                                    @php($combinationKey = $color['id'].'__'.$size['id'])
                                    <div class="variant-matrix-cell" wire:key="variant-cell-{{ $combinationKey }}">
                                        <span class="matrix-size-name">{{ $size['id'] === 'none' ? __('Quantity') : (app()->isLocale('ar') ? (($size['size_ar'] ?? null) ?: $size['size_en']) : $size['size_en']) }}</span>
                                        <span class="quantity-stepper" role="group" aria-label="{{ __('Quantity') }} — {{ $color['color_en'] ?? '' }} {{ $size['size_en'] ?? '' }}" x-data="{ quantity: $wire.entangle('variantQuantities.{{ $combinationKey }}') }">
                                            <button type="button" x-on:click.stop="quantity = Math.max(0, Number(quantity || 0) - 1)" x-bind:disabled="Number(quantity || 0) === 0" aria-label="{{ __('Decrease quantity') }}">−</button>
                                            <output x-text="quantity">{{ (int) ($variantQuantities[$combinationKey] ?? 0) }}</output>
                                            <button type="button" x-on:click.stop="if (total < maximum) quantity = Math.min(10, Number(quantity || 0) + 1)" x-bind:disabled="total >= maximum" aria-label="{{ __('Increase quantity') }}">+</button>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </fieldset>
                @error('variantQuantities')<span class="error">{{ $message }}</span>@enderror
            @endif
            @if (! $trackStock || $product->stock > 0)
                @if ($combinations === [])<div class="product-quantity-stepper"><span>{{ __('Quantity') }}</span><div class="quantity-stepper" role="group" aria-label="{{ __('Quantity') }}" x-data="{ quantity: $wire.entangle('quantity'), maximum: {{ min(10, $trackStock ? $product->stock : 99) }} }"><button type="button" x-on:click.stop="quantity = Math.max(1, Number(quantity || 1) - 1)" x-bind:disabled="Number(quantity || 1) <= 1" aria-label="{{ __('Decrease quantity') }}">−</button><output x-text="quantity">{{ $quantity }}</output><button type="button" x-on:click.stop="quantity = Math.min(maximum, Number(quantity || 1) + 1)" x-bind:disabled="Number(quantity || 1) >= maximum" aria-label="{{ __('Increase quantity') }}">+</button><input type="hidden" data-offline-add-quantity x-bind:value="quantity"></div></div>@endif
                <button class="button" @if ($combinations === []) data-offline-add-to-cart="{{ $product->id }}" @else x-bind:disabled="selectedOptionTotal === 0" x-bind:aria-disabled="selectedOptionTotal === 0" @endif x-on:click="window.captureCartOrigin($el)" wire:click="addToCart">{{ __('Add to cart') }}</button>
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

