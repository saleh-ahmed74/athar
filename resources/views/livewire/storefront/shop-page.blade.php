<section>
    <div class="section-head"><div><p class="hero-eyebrow">{{ \App\Models\StoreSetting::current()->store_name ?: config('app.name') }}</p><h1 class="page-title">{{ $selectedCategory ? (app()->isLocale('ar') ? ($selectedCategory->name_ar ?: $selectedCategory->name) : $selectedCategory->name) : __('All products') }}</h1></div></div>
    <div class="shop-filter-stack">
        <div class="filters shop-search-filters">
            <input type="search" aria-label="{{ __('Search products') }}" placeholder="{{ __('Search products') }}" wire:model.live.debounce.350ms="search">
            <select aria-label="{{ __('Sort by') }}" wire:model.live="sort">
                <option value="featured">{{ __('Newest') }}</option><option value="price_asc">{{ __('Price: low to high') }}</option><option value="price_desc">{{ __('Price: high to low') }}</option>
            </select>
        </div>
        <div class="price-range-filters">
        <label class="field price-filter-field"><span>{{ __('Minimum price (EGP)') }}</span><input type="number" min="0" step="0.01" inputmode="decimal" aria-label="{{ __('Minimum price (EGP)') }}" placeholder="{{ __('Minimum price (EGP)') }}" wire:model.live.debounce.350ms="minPrice"></label>
        <label class="field price-filter-field"><span>{{ __('Maximum price (EGP)') }}</span><input type="number" min="0" step="0.01" inputmode="decimal" aria-label="{{ __('Maximum price (EGP)') }}" placeholder="{{ __('Maximum price (EGP)') }}" wire:model.live.debounce.350ms="maxPrice"></label>
        @if ($minPrice !== '' || $maxPrice !== '')
            <button type="button" class="button small secondary" wire:click="clearPriceRange">{{ __('Clear price filter') }}</button>
        @endif
        </div>
    </div>
    @if ($invalidPriceRange)
        <p class="alert" role="alert">{{ __('Minimum price cannot be greater than maximum.') }}</p>
    @endif
    <nav class="category-filters" aria-label="{{ __('Categories') }}">
        <a class="button small secondary" href="{{ route('shop') }}" wire:navigate>{{ __('All products') }}</a>
        @foreach ($categories as $category)
            <a class="button small secondary" href="{{ route('category.show', $category->slug) }}" wire:navigate>{{ app()->isLocale('ar') ? ($category->name_ar ?: $category->name) : $category->name }}</a>
        @endforeach
    </nav>
    <div class="product-grid">
        @forelse ($products as $product)
            <div><x-storefront.product-card :product="$product" :priority="$loop->first" />
                <button class="button small" style="width:100%;margin-top:8px" data-offline-add-to-cart="{{ $product->id }}" x-on:click="window.captureCartOrigin($el)" wire:click="addToCart({{ $product->id }})" @disabled($trackStock && $product->stock < 1)>{{ $trackStock && $product->stock < 1 ? __('Out of stock') : __('Add to cart') }}</button>
            </div>
        @empty
            <p class="empty-state">{{ __('No products found.') }}</p>
        @endforelse
    </div>
    <div style="margin-top:20px">{{ $products->links() }}</div>
</section>
