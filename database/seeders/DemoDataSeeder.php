<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    private const CATEGORIES = [
        ['slug' => 'cookware', 'name' => 'Cookware', 'name_ar' => 'أواني الطهي'],
        ['slug' => 'dinnerware', 'name' => 'Dinnerware', 'name_ar' => 'أطقم السفرة'],
        ['slug' => 'storage', 'name' => 'Storage', 'name_ar' => 'التخزين'],
        ['slug' => 'utensils', 'name' => 'Utensils', 'name_ar' => 'أدوات المطبخ'],
        ['slug' => 'glassware', 'name' => 'Glassware', 'name_ar' => 'الزجاجيات'],
        ['slug' => 'appliances', 'name' => 'Appliances', 'name_ar' => 'الأجهزة'],
    ];

    private const PRODUCTS = [
        ['Enamel pot 24cm', 'cookware', 1450, 1800, 'حلة مينا ٢٤ سم'],
        ['Non-stick pan set', 'cookware', 2100, 0, 'طقم مقالي غير لاصقة'],
        ['Cast iron skillet', 'cookware', 1650, 0, 'مقلاة حديد زهر'],
        ['Steamer pot', 'cookware', 980, 1200, 'حلة بخار'],
        ['Porcelain plate set (12)', 'dinnerware', 1950, 2400, 'طقم أطباق بورسلين (١٢ قطعة)'],
        ['Soup bowls (6)', 'dinnerware', 640, 0, 'أطباق شوربة (٦ قطع)'],
        ['Serving platter', 'dinnerware', 450, 0, 'طبق تقديم'],
        ['Mug set (6)', 'dinnerware', 520, 0, 'طقم أكواب (٦ قطع)'],
        ['Airtight jar set (5)', 'storage', 520, 0, 'طقم برطمانات محكمة الغلق (٥ قطع)'],
        ['Spice rack', 'storage', 780, 950, 'حامل توابل'],
        ['Bread box', 'storage', 690, 0, 'علبة حفظ العيش'],
        ['Lunch box', 'storage', 310, 0, 'علبة غداء'],
        ['Silicone utensil set', 'utensils', 360, 0, 'طقم أدوات سيليكون'],
        ['Chef knife 20cm', 'utensils', 890, 0, 'سكين طاهٍ ٢٠ سم'],
        ['Cutting board', 'utensils', 420, 0, 'لوح تقطيع'],
        ['Whisk and spatula', 'utensils', 190, 0, 'مضرب وسباتولا'],
        ['Tea glasses (6)', 'glassware', 280, 0, 'أكواب شاي (٦ قطع)'],
        ['Water jug 1.5L', 'glassware', 330, 420, 'إبريق مياه ١٫٥ لتر'],
        ['Wine glasses (4)', 'glassware', 460, 0, 'كاسات (٤ قطع)'],
        ['Glass pitcher', 'glassware', 380, 0, 'إبريق زجاج'],
        ['Electric kettle', 'appliances', 1250, 0, 'غلاية كهربائية'],
        ['Air fryer 5L', 'appliances', 3900, 4500, 'قلاية هوائية ٥ لتر'],
        ['Hand blender', 'appliances', 1100, 0, 'خلاط يدوي'],
        ['Toaster', 'appliances', 950, 0, 'محمصة خبز'],
        ['Stock pot 30cm', 'cookware', 1750, 0, 'حلة طبخ كبيرة ٣٠ سم'],
        ['Grill pan', 'cookware', 1150, 1400, 'مقلاة جريل'],
        ['Milk pan', 'cookware', 420, 0, 'حلة لبن صغيرة'],
        ['Casserole dish', 'cookware', 1380, 0, 'طبق فرن (كاسرول)'],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data can only be seeded in local or testing environments.');
        }

        DB::transaction(function (): void {
            $categories = $this->seedCategories();
            $products = $this->seedProducts($categories);
            $this->seedSettings();
            $this->seedAdmin();
            $this->seedOrders($products);
            $this->seedMessage();
        });

        Cache::forget('store-settings.current');
    }

    /** @return array<string, Category> */
    private function seedCategories(): array
    {
        $categories = [];
        foreach (self::CATEGORIES as $index => $data) {
            $imagePath = "demo/categories/{$data['slug']}.svg";
            Storage::disk('public')->put($imagePath, $this->productArtwork($data['slug'], $index, 0));
            $categories[$data['slug']] = Category::query()->updateOrCreate(['slug' => $data['slug']], [
                ...$data,
                'image_path' => $imagePath,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        return $categories;
    }

    /** @param array<string, Category> $categories
     * @return array<string, Product>
     */
    private function seedProducts(array $categories): array
    {
        $products = [];
        foreach (self::PRODUCTS as $index => [$name, $categorySlug, $price, $compareAtPrice, $nameAr]) {
            $slug = str($name)->slug()->toString();
            $imagePaths = [];
            for ($view = 0; $view < 3; $view++) {
                $path = "demo/products/{$slug}-".($view + 1).'.svg';
                Storage::disk('public')->put($path, $this->productArtwork($categorySlug, $index, $view));
                $imagePaths[] = $path;
            }

            $products[$slug] = Product::query()->updateOrCreate(['slug' => $slug], [
                'category_id' => $categories[$categorySlug]->id,
                'name' => $name,
                'name_ar' => $nameAr,
                'sku' => sprintf('ATH-DEMO-%03d', $index + 1),
                'description' => 'Durable, easy to clean and made for everyday use. Replace with the real product description.',
                'description_ar' => 'متين وسهل التنظيف ومصمم للاستخدام اليومي. أضف وصف المنتج الحقيقي قبل الإطلاق.',
                'price' => $price,
                'compare_at_price' => $compareAtPrice ?: null,
                'stock' => 0,
                'image_path' => $imagePaths[0],
                'gallery_paths' => array_slice($imagePaths, 1),
                'is_active' => true,
                'is_featured' => $index < 8,
            ]);
        }

        return $products;
    }

    private function seedSettings(): void
    {
        $zones = [
            ['Cairo', 'القاهرة', 60],
            ['Giza', 'الجيزة', 60],
            ['Alexandria', 'الإسكندرية', 90],
            ['Other governorates', 'محافظات أخرى', 110],
        ];
        $shippingZones = [];
        foreach ($zones as $index => [$nameEn, $nameAr, $fee]) {
            $shippingZones[] = ['id' => sprintf('10000000-0000-4000-8000-%012d', $index + 1), 'name_en' => $nameEn, 'name_ar' => $nameAr, 'fee' => $fee];
        }

        $policySections = [
            ['Return and exchange', 'Items can be returned or exchanged within 14 days of delivery if unused and in original packaging. Items used for food or liquids cannot be returned unless there is a confirmed defect.', 'الاسترجاع والاستبدال', 'يمكن استرجاع أو استبدال المنتجات خلال ١٤ يوماً من تاريخ الاستلام بشرط أن تكون غير مستخدمة وفي عبوتها الأصلية. لا يمكن إرجاع المنتجات التي استُخدمت مع الطعام أو السوائل إلا في حالة وجود عيب تصنيع مؤكد.'],
            ['Damaged items', 'Check your order on arrival. Report damage within 24 hours with photos or video for a free replacement.', 'المنتجات التالفة', 'يرجى فحص طلبك فور الاستلام. أبلغنا عن أي كسر أو تلف خلال ٢٤ ساعة مع صور أو فيديو لنستبدل المنتج مجاناً.'],
            ['Shipping', 'Delivery across Egypt. The fee depends on your governorate and is shown at checkout.', 'الشحن', 'نوصل لجميع المحافظات. تختلف رسوم الشحن حسب المحافظة وتظهر عند إتمام الطلب.'],
            ['Refunds', 'Approved refunds are processed in 7 to 10 business days.', 'الاسترداد', 'تتم معالجة المبالغ المستردة المعتمدة خلال ٧ إلى ١٠ أيام عمل.'],
        ];
        $policySections = array_map(fn (array $section, int $index): array => [
            'id' => sprintf('20000000-0000-4000-8000-%012d', $index + 1),
            'title_en' => $section[0], 'body_en' => $section[1], 'title_ar' => $section[2], 'body_ar' => $section[3],
        ], $policySections, array_keys($policySections));

        StoreSetting::query()->updateOrCreate([], [
            'store_name' => 'ATHAR',
            'currency_symbol' => 'EGP',
            'show_announcement' => true,
            'track_stock' => false,
            'announcement_en' => 'Cookware, dinnerware and storage for everyday life',
            'announcement_ar' => 'أواني طهي وأطقم سفرة وحافظات لبيتك كل يوم',
            'tagline_en' => 'Follow us for new arrivals and offers, then come back and order again!',
            'tagline_ar' => 'تابعنا لتصلك أحدث المنتجات والعروض، وارجع اطلب تاني!',
            'seo_description_en' => 'Cookware, dinnerware, storage and small appliances delivered across Egypt with cash on delivery.',
            'seo_description_ar' => 'أواني طهي وأطقم سفرة وحافظات وأجهزة صغيرة تصلك في كل أنحاء مصر مع الدفع عند الاستلام.',
            'hero_eyebrow_en' => 'ATHAR · HOME & KITCHEN',
            'hero_eyebrow_ar' => 'أثَر · للبيت والمطبخ',
            'hero_title_en' => 'Make your kitchen feel like home',
            'hero_title_ar' => 'اجعل مطبخك يشعرك بالبيت',
            'hero_text_en' => 'Cookware, dinnerware and storage, delivered across Egypt with cash on delivery.',
            'hero_text_ar' => 'أواني طهي وأطقم سفرة وحافظات تصلك في كل أنحاء مصر مع الدفع عند الاستلام.',
            'hero_cta_en' => 'Shop the collection',
            'hero_cta_ar' => 'تسوق المجموعة',
            'categories_heading_en' => 'Shop by category',
            'categories_heading_ar' => 'تسوق حسب الفئة',
            'featured_heading_en' => 'Featured products',
            'featured_heading_ar' => 'منتجات مميزة',
            'payment_label_en' => 'Cash on delivery.',
            'payment_label_ar' => 'الدفع نقداً عند الاستلام.',
            'order_confirmation_en' => 'We will call you to confirm your order before shipping. You pay cash on delivery.',
            'order_confirmation_ar' => 'سنتصل بك لتأكيد طلبك قبل الشحن. الدفع نقداً عند الاستلام.',
            'support_phone' => '01146677219',
            'logo_url' => 'site/athar-logo.png',
            'whatsapp_url' => 'https://wa.me/201146677219',
            'facebook_url' => 'https://www.facebook.com/share/1JNiaUrLQj/',
            'instagram_url' => 'https://www.instagram.com/salehmoghazy?srtk=eGo1MWluYWk0a3Fj',
            'tiktok_url' => 'https://www.tiktok.com/@salehmoghazy?_r=1&_t=ZS-9ArndaE83Zl',
            'shipping_fee' => 0,
            'shipping_zones' => $shippingZones,
            'about_en' => 'We started with one idea: a well-equipped kitchen should not cost a fortune. Today we supply cookware, dinnerware, storage and small appliances to homes across Egypt.',
            'about_ar' => 'بدأنا بفكرة واحدة: المطبخ المجهز جيداً لا يجب أن يكلف ثروة. واليوم نوفر أواني الطهي وأطقم السفرة والحافظات والأجهزة الصغيرة لبيوت في كل أنحاء مصر.',
            'policy_sections' => $policySections,
        ]);
    }

    private function seedAdmin(): void
    {
        $admin = User::query()->firstOrNew(['email' => 'admin@athar.com']);
        $admin->name = 'Store admin';
        $admin->password = Hash::make('admin123');
        $admin->forceFill(['role' => 'admin'])->save();
    }

    /** @param array<string, Product> $products */
    private function seedOrders(array $products): void
    {
        $orders = [
            ['1004', 'Mona Hassan', '01000000001', 'Cairo', 'enamel-pot-24cm', 60, 'pending'],
            ['1003', 'Omar Ali', '01000000002', 'Giza', 'air-fryer-5l', 60, 'confirmed'],
            ['1002', 'Sara Adel', '01000000003', 'Alexandria', 'spice-rack', 90, 'shipped'],
            ['1001', 'Hany Samir', '01000000004', 'Cairo', 'soup-bowls-6', 60, 'delivered'],
        ];

        foreach ($orders as [$number, $name, $phone, $city, $slug, $shippingFee, $status]) {
            $product = $products[$slug];
            $order = Order::query()->updateOrCreate(['order_number' => 'ATH-DEMO-'.$number], [
                'checkout_token' => 'demo-checkout-'.$number,
                'customer_name' => $name,
                'phone' => $phone,
                'city' => $city,
                'address' => '12 Demo Street, '.$city,
                'subtotal' => $product->price,
                'shipping_fee' => $shippingFee,
                'total' => (float) $product->price + $shippingFee,
                'status' => $status,
                'payment_method' => 'cod',
                'payment_status' => 'unpaid',
                'inventory_tracked' => false,
            ]);

            OrderItem::query()->updateOrCreate(['order_id' => $order->id, 'product_id' => $product->id], [
                'product_name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => $product->price,
                'quantity' => 1,
                'line_total' => $product->price,
            ]);
        }
    }

    private function seedMessage(): void
    {
        ContactMessage::query()->updateOrCreate([
            'email' => 'nour.demo@example.com',
            'subject' => 'Product question',
        ], [
            'name' => 'Nour Ahmed',
            'phone' => '01000000009',
            'message' => 'Do you have the air fryer in black? And how long does delivery to Alexandria take?',
            'read_at' => null,
        ]);
    }

    private function productArtwork(string $category, int $index, int $view): string
    {
        $colors = ['#8a5a3b', '#4f7a6b', '#b5654a', '#4d6a8c', '#c79a2b', '#7b5a9c'];
        $backgrounds = ['#f3e2c4', '#d5e5da', '#f0d5c8', '#d3dfec', '#f6e8b4', '#e2d6f0'];
        $color = $colors[$index % count($colors)];
        $accent = $colors[($index + 2) % count($colors)];
        $bg = $backgrounds[($index + $view) % count($backgrounds)];
        $art = match ($category) {
            'cookware' => '<rect x="52" y="88" width="96" height="62" rx="10" fill="'.$color.'"/><rect x="36" y="98" width="20" height="11" rx="5" fill="'.$color.'"/><rect x="144" y="98" width="20" height="11" rx="5" fill="'.$color.'"/><rect x="46" y="76" width="108" height="18" rx="9" fill="'.$color.'"/><rect x="92" y="62" width="16" height="14" rx="6" fill="#2b1d14"/>',
            'dinnerware' => '<circle cx="100" cy="95" r="66" fill="'.$color.'"/><circle cx="100" cy="95" r="48" fill="#fff"/><circle cx="100" cy="95" r="34" fill="'.$accent.'" opacity=".35"/>',
            'storage' => '<rect x="60" y="58" width="80" height="16" rx="6" fill="#2b1d14"/><rect x="54" y="74" width="92" height="82" rx="14" fill="'.$color.'"/><rect x="66" y="94" width="68" height="34" rx="6" fill="#fff"/>',
            'utensils' => '<g transform="rotate(-25 100 95)"><ellipse cx="100" cy="46" rx="18" ry="28" fill="'.$color.'"/><rect x="94" y="70" width="12" height="88" rx="6" fill="'.$color.'"/></g><g transform="rotate(25 100 95)"><rect x="84" y="24" width="32" height="34" rx="5" fill="#2b1d14"/><rect x="94" y="54" width="12" height="104" rx="6" fill="#2b1d14"/></g>',
            'glassware' => '<path d="M60 44h80l-9 108a11 11 0 0 1-11 10H80a11 11 0 0 1-11-10z" fill="'.$color.'"/><path d="M66 78h68l-5 72H71z" fill="#fff" opacity=".75"/>',
            default => '<path d="M58 152V98a42 42 0 0 1 84 0v54z" fill="'.$color.'"/><path d="M142 114l30-24" stroke="'.$color.'" stroke-width="12" stroke-linecap="round"/><path d="M60 72a42 42 0 0 1 80 0" stroke="#2b1d14" stroke-width="9" fill="none" stroke-linecap="round"/><rect x="52" y="152" width="96" height="12" rx="6" fill="#2b1d14"/>',
        };

        if ($view === 1) {
            $art = '<g transform="translate(-30 -26) scale(1.3)">'.$art.'</g>';
        } elseif ($view === 2) {
            $art = '<g transform="translate(-8 20) scale(.72)">'.$art.'</g><g transform="translate(80 40) scale(.62)" opacity=".75">'.$art.'</g>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><rect width="200" height="200" fill="'.$bg.'"/><rect y="160" width="200" height="40" fill="#0000000d"/>'.$art.'</svg>';
    }
}
