<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;

test('production redirects to the configured HTTPS host and sends HSTS and CSP', function () {
    $this->app['env'] = 'production';
    config(['app.url' => 'https://athar.example']);

    $this->get('/shop?sort=featured')
        ->assertRedirect('https://athar.example/shop?sort=featured')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000')
        ->assertHeader('Content-Security-Policy');
});

test('production errors do not expose exception details', function () {
    config(['app.debug' => false]);
    $this->app['router']->get('/__test-error', fn () => throw new RuntimeException('sensitive stack trace marker'));

    $this->get('/__test-error')
        ->assertServerError()
        ->assertSee('We could not complete your request')
        ->assertDontSee('sensitive stack trace marker');
});

test('shop query filters reject unbounded values', function () {
    $this->get('/shop?q='.str_repeat('a', 101))->assertUnprocessable();
});

test('password breach check uses the k-anonymity range service', function () {
    Http::fake(['https://api.pwnedpasswords.com/range/*' => Http::response("FFFFF:0\n", 200)]);

    $validator = validator(
        ['password' => 'Valid-Unique-Password-482!'],
        ['password' => [Password::defaults()]],
    );

    expect($validator->passes())->toBeTrue();
    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/range/'));
});

test('robots and sitemap expose only public storefront pages', function () {
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('/sitemap.xml');
    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('home'), false)->assertDontSee(route('admin.dashboard'), false);
});

test('every admin page denies guests and normal users', function () {
    $order = Order::factory()->create();
    $user = User::factory()->create();
    $routes = [
        'admin.dashboard' => [],
        'admin.products' => [],
        'admin.categories' => [],
        'admin.orders' => [],
        'admin.orders.show' => [$order],
        'admin.messages' => [],
        'admin.settings' => [],
        'admin.whatsapp-templates' => [],
    ];

    foreach ($routes as $name => $parameters) {
        $this->get(route($name, $parameters))->assertRedirect(route('login'));
    }

    $this->actingAs($user);
    foreach ($routes as $name => $parameters) {
        $this->get(route($name, $parameters))->assertForbidden();
    }
});

test('storefront pages stay paginated and avoid per-product query growth at 500 products', function () {
    $category = Category::factory()->create();
    Product::factory()->count(500)->create(['category_id' => $category->id]);
    $queryCount = 0;
    DB::listen(function (QueryExecuted $query) use (&$queryCount): void {
        if (str_starts_with(strtolower($query->sql), 'select')) {
            $queryCount++;
        }
    });

    $response = $this->get(route('shop'))->assertOk();
    expect(substr_count($response->getContent(), 'class="product-card"'))->toBe(12)
        ->and($queryCount)->toBeLessThanOrEqual(12);
});
