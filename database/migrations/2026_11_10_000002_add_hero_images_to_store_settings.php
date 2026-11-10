<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('store_settings', 'hero_images')) {
            Schema::table('store_settings', function (Blueprint $table): void {
                $table->json('hero_images')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('store_settings', 'hero_images')) {
            Schema::table('store_settings', function (Blueprint $table): void {
                $table->dropColumn('hero_images');
            });
        }
    }
};
