@php($language = app()->isLocale('ar') ? 'ar' : 'en')
@php($heroImages = $settings->heroImageUrls())
<section>
    @if ($settings->hero_title_ar || $settings->hero_title_en || count($heroImages) > 0)
        <div class="hero" x-data="{ copyOpen: true, activeSlide: 0, pointerStart: null, slideCount: @js(max(count($heroImages), 1)), next() { this.activeSlide = (this.activeSlide + 1) % this.slideCount }, previous() { this.activeSlide = (this.activeSlide - 1 + this.slideCount) % this.slideCount }, start(event) { if (event.target.closest('button, a')) return; this.pointerStart = event.clientX; event.currentTarget.setPointerCapture(event.pointerId) }, finish(event) { if (this.pointerStart === null) return; const distance = event.clientX - this.pointerStart; if (Math.abs(distance) > 40) distance < 0 ? this.next() : this.previous(); this.pointerStart = null } }" x-bind:class="{ 'hero-copy-open': copyOpen, 'hero-copy-closed': !copyOpen }" @if(count($heroImages) > 1) x-init="setInterval(() => next(), 5000)" x-on:pointerdown="start($event)" x-on:pointerup="finish($event)" x-on:pointercancel="pointerStart = null" x-on:keydown.arrow-right.prevent="next()" x-on:keydown.arrow-left.prevent="previous()" tabindex="0" aria-roledescription="carousel" aria-label="{{ __('Homepage gallery images') }}" @endif>
            @foreach ($heroImages as $index => $heroImage)<img class="hero-image{{ $loop->first ? ' is-active' : '' }}" src="{{ $heroImage }}" alt="" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif width="1600" height="760" @if(count($heroImages) > 1) x-bind:class="{ 'is-active': activeSlide === {{ $index }} }" x-bind:aria-hidden="activeSlide !== {{ $index }}" @endif>@endforeach
            @if (count($heroImages) > 1)
                <button class="hero-gallery-arrow hero-gallery-arrow-previous" type="button" x-on:click.stop="previous()" aria-label="{{ __('Previous photo') }}">‹</button>
                <button class="hero-gallery-arrow hero-gallery-arrow-next" type="button" x-on:click.stop="next()" aria-label="{{ __('Next photo') }}">›</button>
            @endif
            @if ($settings->localized('hero_eyebrow') || $settings->localized('hero_title') || $settings->localized('hero_text') || $settings->localized('hero_cta'))
            <div class="hero-copy-panel">
                <div class="hero-content" x-show="copyOpen" x-transition>
                    <button class="hero-content-close" type="button" x-on:click.stop="copyOpen = false" aria-label="{{ __('Close hero text') }}">×</button>
                    @if ($settings->localized('hero_eyebrow'))<span class="hero-eyebrow">{{ $settings->localized('hero_eyebrow') }}</span>@endif
                    @if ($settings->localized('hero_title'))<h1>{{ $settings->localized('hero_title') }}</h1>@endif
                    @if ($settings->localized('hero_text'))<p>{{ $settings->localized('hero_text') }}</p>@endif
                    @if ($settings->localized('hero_cta'))<a class="button" href="{{ route('shop') }}" wire:navigate>{{ $settings->localized('hero_cta') }}</a>@endif
                </div>
                <button class="hero-copy-reopen" type="button" x-show="!copyOpen" x-transition x-on:click.stop="copyOpen = true">{{ __('Show hero text') }}</button>
            </div>
            @endif
        </div>
    @endif

    @if ($categories->isNotEmpty())
        <div class="home-category-section">
        <div class="section-head"><h2>{{ $settings->localized('categories_heading') }}</h2><button class="text-link home-view-categories" type="button" wire:click="toggleCategories" aria-expanded="{{ $showAllCategories ? 'true' : 'false' }}">{{ $showAllCategories ? __('Show less') : __('View all') }}</button></div>
        <div class="category-grid">
            @foreach ($categories as $category)
                <a class="category-tile" href="{{ route('category.show', $category->slug) }}" wire:navigate>
                    @php($categoryImage = $category->image_path ?: $category->products->first()?->image_path)
                    @if ($categoryImage)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($categoryImage) }}" alt="" loading="lazy">@else<span class="category-placeholder" aria-hidden="true">{{ mb_substr(app()->isLocale('ar') ? ($category->name_ar ?: $category->name) : $category->name, 0, 1) }}</span>@endif
                    <span>{{ app()->isLocale('ar') ? ($category->name_ar ?: $category->name) : $category->name }}</span>
                </a>
            @endforeach
        </div>
        </div>
    @endif

    @if ($homeProducts->isNotEmpty())
        <div class="section-head"><h2>{{ $bestSellerProducts->isNotEmpty() ? __('Best sellers') : ($settings->localized('featured_heading') ?: __('Newest')) }}</h2><a class="text-link" href="{{ $bestSellerProducts->isNotEmpty() ? route('shop', ['highlight' => 'bestseller']) : route('shop') }}" wire:navigate>{{ __('View all') }}</a></div>
        <div class="product-grid home-product-grid">
            @foreach ($homeProducts as $product)
                <article class="home-product-item" wire:key="home-product-{{ $product->id }}">
                    <x-storefront.product-card :product="$product" />
                    <button class="button small home-add-to-cart" data-offline-add-to-cart="{{ $product->id }}" x-on:click="window.captureCartOrigin($el)" wire:click="addToCart({{ $product->id }})" @disabled($settings->track_stock && $product->stock < 1)>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.1 11.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 1.9-1.4L22 9H7"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle></svg>
                        {{ $settings->track_stock && $product->stock < 1 ? __('Out of stock') : __('Add to cart') }}
                    </button>
                </article>
            @endforeach
        </div>
    @endif
</section>
