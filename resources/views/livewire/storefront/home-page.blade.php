@php($language = app()->isLocale('ar') ? 'ar' : 'en')
<section>
    @if ($settings->hero_title_ar || $settings->hero_title_en)
        <div class="hero">
            @if ($heroImage = $settings->imageUrl('hero_image_url'))<img class="hero-image" src="{{ $heroImage }}" alt="" fetchpriority="high" width="1200" height="480">@endif
            @if ($settings->localized('hero_eyebrow'))<span class="hero-eyebrow">{{ $settings->localized('hero_eyebrow') }}</span>@endif
            <h1>{{ $settings->localized('hero_title') }}</h1>
            @if ($settings->localized('hero_text'))<p>{{ $settings->localized('hero_text') }}</p>@endif
            @if ($settings->localized('hero_cta'))<a class="button" href="{{ route('shop') }}" wire:navigate>{{ $settings->localized('hero_cta') }}</a>@endif
        </div>
    @endif

    @if ($categories->isNotEmpty())
        <div class="section-head"><h2>{{ $settings->localized('categories_heading') }}</h2><a class="text-link" href="{{ route('shop') }}" wire:navigate>{{ __('View all') }}</a></div>
        <div class="category-grid">
            @foreach ($categories as $category)
                <a class="category-tile" href="{{ route('category.show', $category->slug) }}" wire:navigate>
                    @php($categoryImage = $category->image_path ?: $category->products->first()?->image_path)
                    @if ($categoryImage)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($categoryImage) }}" alt="" loading="lazy">@endif
                    <span>{{ app()->isLocale('ar') ? ($category->name_ar ?: $category->name) : $category->name }}</span>
                </a>
            @endforeach
        </div>
    @endif

    @if ($settings->localized('featured_heading'))<div class="section-head"><h2>{{ $settings->localized('featured_heading') }}</h2><a class="text-link" href="{{ route('shop') }}" wire:navigate>{{ __('Shop all') }}</a></div>@endif
    <div class="product-grid">
        @forelse ($featuredProducts as $product)
            <article class="home-product-item" wire:key="home-product-{{ $product->id }}">
                <x-storefront.product-card :product="$product" />
                <button class="button small home-add-to-cart" data-offline-add-to-cart="{{ $product->id }}" x-on:click="window.captureCartOrigin($el)" wire:click="addToCart({{ $product->id }})" @disabled($settings->track_stock && $product->stock < 1)>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.1 11.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 1.9-1.4L22 9H7"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle></svg>
                    {{ $settings->track_stock && $product->stock < 1 ? __('Out of stock') : __('Add to cart') }}
                </button>
            </article>
        @empty
            <div class="empty-state"><h2>{{ __('No products found.') }}</h2><p>{{ __('Add products from the store administration to begin.') }}</p></div>
        @endforelse
    </div>
</section>
