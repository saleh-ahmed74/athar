<?php

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/settings/profile')->assertOk()->assertSee('My details')->assertSee('Back to store');
});

test('profile page keeps the one week session policy and avoids duplicate order lookups', function () {
    $user = User::factory()->create();
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $this->actingAs($user)->get('/settings/profile')->assertOk();
    $orderQueries = collect($queries)->filter(fn (string $sql): bool => preg_match('/\bselect\b.*\bfrom\s+["`]?orders["`]?/i', $sql) === 1);

    expect((int) config('session.lifetime'))->toBe(10080)
        ->and(config('session.expire_on_close'))->toBeFalse()
        ->and($orderQueries)->toHaveCount(2);
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Volt::test('settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('phone', '+20 10-1234-5678')
        ->set('address', '12 Nile Street, Cairo')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->phone)->toEqual('01012345678');
    expect($user->address)->toEqual('12 Nile Street, Cairo');
    expect($user->email_verified_at)->toBeNull();
});

test('profile rejects a non Egyptian mobile number', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('settings.profile')
        ->set('phone', '+1 202-555-0113')
        ->call('updateProfileInformation')
        ->assertHasErrors(['phone']);

    expect($user->fresh()->phone)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Volt::test('settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Volt::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Volt::test('settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
