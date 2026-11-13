<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['category_id', 'name', 'name_ar', 'slug', 'sku', 'short_description', 'short_description_ar', 'description', 'description_ar', 'price', 'compare_at_price', 'stock', 'image_path', 'gallery_paths', 'colors', 'sizes', 'is_active', 'is_featured', 'is_bestseller', 'archived_at', 'archived_with_category'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'compare_at_price' => 'decimal:2', 'stock' => 'integer', 'gallery_paths' => 'array', 'colors' => 'array', 'sizes' => 'array', 'is_active' => 'boolean', 'is_featured' => 'boolean', 'is_bestseller' => 'boolean', 'archived_at' => 'datetime', 'archived_with_category' => 'boolean'];
    }

    public function hasOptions(): bool
    {
        return ($this->colors ?? []) !== [] || ($this->sizes ?? []) !== [];
    }

    /** @return array<string, mixed>|null */
    public function optionCombination(?string $colorId, ?string $sizeId): ?array
    {
        $colors = $this->colors ?? [];
        $sizes = $this->sizes ?? [];
        $color = $colorId ? collect($colors)->firstWhere('id', $colorId) : null;
        $size = $sizeId ? collect($sizes)->firstWhere('id', $sizeId) : null;

        if (($colors !== [] && ! $color) || ($sizes !== [] && ! $size) || ($colors === [] && $colorId !== null) || ($sizes === [] && $sizeId !== null)) {
            return null;
        }

        return [
            'color_id' => $color['id'] ?? null,
            'color_en' => $color['color_en'] ?? null,
            'color_ar' => $color['color_ar'] ?? null,
            'color_hex' => $color['color_hex'] ?? null,
            'size_id' => $size['id'] ?? null,
            'size_en' => $size['size_en'] ?? null,
            'size_ar' => $size['size_ar'] ?? null,
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @param Builder<Product> $query */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_active', true)->whereNull('archived_at')->where('stock', '>', 0);
    }
}
