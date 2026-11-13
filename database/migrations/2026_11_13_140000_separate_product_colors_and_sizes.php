<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->json('colors')->nullable();
            $table->json('sizes')->nullable();
        });

        DB::table('products')->select(['id', 'variants'])->orderBy('id')->chunkById(100, function ($products): void {
            foreach ($products as $product) {
                $variants = json_decode($product->variants ?? '[]', true) ?: [];
                $colors = [];
                $sizes = [];

                foreach ($variants as $variant) {
                    $colorEnglish = trim((string) ($variant['color_en'] ?? ''));
                    $colorArabic = trim((string) ($variant['color_ar'] ?? ''));
                    if ($colorEnglish !== '' || $colorArabic !== '') {
                        $colorEnglish = $colorEnglish ?: $colorArabic;
                        $colorArabic = $colorArabic ?: $colorEnglish;
                        $key = mb_strtolower($colorEnglish.'|'.$colorArabic);
                        $colors[$key] ??= [
                            'id' => (string) Str::uuid(),
                            'color_en' => $colorEnglish,
                            'color_ar' => $colorArabic,
                            'color_hex' => $variant['color_hex'] ?? '#000000',
                            'image_path' => $variant['image_path'] ?? null,
                        ];
                    }

                    $sizeEnglish = trim((string) ($variant['size_en'] ?? ''));
                    $sizeArabic = trim((string) ($variant['size_ar'] ?? ''));
                    if ($sizeEnglish !== '' || $sizeArabic !== '') {
                        $sizeEnglish = $sizeEnglish ?: $sizeArabic;
                        $sizeArabic = $sizeArabic ?: $sizeEnglish;
                        $key = mb_strtolower($sizeEnglish.'|'.$sizeArabic);
                        $sizes[$key] ??= [
                            'id' => (string) Str::uuid(),
                            'size_en' => $sizeEnglish,
                            'size_ar' => $sizeArabic,
                        ];
                    }
                }

                DB::table('products')->where('id', $product->id)->update([
                    'colors' => $colors === [] ? null : json_encode(array_values($colors), JSON_UNESCAPED_UNICODE),
                    'sizes' => $sizes === [] ? null : json_encode(array_values($sizes), JSON_UNESCAPED_UNICODE),
                ]);
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('variants');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->json('variants')->nullable();
        });

        DB::table('products')->select(['id', 'colors', 'sizes'])->orderBy('id')->chunkById(100, function ($products): void {
            foreach ($products as $product) {
                $colors = json_decode($product->colors ?? '[]', true) ?: [];
                $sizes = json_decode($product->sizes ?? '[]', true) ?: [];
                $colorOptions = $colors ?: [null];
                $sizeOptions = $sizes ?: [null];
                $variants = [];

                foreach ($colorOptions as $color) {
                    foreach ($sizeOptions as $size) {
                        $variants[] = [
                            'id' => (string) Str::uuid(),
                            'color_en' => $color['color_en'] ?? null,
                            'color_ar' => $color['color_ar'] ?? null,
                            'color_hex' => $color['color_hex'] ?? null,
                            'image_path' => $color['image_path'] ?? null,
                            'size_en' => $size['size_en'] ?? null,
                            'size_ar' => $size['size_ar'] ?? null,
                        ];
                    }
                }

                DB::table('products')->where('id', $product->id)->update([
                    'variants' => $variants === [] ? null : json_encode($variants, JSON_UNESCAPED_UNICODE),
                ]);
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['colors', 'sizes']);
        });
    }
};
