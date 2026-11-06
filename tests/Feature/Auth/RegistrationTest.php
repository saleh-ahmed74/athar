<?php

use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registration is limited after five attempts for the same email and IP', function () {
    Http::fake(['https://api.pwnedpasswords.com/range/*' => Http::response("FFFFF:0\n", 200)]);
    $component = Volt::test('auth.register')
        ->set('name', 'Rate Limited User')
        ->set('email', 'limited-register@example.test')
        ->set('password', 'weak')
        ->set('password_confirmation', 'weak');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $component->call('register')->assertHasErrors('password');
    }

    $component->call('register')->assertHasErrors('email');
});

test('new users can register', function () {
    Http::fake(['https://api.pwnedpasswords.com/range/*' => Http::response("FFFFF:0\n", 200)]);
    $this->withSession(['cart' => [99 => 1]]);

    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'Strong-Register-Password-42!')
        ->set('password_confirmation', 'Strong-Register-Password-42!')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();
    expect(session('cart'))->toBeNull();
});
