<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Tableware', 'name_ar' => 'أدوات المائدة', 'slug' => 'tableware', 'sort_order' => 1],
            ['name' => 'Kitchen', 'name_ar' => 'المطبخ', 'slug' => 'kitchen', 'sort_order' => 2],
            ['name' => 'Home Decor', 'name_ar' => 'ديكور المنزل', 'slug' => 'home-decor', 'sort_order' => 3],
            ['name' => 'Textiles', 'name_ar' => 'المنسوجات', 'slug' => 'textiles', 'sort_order' => 4],
            ['name' => 'Storage', 'name_ar' => 'التخزين والتنظيم', 'slug' => 'storage', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(['slug' => $category['slug']], $category + ['is_active' => true]);
        }
    }
}
