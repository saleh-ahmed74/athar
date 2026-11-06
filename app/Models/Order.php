<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['order_number', 'user_id', 'customer_name', 'email', 'phone', 'city', 'address', 'notes', 'subtotal', 'shipping_fee', 'total', 'status', 'return_reason', 'payment_method', 'payment_status', 'checkout_token', 'inventory_tracked'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'shipping_fee' => 'decimal:2', 'total' => 'decimal:2', 'inventory_tracked' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
