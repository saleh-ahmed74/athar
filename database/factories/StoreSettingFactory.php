<?php

namespace Database\Factories;

use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StoreSetting> */
class StoreSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'announcement' => fake()->sentence(6),
            'support_email' => fake()->safeEmail(),
            'support_phone' => '+2010'.fake()->numerify('########'),
            'address' => fake()->address(),
            'shipping_fee' => 70,
            'about_en' => fake()->paragraph(),
            'about_ar' => fake()->paragraph(),
            'policy_en' => fake()->paragraphs(2, true),
            'policy_ar' => fake()->paragraphs(2, true),
        ];
    }
}
