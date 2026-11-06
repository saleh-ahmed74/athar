<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::query()->firstOrCreate([], [
            'store_name' => 'ATHAR',
            'logo_url' => null,
            'currency_symbol' => 'EGP',
            'payment_label_en' => 'Payment is collected in cash when your order arrives.',
            'payment_label_ar' => 'يتم تحصيل قيمة الطلب نقدًا عند الاستلام.',
            'show_announcement' => true,
            'track_stock' => false,
            'order_confirmation_en' => 'Your request has been received. Our customer service team will contact you to confirm availability and delivery details.',
            'order_confirmation_ar' => 'استلمنا طلبك، وسيتواصل معك فريق خدمة العملاء لتأكيد توفر المنتجات وتفاصيل التوصيل.',
            'announcement_en' => 'Thoughtful things for everyday living',
            'announcement_ar' => 'تفاصيل جميلة لحياة يومية أهدأ',
            'tagline_en' => 'Follow us on Instagram, TikTok and Facebook for new arrivals and offers, then come back and order again!',
            'tagline_ar' => 'تابعنا على إنستجرام وتيك توك وفيسبوك لتصلك أحدث المنتجات والعروض، وارجع اطلب تاني!',
            'seo_description_en' => 'Thoughtful pieces for your home and kitchen.',
            'seo_description_ar' => 'قطع مختارة لبيتك ومطبخك.',
            'hero_eyebrow_en' => 'ATHAR · HOME & KITCHEN',
            'hero_eyebrow_ar' => 'أثَر · للبيت والمطبخ',
            'hero_title_en' => 'Discover the little things that make a home feel special.',
            'hero_title_ar' => 'اكتشف التفاصيل الصغيرة التي تجعل البيت مميزًا.',
            'hero_text_en' => 'Thoughtful pieces for your home and kitchen.',
            'hero_text_ar' => 'قطع مختارة لبيتك ومطبخك.',
            'hero_cta_en' => 'Shop the collection',
            'hero_cta_ar' => 'تسوق المجموعة',
            'categories_heading_en' => 'Browse by category',
            'categories_heading_ar' => 'تسوق حسب الفئة',
            'featured_heading_en' => 'Featured pieces',
            'featured_heading_ar' => 'اختياراتنا',
            'shipping_fee' => 0,
            'instagram_url' => 'https://www.instagram.com/salehmoghazy?srtk=eGo1MWluYWk0a3Fj',
            'tiktok_url' => 'https://www.tiktok.com/@salehmoghazy?_r=1&_t=ZS-9ArndaE83Zl',
            'facebook_url' => 'https://www.facebook.com/share/1JNiaUrLQj/',
            'whatsapp_url' => 'https://wa.me/201146677219',
        ]);

        Cache::forget('store-settings.current');
    }
}
