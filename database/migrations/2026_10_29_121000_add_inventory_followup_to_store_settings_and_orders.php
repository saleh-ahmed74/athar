<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->boolean('track_stock')->default(false);
            $table->text('order_confirmation_ar')->nullable();
            $table->text('order_confirmation_en')->nullable();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->boolean('inventory_tracked')->default(false);
        });

        DB::table('store_settings')->update([
            'track_stock' => false,
            'order_confirmation_en' => 'Your request has been received. Our customer service team will contact you to confirm availability and delivery details.',
            'order_confirmation_ar' => 'استلمنا طلبك، وسيتواصل معك فريق خدمة العملاء لتأكيد توفر المنتجات وتفاصيل التوصيل.',
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('inventory_tracked');
        });

        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn(['track_stock', 'order_confirmation_ar', 'order_confirmation_en']);
        });
    }
};
