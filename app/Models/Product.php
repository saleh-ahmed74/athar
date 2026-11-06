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
    protected $fillable = ['category_id', 'name', 'name_ar', 'slug', 'sku', 'short_description', 'short_description_ar', 'description', 'description_ar', 'price', 'compare_at_price', 'stock', 'image_path', 'gallery_paths', 'is_active', 'is_featured', 'archived_at', 'archived_with_category'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'compare_at_price' => 'decimal:2', 'stock' => 'integer', 'gallery_paths' => 'array', 'is_active' => 'boolean', 'is_featured' => 'boolean', 'archived_at' => 'datetime', 'archived_with_category' => 'boolean'];
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
