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
            $table->string('store_name')->nullable();
            $table->string('logo_url', 2048)->nullable();
            $table->string('instagram_url', 2048)->nullable();
            $table->string('facebook_url', 2048)->nullable();
            $table->string('whatsapp_url', 2048)->nullable();
            $table->string('payment_label_ar')->nullable();
            $table->string('payment_label_en')->nullable();
            $table->string('currency_symbol', 12)->nullable();
            $table->boolean('show_announcement')->default(true);
            $table->string('announcement_ar')->nullable();
            $table->string('announcement_en')->nullable();
            $table->text('tagline_ar')->nullable();
            $table->text('tagline_en')->nullable();
            $table->string('seo_description_ar', 320)->nullable();
            $table->string('seo_description_en', 320)->nullable();
            $table->string('hero_eyebrow_ar')->nullable();
            $table->string('hero_eyebrow_en')->nullable();
            $table->string('hero_title_ar')->nullable();
            $table->string('hero_title_en')->nullable();
            $table->text('hero_text_ar')->nullable();
            $table->text('hero_text_en')->nullable();
            $table->string('hero_cta_ar')->nullable();
            $table->string('hero_cta_en')->nullable();
            $table->string('hero_image_url', 2048)->nullable();
            $table->string('categories_heading_ar')->nullable();
            $table->string('categories_heading_en')->nullable();
            $table->string('featured_heading_ar')->nullable();
            $table->string('featured_heading_en')->nullable();
        });

        foreach (DB::table('store_settings')->get() as $settings) {
            DB::table('store_settings')->where('id', $settings->id)->update([
                'store_name' => $settings->store_name ?: 'ATHAR',
                'currency_symbol' => $settings->currency_symbol ?: 'EGP',
                'announcement_en' => $settings->announcement_en ?: ($settings->announcement ?: 'Thoughtful things for everyday living'),
                'announcement_ar' => $settings->announcement_ar ?: 'تفاصيل جميلة لحياة يومية أهدأ',
                'tagline_en' => $settings->tagline_en ?: 'Made for a home that feels like yours.',
                'tagline_ar' => $settings->tagline_ar ?: 'اختيارات تجعل البيت أقرب إليك.',
                'seo_description_en' => $settings->seo_description_en ?: 'Thoughtful pieces for your home and kitchen.',
                'seo_description_ar' => $settings->seo_description_ar ?: 'قطع مختارة لبيتك ومطبخك.',
                'hero_eyebrow_en' => $settings->hero_eyebrow_en ?: 'ATHAR · HOME & KITCHEN',
                'hero_eyebrow_ar' => $settings->hero_eyebrow_ar ?: 'أثَر · للبيت والمطبخ',
                'hero_title_en' => $settings->hero_title_en ?: 'Discover the little things that make a home feel special.',
                'hero_title_ar' => $settings->hero_title_ar ?: 'اكتشف التفاصيل الصغيرة التي تجعل البيت مميزًا.',
                'hero_text_en' => $settings->hero_text_en ?: 'Thoughtful pieces for your home and kitchen.',
                'hero_text_ar' => $settings->hero_text_ar ?: 'قطع مختارة لبيتك ومطبخك.',
                'hero_cta_en' => $settings->hero_cta_en ?: 'Shop the collection',
                'hero_cta_ar' => $settings->hero_cta_ar ?: 'تسوق المجموعة',
                'categories_heading_en' => $settings->categories_heading_en ?: 'Browse by category',
                'categories_heading_ar' => $settings->categories_heading_ar ?: 'تسوق حسب الفئة',
                'featured_heading_en' => $settings->featured_heading_en ?: 'Featured pieces',
                'featured_heading_ar' => $settings->featured_heading_ar ?: 'اختياراتنا',
                'payment_label_en' => $settings->payment_label_en ?: 'Payment is collected in cash when your order arrives.',
                'payment_label_ar' => $settings->payment_label_ar ?: 'يتم تحصيل قيمة الطلب نقدًا عند الاستلام.',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'store_name', 'logo_url', 'instagram_url', 'facebook_url', 'whatsapp_url', 'payment_label_ar', 'payment_label_en', 'currency_symbol', 'show_announcement', 'announcement_ar', 'announcement_en',
                'tagline_ar', 'tagline_en', 'seo_description_ar', 'seo_description_en',
                'hero_eyebrow_ar', 'hero_eyebrow_en', 'hero_title_ar', 'hero_title_en',
                'hero_text_ar', 'hero_text_en', 'hero_cta_ar', 'hero_cta_en', 'hero_image_url',
                'categories_heading_ar', 'categories_heading_en', 'featured_heading_ar', 'featured_heading_en',
            ]);
        });
    }
};
