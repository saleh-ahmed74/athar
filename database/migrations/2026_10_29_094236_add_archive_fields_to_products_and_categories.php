<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->index();
            $table->boolean('archived_with_category')->default(false);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['archived_at', 'archived_with_category']);
        });
    }
};
