<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('announcement')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->text('about_en')->nullable();
            $table->text('about_ar')->nullable();
            $table->longText('policy_en')->nullable();
            $table->longText('policy_ar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
