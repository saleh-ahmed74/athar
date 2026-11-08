<?php

use App\Livewire\Account\OrderHistory;
use App\Livewire\Admin\Categories;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Messages as AdminMessages;
use App\Livewire\Admin\Orders as AdminOrders;
use App\Livewire\Admin\Products as AdminProducts;
use App\Livewire\Admin\StoreSettings as AdminStoreSettings;
use App\Livewire\Admin\WhatsAppTemplates as AdminWhatsAppTemplates;
use App\Livewire\Storefront\CartPage;
use App\Livewire\Storefront\CheckoutPage;
use App\Livewire\Storefront\HomePage;
use App\Livewire\Storefront\ProductPage;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('shop filters products by an inclusive price range and can clear it', function () {
    $below = Product::factory()->create(['name' => 'Below Price Range', 'price' => 99]);
    $inside = Product::factory()->create(['name' => 'Inside Price Range', 'price' => 250]);
    $above = Product::factory()->create(['name' => 'Above Price Range', 'price' => 501]);

    $this->get(route('shop', ['min_price' => 100, 'max_price' => 500]))
        ->assertOk()
        ->assertSee($inside->name)
        ->assertDontSee($below->name)
        ->assertDontSee($above->name)
        ->assertSee('Clear price filter');
});

test('guest can place an order without stock and inventory is not decremented', function () {
    Cache::flush();
    $product = Product::factory()->create(['stock' => 0]);
    StoreSetting::factory()->create(['track_stock' => false, 'shipping_fee' => 70]);
    $this->withSession(['cart' => [$product->id => 2], 'checkout_token' => (string) Str::uuid()]);

    Livewire::test(CheckoutPage::class)
        ->set('name', 'Test Customer')
        ->set('email', 'customer@example.com')
        ->set('phone', '01012345678')
        ->set('city', 'Cairo')
        ->set('address', '12 Test Street, Cairo')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->with('items')->sole();
    expect($order->inventory_tracked)->toBeFalse()
        ->and($order->status)->toBe('pending')
        ->and((int) $order->items->sole()->quantity)->toBe(2)
        ->and((int) $product->fresh()->stock)->toBe(0)
        ->and(session('cart'))->toBeNull();
});

test('checkout lists countries with dial codes and stores a validated international phone number', function () {
    Cache::flush();
    StoreSetting::factory()->create(['track_stock' => false, 'shipping_fee' => 25, 'shipping_zones' => []]);
    $product = Product::factory()->create();
    $this->withSession(['cart' => [$product->id => 1], 'checkout_token' => (string) Str::uuid()]);

    Livewire::test(CheckoutPage::class)
        ->assertSet('phoneCountry', 'EG')
        ->assertSee('Egypt (+20)')
        ->assertSee('United Kingdom (+44)')
        ->assertSet('city', 'Cairo')
        ->assertSee('Cairo')
        ->assertSee('Default')
        ->assertSee('Other city')
        ->set('name', 'International Customer')
        ->set('phoneCountry', 'GB')
        ->set('phone', '020 7946 0018')
        ->set('city', '__other__')
        ->set('customCity', 'London')
        ->set('address', '12 Long Street, London')
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect(Order::query()->sole()->phone)->toBe('+442079460018');
});

test('checkout defaults to the cairo shipping zone even if it is not first', function () {
    Cache::flush();
    $giza = ['id' => 'giza', 'name_en' => 'Giza', 'name_ar' => 'الجيزة', 'fee' => '35.00'];
    $cairo = ['id' => 'cairo', 'name_en' => 'Cairo', 'name_ar' => 'القاهرة', 'fee' => '60.00'];
    StoreSetting::factory()->create(['shipping_zones' => [$giza, $cairo], 'shipping_fee' => 70]);
    $product = Product::factory()->create();
    $this->withSession(['cart' => [$product->id => 1]]);

    Livewire::test(CheckoutPage::class)
        ->assertSet('city', 'cairo')
        ->assertSee('60.00')
        ->assertSee('Cairo')
        ->assertSee('Default')
        ->assertSee('Shipping fee for selected area');
});

test('home page adds an active product to the cart and rejects unavailable stock', function () {
    Cache::flush();
    StoreSetting::factory()->create(['track_stock' => false]);
    $product = Product::factory()->create(['stock' => 0]);

    Livewire::test(HomePage::class)
        ->assertSee('Add to cart')
        ->call('addToCart', $product->id)
        ->assertDispatched('cart-updated', count: 1);

    expect(session('cart'))->toBe([$product->id => 1]);

    StoreSetting::query()->firstOrFail()->forceFill(['track_stock' => true])->save();
    Cache::flush();
    app()->forgetInstance('store-settings.current.request');

    Livewire::test(HomePage::class)
        ->call('addToCart', $product->id)
        ->assertStatus(409);
});

test('cart quantity changes and removals dispatch the current count', function () {
    Cache::flush();
    $product = Product::factory()->create();
    StoreSetting::factory()->create(['track_stock' => false]);
    $this->withSession(['cart' => [$product->id => 1]]);

    Livewire::test(CartPage::class)
        ->call('updateQuantity', $product->id, 3)
        ->assertDispatched('cart-updated', count: 3)
        ->call('remove', $product->id)
        ->assertDispatched('cart-updated', count: 0);

    expect(session('cart'))->toBe([]);
});

test('browser cart recovery restores only active products and preserves a checkout token', function () {
    Cache::flush();
    $available = Product::factory()->create(['stock' => 3]);
    $unavailable = Product::factory()->create(['is_active' => false]);
    StoreSetting::factory()->create(['track_stock' => true]);
    $token = (string) Str::uuid();

    $this->postJson(route('cart.recover'), [
        'cart' => [$available->id => 8, $unavailable->id => 1, 999999 => 1],
        'checkout_token' => $token,
    ])->assertOk()->assertJsonPath('cart.'.$available->id, 3)
        ->assertJsonPath('count', 3)
        ->assertJsonPath('checkout_token', $token);

    expect(session('cart'))->toBe([$available->id => 3])
        ->and(session('checkout_token'))->toBe($token);
});

test('offline cart sync replaces the session cart with available products and stock limits', function () {
    Cache::flush();
    $available = Product::factory()->create(['stock' => 2]);
    $unavailable = Product::factory()->create(['is_active' => false]);
    $oldCartProduct = Product::factory()->create();
    StoreSetting::factory()->create(['track_stock' => true]);
    $browserCart = [$available->id => 8, $unavailable->id => 1];

    $this->withSession(['cart' => [$oldCartProduct->id => 3]])
        ->postJson(route('cart.recover'), ['cart' => $browserCart, 'offline_sync' => true])
        ->assertOk()
        ->assertJsonPath('cart.'.$available->id, 2)
        ->assertJsonMissingPath('cart.'.$unavailable->id);

    expect(session('cart'))->toBe([$available->id => 2]);
});

test('offline cart sync can persist an emptied cart', function () {
    $product = Product::factory()->create();
    StoreSetting::factory()->create(['track_stock' => false]);

    $this->withSession(['cart' => [$product->id => 1]])
        ->postJson(route('cart.recover'), ['cart' => [], 'offline_sync' => true])
        ->assertOk()
        ->assertJsonPath('cart', []);

    expect(session('cart'))->toBe([]);
});

test('offline cart sync rejects a missing snapshot without changing the session cart', function () {
    $product = Product::factory()->create();

    $this->withSession(['cart' => [$product->id => 2]])
        ->postJson(route('cart.recover'), ['offline_sync' => true])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cart');

    expect(session('cart'))->toBe([$product->id => 2]);
});

test('cart recovery finds an order after a lost checkout response and clears stale cart data', function () {
    $token = (string) Str::uuid();
    $order = Order::factory()->create(['checkout_token' => $token]);
    $this->withSession(['cart' => [123 => 2], 'checkout_token' => $token])
        ->postJson(route('cart.recover'), ['checkout_token' => $token])
        ->assertOk()
        ->assertJsonPath('order_url', route('orders.confirmation', ['orderNumber' => $order->order_number]));

    expect(session('last_order_number'))->toBe($order->order_number)
        ->and(session()->has('cart'))->toBeFalse();
});
test('admin pages are forbidden to non-admin users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.settings'))
        ->assertForbidden();
});

test('customer shipping zone determines the saved city and fee', function () {
    Cache::flush();
    $product = Product::factory()->create();
    $zoneId = (string) Str::uuid();
    StoreSetting::factory()->create([
        'track_stock' => false,
        'shipping_zones' => [['id' => $zoneId, 'name_en' => 'Giza', 'name_ar' => 'الجيزة', 'fee' => '35.00']],
    ]);
    $this->withSession(['cart' => [$product->id => 1], 'checkout_token' => (string) Str::uuid()]);

    Livewire::test(CheckoutPage::class)
        ->set('name', 'Test Customer')
        ->set('phone', '01012345678')
        ->set('city', $zoneId)
        ->assertSee('35.00')
        ->set('address', '12 Test Street, Giza')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->sole();
    expect($order->city)->toBe('Giza')
        ->and((float) $order->shipping_fee)->toBe(35.0);
});

test('checkout accepts Egyptian mobile numbers and stores them in international format', function () {
    Cache::flush();
    $product = Product::factory()->create();
    StoreSetting::factory()->create(['track_stock' => false]);
    $this->withSession(['cart' => [$product->id => 1], 'checkout_token' => (string) Str::uuid()]);

    Livewire::test(CheckoutPage::class)
        ->set('name', 'Egyptian Customer')
        ->set('phone', '+20 10-1234-5678')
        ->set('city', 'Cairo')
        ->set('address', '12 Test Street, Cairo')
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect(Order::query()->sole()->phone)->toBe('+201012345678');
});

test('checkout rejects non-Egyptian mobile numbers', function () {
    Cache::flush();
    $product = Product::factory()->create();
    StoreSetting::factory()->create(['track_stock' => false]);
    $this->withSession(['cart' => [$product->id => 1], 'checkout_token' => (string) Str::uuid()]);

    Livewire::test(CheckoutPage::class)
        ->set('name', 'Test Customer')
        ->set('phone', '+1 202 555 0123')
        ->set('city', 'Cairo')
        ->set('address', '12 Test Street, Cairo')
        ->call('placeOrder')
        ->assertHasErrors(['phone']);

    expect(Order::query()->count())->toBe(0);
});

test('admin can mark a delivered order returned only with a reason', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    $order = Order::factory()->create(['status' => 'delivered', 'payment_status' => 'paid']);

    Livewire::actingAs($admin)->test(AdminOrders::class)
        ->set('statusChoices.'.$order->id, 'returned')
        ->call('updateStatusFor', $order->id)
        ->assertHasErrors(['returnReasons.'.$order->id]);

    $component = Livewire::actingAs($admin)->test(AdminOrders::class)
        ->call('changeStatus', $order->id, 'returned')
        ->assertSee('Return reason')
        ->assertSee('Describe the reason for the return')
        ->assertSee('Save return status')
        ->assertDontSee('Saved automatically when you leave this field')
        ->set('returnReasons.'.$order->id, 'Customer returned the item')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe('delivered');

    $component->call('updateStatusFor', $order->id)->assertHasNoErrors();

    expect($order->fresh()->status)->toBe('returned')
        ->and($order->fresh()->return_reason)->toBe('Customer returned the item')
        ->and($order->fresh()->payment_status)->toBe('refunded');
});

test('delivered orders keep an enabled selector with every status choice', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    $order = Order::factory()->create(['status' => 'delivered']);

    $html = Livewire::actingAs($admin)->test(AdminOrders::class)->html();
    preg_match('/<select class="order-status"[^>]*>/', $html, $select);

    expect($select[0] ?? '')->not->toContain('disabled');
    foreach (['pending', 'confirmed', 'shipped', 'delivered', 'returned', 'cancelled'] as $status) {
        expect($html)->toContain('<option value="'.$status.'"');
    }

    Livewire::actingAs($admin)->test(AdminOrders::class)
        ->call('changeStatus', $order->id, 'pending')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe('pending')
        ->and($order->fresh()->payment_status)->toBe('unpaid');
});

test('admin messages show customer email in Gmail and phone in WhatsApp', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    ContactMessage::factory()->create(['email' => 'customer@example.com', 'phone' => '01062575484']);

    Livewire::actingAs($admin)->test(AdminMessages::class)
        ->assertSee('https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to=customer%40example.com', false)
        ->assertSee('https://wa.me/201062575484', false)
        ->assertSee('Gmail')
        ->assertSee('WhatsApp');
});

test('admin can open complete order details from the order image', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    $order = Order::factory()->create([
        'customer_name' => 'Details Customer',
        'email' => 'details@example.com',
        'phone' => '01062575484',
        'city' => 'Cairo',
        'address' => '12 Detail Street',
        'notes' => 'Call before delivery',
    ]);
    OrderItem::factory()->for($order)->create([
        'product_name' => 'Details Product',
        'sku' => 'SKU-DETAIL',
        'unit_price' => 250,
        'quantity' => 2,
        'line_total' => 500,
    ]);

    Livewire::actingAs($admin)->test(AdminOrders::class)
        ->assertSee(route('admin.orders.show', $order), false);

    $this->actingAs($admin)->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Details Customer')
        ->assertSee('details@example.com')
        ->assertSee('12 Detail Street')
        ->assertSee('Call before delivery')
        ->assertSee('Details Product')
        ->assertSee('SKU-DETAIL')
        ->assertSee('500.00');
});

test('only admins can view order details', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create();

    $this->actingAs($user)->get(route('admin.orders.show', $order))->assertForbidden();
});

test('opening an admin message marks it read without a mark as read button', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    $message = ContactMessage::factory()->create(['read_at' => null]);

    Livewire::actingAs($admin)->test(AdminMessages::class)
        ->assertSee('x-on:toggle=', false)
        ->assertSee('$wire.markRead('.$message->id.')', false)
        ->assertDontSee('Mark read')
        ->assertDontSee('Mark unread')
        ->assertSee('Delete')
        ->call('markRead', $message->id)
        ->assertHasNoErrors();

    expect($message->fresh()->read_at)->not->toBeNull();
});

test('admin order filters reflect stored status and never offer processing', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    $pending = Order::factory()->create(['status' => 'pending']);
    $confirmed = Order::factory()->create(['status' => 'confirmed']);
    $legacyProcessing = Order::factory()->create(['status' => 'processing']);
    $shipped = Order::factory()->create(['status' => 'shipped']);

    Livewire::actingAs($admin)->test(AdminOrders::class)
        ->set('statusFilter', 'confirmed')
        ->assertSee($confirmed->order_number)
        ->assertSee($legacyProcessing->order_number)
        ->assertDontSee($pending->order_number)
        ->assertDontSee($shipped->order_number)
        ->assertDontSee('Processing');
});

test('WhatsApp button is always available but only pending orders open with a prefilled confirmation', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    StoreSetting::factory()->create(['whatsapp_status_templates' => [
        'pending' => "أهلًا يا :name\nطلبك رقم :order_number",
        'confirmed' => 'أهلًا يا :name، طلبك رقم :order_number',
    ]]);
    Cache::flush();
    $order = Order::factory()->create([
        'status' => 'pending',
        'phone' => '01062575484',
        'customer_name' => 'Test Customer',
        'address' => '12 Test Street',
        'city' => 'Cairo',
    ]);

    Livewire::actingAs($admin)->test(AdminOrders::class)
        ->assertSee('wa.me/201062575484?text=')
        ->assertSee(rawurlencode('أهلًا يا Test Customer'))
        ->call('changeStatus', $order->id, 'confirmed')
        ->assertHasNoErrors()
        ->assertSee('https://wa.me/201062575484')
        ->assertDontSee('?text=')
        ->assertSee('Manage messages')
        ->assertSee('admin/whatsapp-templates');

    expect($order->fresh()->status)->toBe('confirmed');
});

test('admin can save and remove a custom Egyptian WhatsApp template for an order status', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    StoreSetting::factory()->create();
    Cache::flush();

    $templates = Livewire::actingAs($admin)->test(AdminWhatsAppTemplates::class)
        ->assertSee('الرسالة بالمصري')
        ->assertDontSee('Create one editable Egyptian Arabic message')
        ->set('templates.confirmed', 'أهلًا يا :name، طلبك :order_number')
        ->call('saveTemplate', 'confirmed')
        ->assertSee('تم حفظ الرسالة')
        ->assertHasNoErrors();
    expect($templates->get('templates.pending'))->toContain('أهلًا يا :name');
    expect(StoreSetting::query()->firstOrFail()->whatsapp_status_templates['confirmed'])->toBe('أهلًا يا :name، طلبك :order_number');

    Livewire::actingAs($admin)->test(AdminWhatsAppTemplates::class)
        ->call('deleteTemplate', 'confirmed')
        ->assertSee('تم حذف الرسالة')
        ->assertHasNoErrors();

    expect(StoreSetting::query()->firstOrFail()->whatsapp_status_templates['confirmed'])->toBe('');
});

test('saved pending WhatsApp template replaces the default in the order link', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    StoreSetting::factory()->create();
    Cache::flush();

    Livewire::actingAs($admin)->test(AdminWhatsAppTemplates::class)
        ->set('templates.pending', 'رسالة معدلة للطلب :order_number')
        ->assertHasNoErrors();

    $order = Order::factory()->create(['status' => 'pending', 'phone' => '01062575484']);

    Livewire::actingAs($admin)->test(AdminOrders::class)
        ->assertSee(rawurlencode('رسالة معدلة للطلب '.$order->order_number))
        ->assertDontSee(rawurlencode('معاك فريق أثَر'));
});

test('customer can cancel only their own pending orders', function () {
    $customer = User::factory()->create();
    $pending = Order::factory()->create(['user_id' => $customer->id, 'status' => 'pending']);
    $confirmed = Order::factory()->create(['user_id' => $customer->id, 'status' => 'confirmed']);

    Livewire::actingAs($customer)->test(OrderHistory::class)
        ->call('cancelOrder', $pending->id)
        ->assertHasNoErrors();

    expect($pending->fresh()->status)->toBe('cancelled');

    Livewire::actingAs($customer)->test(OrderHistory::class)
        ->call('cancelOrder', $confirmed->id)
        ->assertForbidden();
});

test('site settings can upload and persist a logo in public storage', function () {
    Cache::flush();
    Storage::fake('public');
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    StoreSetting::factory()->create(['store_name' => 'ATHAR', 'currency_symbol' => 'EGP']);

    Livewire::actingAs($admin)->test(AdminStoreSettings::class)
        ->set('logoFile', UploadedFile::fake()->image('athar.png', 1400, 700))
        ->set('heroImageFile', UploadedFile::fake()->image('hero.jpg', 2400, 1400))
        ->call('save')
        ->assertHasNoErrors();

    $settings = StoreSetting::query()->sole();
    $logoPath = $settings->logo_url;
    $heroPath = $settings->hero_image_url;
    expect($logoPath)->toStartWith('site/')->toEndWith('.webp')
        ->and($heroPath)->toStartWith('site/')->toEndWith('.webp');
    Storage::disk('public')->assertExists($logoPath);
    Storage::disk('public')->assertExists($heroPath);
    expect(getimagesize(Storage::disk('public')->path($logoPath)))->toMatchArray([0 => 1200, 1 => 600, 2 => IMAGETYPE_WEBP])
        ->and(getimagesize(Storage::disk('public')->path($heroPath)))->toMatchArray([0 => 1920, 1 => 1120, 2 => IMAGETYPE_WEBP]);
});

test('admin dashboard separates confirmed revenue, delivered revenue, and returned order value', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    Order::factory()->create(['status' => 'confirmed', 'total' => 1200]);
    Order::factory()->create(['status' => 'delivered', 'total' => 800]);
    Order::factory()->create(['status' => 'returned', 'total' => 350]);

    Livewire::actingAs($admin)->test(AdminDashboard::class)
        ->assertSee('1,200.00')
        ->assertSee('800.00')
        ->assertSee('350.00');
});

test('admin dashboard polls while visible and includes newly created orders on refresh', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();

    $dashboard = Livewire::actingAs($admin)->test(AdminDashboard::class)
        ->assertSee('wire:poll.10s.visible', false);

    $order = Order::factory()->create(['customer_name' => 'New Dashboard Order']);

    $dashboard->call('$refresh')->assertSee($order->order_number);
});

test('admin dashboard status chips filter recent orders without leaving the dashboard', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    $delivered = Order::factory()->create(['status' => 'delivered']);
    $pending = Order::factory()->create(['status' => 'pending']);

    Livewire::actingAs($admin)->test(AdminDashboard::class)
        ->call('filterOrdersByStatus', 'delivered')
        ->assertSee($delivered->order_number)
        ->assertDontSee($pending->order_number)
        ->assertSee('Delivered');
});

test('editing a product opens the editor and only exposes visibility', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    $product = Product::factory()->create(['is_featured' => true]);

    Livewire::actingAs($admin)->test(AdminProducts::class)
        ->call('editProduct', $product->id)
        ->assertDispatched('product-form-opened')
        ->assertSee('Visible in store')
        ->assertDontSee('Featured');
});

test('admin can save shipping zones in store settings', function () {
    Cache::flush();
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    StoreSetting::factory()->create(['store_name' => 'ATHAR', 'currency_symbol' => 'EGP']);
    $zone = ['id' => (string) Str::uuid(), 'name_en' => 'Cairo', 'name_ar' => 'القاهرة', 'fee' => '50.00'];
    $policySection = ['id' => (string) Str::uuid(), 'title_en' => 'Delivery', 'body_en' => 'Delivery details', 'title_ar' => 'التوصيل', 'body_ar' => 'تفاصيل التوصيل'];

    Livewire::actingAs($admin)->test(AdminStoreSettings::class)
        ->set('form.shipping_zones', [$zone])
        ->set('form.policy_sections', [$policySection])
        ->call('save')
        ->assertHasNoErrors();

    expect(StoreSetting::query()->sole()->shipping_zones)->toBe([$zone])
        ->and(StoreSetting::query()->sole()->policy_sections)->toBe([$policySection]);
    $this->get(route('policy'))->assertOk()->assertSee('Delivery')->assertSee('Delivery details');
});

test('admin can save multiple product photos for the storefront gallery', function () {
    Storage::fake('public');
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    $category = Category::factory()->create();

    Livewire::actingAs($admin)->test(AdminProducts::class)
        ->call('createProduct')
        ->set('categoryId', $category->id)
        ->set('name', 'Serving Bowl')
        ->set('price', '250')
        ->set('newImages', [UploadedFile::fake()->image('front.jpg', 2000, 1000), UploadedFile::fake()->image('side.jpg', 900, 600)])
        ->call('saveProduct')
        ->assertHasNoErrors();

    $product = Product::query()->sole();
    expect($product->image_path)->not->toBeNull()
        ->and($product->image_path)->toEndWith('.webp')
        ->and($product->gallery_paths)->toHaveCount(1)
        ->and($product->gallery_paths[0])->toEndWith('.webp');
    Storage::disk('public')->assertExists($product->image_path);
    Storage::disk('public')->assertExists($product->gallery_paths[0]);
    expect(getimagesize(Storage::disk('public')->path($product->image_path)))->toMatchArray([0 => 1600, 1 => 800, 2 => IMAGETYPE_WEBP]);
});

test('product image uploads reject incorrect mime types, fake extensions, and oversized files', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    $category = Category::factory()->create();
    $form = Livewire::actingAs($admin)->test(AdminProducts::class)
        ->call('createProduct')
        ->set('categoryId', $category->id)
        ->set('name', 'Validated Upload Product')
        ->set('price', '250');

    $form->set('newImages', [UploadedFile::fake()->createWithContent('not-an-image.jpg', 'plain text')])
        ->call('saveProduct')->assertHasErrors();
    $form->set('newImages', [UploadedFile::fake()->image('executable.php', 100, 100)])
        ->call('saveProduct')->assertHasErrors();
    $form->set('newImages', [UploadedFile::fake()->image('oversized.jpg', 100, 100)->size(4097)])
        ->call('saveProduct')->assertHasErrors();

    expect(Product::query()->count())->toBe(0);
});

test('category cover uploads are resized and saved as WebP', function () {
    Storage::fake('public');
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();

    Livewire::actingAs($admin)->test(Categories::class)
        ->call('createCategory')
        ->set('name', 'Image Category')
        ->set('image', UploadedFile::fake()->image('category.png', 2000, 1000))
        ->call('saveCategory')
        ->assertHasNoErrors();

    $path = Category::query()->sole()->image_path;
    expect($path)->toEndWith('.webp')
        ->and(getimagesize(Storage::disk('public')->path($path)))->toMatchArray([0 => 1200, 1 => 600, 2 => IMAGETYPE_WEBP]);
});

test('storefront routes render and send security headers', function () {
    $product = Product::factory()->create(['image_path' => 'products/front.jpg', 'gallery_paths' => ['products/side.jpg']]);

    $response = $this->get(route('home'))->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
        ->assertSee('data-navigate-once="true"', false);
    preg_match('/<script id="storefront-runtime" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $runtimeMatch);

    expect($runtimeMatch)->toHaveCount(2);
    expect(json_decode($runtimeMatch[1], true, flags: JSON_THROW_ON_ERROR))->toMatchArray([
        'scope' => 'guest',
        'cartPath' => route('cart'),
    ]);
    $this->get(route('shop'))->assertOk();
    $this->get(route('category.show', $product->category->slug))->assertOk();
    $this->get(route('product.show', $product->slug))->assertOk()->assertSee('product-photo-'.$product->id.'-1');
    $this->get(route('contact'))->assertOk();
    $this->get(route('about'))->assertOk();
    $this->get(route('policy'))->assertOk();
});

test('guest storefront page can be cached for offline reading', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="offline-cacheable" content="public">', false);
});

test('authenticated storefront page is marked private for offline caching', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="offline-cacheable" content="private">', false);
});

test('product gallery arrows move through every image in both directions', function () {
    $product = Product::factory()->create(['image_path' => 'products/front.jpg', 'gallery_paths' => ['products/side.jpg']]);

    Livewire::test(ProductPage::class, ['product' => $product])
        ->assertSet('activeImageIndex', 0)
        ->call('nextImage')
        ->assertSet('activeImageIndex', 1)
        ->call('previousImage')
        ->assertSet('activeImageIndex', 0);
});

test('every admin screen renders for an administrator', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    $this->actingAs($admin);

    foreach (['admin.dashboard', 'admin.orders', 'admin.products', 'admin.categories', 'admin.messages', 'admin.settings', 'admin.whatsapp-templates'] as $routeName) {
        $this->get(route($routeName))->assertOk();
    }
});

test('manual demo seeder adds the preview catalog and remains repeatable', function () {
    Storage::fake('public');

    $this->seed(DemoDataSeeder::class);
    $this->seed(DemoDataSeeder::class);

    expect(Category::query()->whereIn('slug', ['cookware', 'dinnerware', 'storage', 'utensils', 'glassware', 'appliances'])->count())->toBe(6)
        ->and(Product::query()->count())->toBe(28)
        ->and(Order::query()->count())->toBe(4)
        ->and(StoreSetting::query()->sole()->track_stock)->toBeFalse()
        ->and(StoreSetting::query()->sole()->tiktok_url)->toContain('tiktok.com')
        ->and(User::query()->where('email', 'admin@athar.com')->sole()->isAdmin())->toBeTrue();

    Storage::disk('public')->assertExists('demo/products/enamel-pot-24cm-1.svg');
});
