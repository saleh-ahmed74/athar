<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('password reset requests are limited after five attempts for the same email and IP', function () {
    Notification::fake();
    $component = Volt::test('auth.forgot-password')->set('email', 'limited-reset@example.test');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $component->call('sendPasswordResetLink')->assertHasNoErrors();
    }

    $component->call('sendPasswordResetLink')->assertHasErrors('email');
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Http::fake(['https://api.pwnedpasswords.com/range/*' => Http::response("FFFFF:0\n", 200)]);
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = Volt::test('auth.reset-password', ['token' => $notification->token])
            ->set('email', $user->email)
            ->set('password', 'Strong-Reset-Password-42!')
            ->set('password_confirmation', 'Strong-Reset-Password-42!')
            ->call('resetPassword');

        $response
            ->assertHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        return true;
    });
});
