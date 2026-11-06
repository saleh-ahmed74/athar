<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 100, 2000);
        $quantity = fake()->numberBetween(1, 4);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'sku' => fake()->bothify('ATH-####??'),
            'unit_price' => $price,
            'quantity' => $quantity,
            'line_total' => number_format($price * $quantity, 2, '.', ''),
        ];
    }
}
