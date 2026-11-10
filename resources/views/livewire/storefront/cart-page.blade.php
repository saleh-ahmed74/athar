<section>
    <h1 class="page-title">{{ __('Your cart') }}</h1>
    @if (session('status'))<p class="status-message">{{ session('status') }}</p>@endif
    @if (count($lines))
        <div class="cart-layout" style="margin-top:18px">
            <div class="cart-box">
                @foreach ($lines as $line)
                    @php($product = $line['product'])
                    <div class="cart-line" data-offline-cart-line="{{ $product->id }}">
                        @if ($product->image_path)<img class="cart-thumb" src="{{ '/storage/'.ltrim($product->image_path, '/') }}" alt="">@else<div class="cart-thumb image-placeholder" style="display:grid;place-items:center">A</div>@endif
                        <div><a class="product-name" href="{{ route('product.show', $product->slug) }}" wire:navigate>{{ app()->isLocale('ar') ? ($product->name_ar ?: $product->name) : $product->name }}</a><div class="note">{{ number_format((float) $product->price, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</div></div>
                        <div class="quantity-control"><button aria-label="{{ __('Decrease quantity') }}" data-offline-cart-action="decrease" data-product-id="{{ $product->id }}" wire:click="updateQuantity({{ $product->id }}, {{ $line['quantity'] - 1 }})">−</button><span data-offline-cart-quantity>{{ $line['quantity'] }}</span><button aria-label="{{ __('Increase quantity') }}" data-offline-cart-action="increase" data-product-id="{{ $product->id }}" wire:click="updateQuantity({{ $product->id }}, {{ $line['quantity'] + 1 }})">+</button></div>
                        <button class="danger-button line-remove" data-offline-cart-action="remove" data-product-id="{{ $product->id }}" wire:click="remove({{ $product->id }})">{{ __('Remove') }}</button>
                    </div>
                @endforeach
            </div>
            <aside class="cart-total"><div><span>{{ __('Subtotal') }}</span><strong>{{ $subtotal }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong></div><a class="button" href="{{ route('checkout') }}" wire:navigate>{{ __('Proceed to checkout') }}</a><a class="button secondary" href="{{ route('shop') }}" wire:navigate>{{ __('Continue shopping') }}</a></aside>
        </div>
    @else
        <div class="empty-state"><h2>{{ __('Your cart is empty.') }}</h2><a class="button" href="{{ route('shop') }}" wire:navigate>{{ __('Continue shopping') }}</a></div>
    @endif
</section>

