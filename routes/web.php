<?php

use App\Http\Controllers\CartRecoveryController;
use App\Livewire\Account\OrderHistory;
use App\Livewire\Admin\Categories as AdminCategories;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Messages as AdminMessages;
use App\Livewire\Admin\OrderDetails as AdminOrderDetails;
use App\Livewire\Admin\Orders as AdminOrders;
use App\Livewire\Admin\Products as AdminProducts;
use App\Livewire\Admin\StoreSettings as AdminStoreSettings;
use App\Livewire\Admin\WhatsAppTemplates as AdminWhatsAppTemplates;
use App\Livewire\Storefront\CartPage;
use App\Livewire\Storefront\CheckoutPage;
use App\Livewire\Storefront\ContactPage;
use App\Livewire\Storefront\HomePage;
use App\Livewire\Storefront\OrderConfirmation;
use App\Livewire\Storefront\ProductPage;
use App\Livewire\Storefront\ShopPage;
use App\Livewire\Storefront\StaticPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', HomePage::class)->middleware('throttle:public')->name('home');
Route::get('/robots.txt', function () {
    $sitemap = e(route('sitemap'));

    return response("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /account\nDisallow: /settings\nSitemap: {$sitemap}\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

Route::get('/sitemap.xml', function () {
    $urls = collect(['home', 'shop', 'about', 'contact', 'policy'])
        ->map(fn (string $name): string => route($name))
        ->map(fn (string $url): string => '<url><loc>'.e($url).'</loc></url>')
        ->implode('');

    return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$urls.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');
Route::middleware('throttle:public')->group(function (): void {
    Route::get('/shop', ShopPage::class)->name('shop');
    Route::get('/category/{category:slug}', ShopPage::class)->name('category.show');
    Route::get('/product/{product:slug}', ProductPage::class)->name('product.show');
});
Route::get('/cart', CartPage::class)->name('cart');
Route::post('/cart/recover', CartRecoveryController::class)->middleware('throttle:30,1')->name('cart.recover');
Route::get('/checkout', CheckoutPage::class)->middleware('throttle:checkout')->name('checkout');
Route::get('/order-confirmation/{orderNumber}', OrderConfirmation::class)->name('orders.confirmation');
Route::get('/contact', ContactPage::class)->middleware('throttle:contact')->name('contact');
Route::get('/about', StaticPage::class)->defaults('page', 'about')->name('about');
Route::get('/policy', StaticPage::class)->defaults('page', 'policy')->name('policy');

Route::get('/language/{locale}', function (Request $request, string $locale) {
    abort_unless(in_array($locale, ['en', 'ar'], true), 404);
    $request->session()->put('locale', $locale);

    return redirect()->back();
})->middleware('throttle:30,1')->name('language.switch');

Route::middleware('auth')->group(function (): void {
    Route::get('/account/orders', OrderHistory::class)->name('account.orders');
    Route::redirect('/account', '/settings/profile')->name('account');
});

Route::prefix(config('app.admin_path', 'admin'))->name('admin.')->middleware(['auth', 'admin', 'throttle:admin'])->group(function (): void {
    Route::get('/', AdminDashboard::class)->name('dashboard');
    Route::get('/products', AdminProducts::class)->name('products');
    Route::get('/categories', AdminCategories::class)->name('categories');
    Route::get('/orders', AdminOrders::class)->name('orders');
    Route::get('/orders/{order}', AdminOrderDetails::class)->name('orders.show');
    Route::get('/messages', AdminMessages::class)->name('messages');
    Route::get('/settings', AdminStoreSettings::class)->name('settings');
    Route::get('/whatsapp-templates', AdminWhatsAppTemplates::class)->name('whatsapp-templates');
});

Route::redirect('dashboard', '/settings/profile')->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware(['auth'])->group(function (): void {
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
