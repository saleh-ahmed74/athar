<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number' => 'ATH-'.fake()->unique()->bothify('##########'),
            'checkout_token' => fake()->unique()->uuid(),
            'user_id' => null,
            'customer_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '+2010'.fake()->numerify('########'),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'subtotal' => 500,
            'shipping_fee' => 70,
            'total' => 570,
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
        ];
    }
}
