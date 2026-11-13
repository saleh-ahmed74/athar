<?php

namespace App\Livewire\Storefront;

use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ProductPage extends Component
{
    public Product $product;

    public int $quantity = 1;

    public int $activeImageIndex = 0;

    public string $selectedColor = '';

    /** @var array<string, int|string> */
    public array $variantQuantities = [];

    public function mount(Product $product): void
    {
        $this->product = Product::query()->where('is_active', true)->whereNull('archived_at')->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))->findOrFail($product->id);
        $this->variantQuantities = $this->emptyVariantQuantities();
    }

    public function addToCart(): void
    {
        $this->product = Product::query()
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))
            ->findOrFail($this->product->id);
        $trackStock = (bool) StoreSetting::current()->track_stock;
        abort_if($trackStock && $this->product->stock < 1, 409);
        $cart = session('cart', []);
        if ($this->product->hasOptions()) {
            $quantities = collect($this->variantQuantities)->map(fn ($quantity): int => max(0, (int) $quantity))->filter(fn (int $quantity): bool => $quantity > 0);
            if ($quantities->isEmpty() || $quantities->sum() > 10) {
                throw ValidationException::withMessages(['variantQuantities' => __('Choose options with a total quantity between 1 and 10.')]);
            }
            if ($trackStock && collect($cart)->filter(fn ($quantity, $key): bool => str_starts_with((string) $key, $this->product->id.':'))->sum() + $quantities->sum() > $this->product->stock) {
                throw ValidationException::withMessages(['variantQuantities' => __('The selected quantities exceed available stock.')]);
            }
            foreach ($quantities as $combinationKey => $quantity) {
                [$colorId, $sizeId] = explode('__', (string) $combinationKey, 2);
                $colorId = $colorId === 'none' ? null : $colorId;
                $sizeId = $sizeId === 'none' ? null : $sizeId;
                abort_unless($this->product->optionCombination($colorId, $sizeId), 404);
                $cartKey = $this->product->id.':'.($colorId ?? '-').':'.($sizeId ?? '-');
                $cart[$cartKey] = min((int) ($cart[$cartKey] ?? 0) + $quantity, 10);
            }
        } else {
            $maximum = min(10, $trackStock ? $this->product->stock : 99);
            $quantity = max(1, min((int) $this->quantity, $maximum));
            $cart[(string) $this->product->id] = min((int) ($cart[$this->product->id] ?? 0) + $quantity, $maximum);
        }
        session()->put('cart', $cart);
        $this->dispatch('cart-updated', count: (int) collect($cart)->sum(), cart: $cart);
        $this->quantity = 1;
        $this->variantQuantities = $this->emptyVariantQuantities();
    }

    public function updatedQuantity(int $quantity): void
    {
        $maximum = StoreSetting::current()->track_stock ? max(1, $this->product->stock) : 99;
        $this->quantity = max(1, min($quantity, $maximum));
    }

    public function updatedSelectedColor(): void
    {
        $this->activeImageIndex = 0;
    }

    /** @return array<string, mixed>|null */
    private function imageVariant(): ?array
    {
        return collect($this->product->colors ?? [])->firstWhere('id', $this->selectedColor);
    }

    /** @return array<string, int> */
    private function emptyVariantQuantities(): array
    {
        if (! $this->product->hasOptions()) {
            return [];
        }

        $colors = $this->product->colors ?: [['id' => 'none']];
        $sizes = $this->product->sizes ?: [['id' => 'none']];
        $quantities = [];

        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                $quantities[$color['id'].'__'.$size['id']] = 0;
            }
        }

        return $quantities;
    }

    public function selectImage(int $index): void
    {
        $imageCount = count($this->productImages($this->imageVariant()));
        abort_unless($index >= 0 && $index < $imageCount, 404);
        $this->activeImageIndex = $index;
    }

    public function previousImage(): void
    {
        $imageCount = count($this->productImages($this->imageVariant()));
        if ($imageCount > 1) {
            $this->activeImageIndex = ($this->activeImageIndex - 1 + $imageCount) % $imageCount;
        }
    }

    public function nextImage(): void
    {
        $imageCount = count($this->productImages($this->imageVariant()));
        if ($imageCount > 1) {
            $this->activeImageIndex = ($this->activeImageIndex + 1) % $imageCount;
        }
    }

    public function render(): View
    {
        $colors = $this->product->colors ?? [];
        $sizes = $this->product->sizes ?? [];
        $trackStock = (bool) StoreSetting::current()->track_stock;
        $cartQuantity = collect(session('cart', []))
            ->filter(fn ($quantity, $key): bool => str_starts_with((string) $key, $this->product->id.':'))
            ->sum();
        $combinations = [];
        if ($colors !== [] || $sizes !== []) {
            $colorDimensions = $colors ?: [['id' => 'none']];
            $sizeDimensions = $sizes ?: [['id' => 'none']];
            foreach ($colorDimensions as $color) {
                foreach ($sizeDimensions as $size) {
                    $combinations[] = [
                        'key' => ($color['id'] ?? 'none').'__'.($size['id'] ?? 'none'),
                        'color' => $color['id'] === 'none' ? null : $color,
                        'size' => $size['id'] === 'none' ? null : $size,
                    ];
                }
            }
        }

        return view('livewire.storefront.product-page', [
            'trackStock' => $trackStock,
            'maximumVariantQuantity' => min(10, $trackStock ? max(0, $this->product->stock - $cartQuantity) : 10),
            'productImages' => $this->productImages($this->imageVariant()),
            'activeImageIndex' => $this->activeImageIndex,
            'colors' => $colors,
            'sizes' => $sizes,
            'combinations' => $combinations,
            'requiresColor' => $colors !== [],
            'requiresSize' => $sizes !== [],
            'relatedProducts' => Product::query()->where('category_id', $this->product->category_id)->where('is_active', true)->whereNull('archived_at')->whereHas('category', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))->where('id', '<>', $this->product->id)->latest()->limit(4)->get(),
        ])->layout('layouts.storefront', ['title' => $this->product->name]);
    }

    /** @return list<string> */
    private function productImages(?array $variant = null): array
    {
        return array_values(array_filter(array_unique(array_merge([$variant['image_path'] ?? null, $this->product->image_path], $this->product->gallery_paths ?? []))));
    }
}
