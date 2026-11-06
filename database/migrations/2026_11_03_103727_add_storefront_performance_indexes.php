<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index('price', 'products_price_index');
            $table->index(['is_active', 'archived_at', 'created_at'], 'products_storefront_order_index');
            $table->index(['category_id', 'is_active', 'archived_at', 'created_at'], 'products_category_listing_index');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->index(['is_active', 'archived_at', 'sort_order'], 'categories_storefront_order_index');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index('created_at', 'orders_created_at_index');
        });

        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->index('created_at', 'contact_messages_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->dropIndex('contact_messages_created_at_index');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_created_at_index');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex('categories_storefront_order_index');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_price_index');
            $table->dropIndex('products_storefront_order_index');
            $table->dropIndex('products_category_listing_index');
        });
    }
};
