<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->randomFloat(2, 100, 5000);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'name_ar' => fake()->optional()->words(3, true),
            'slug' => Str::slug($name),
            'sku' => Str::upper(fake()->unique()->bothify('ATH-####??')),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'price' => $price,
            'compare_at_price' => null,
            'stock' => fake()->numberBetween(0, 50),
            'is_active' => true,
            'is_featured' => false,
        ];
    }
}
