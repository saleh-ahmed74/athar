<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

test('password can be updated', function () {
    Http::fake(['https://api.pwnedpasswords.com/range/*' => Http::response("FFFFF:0\n", 200)]);
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Volt::test('settings.password')
        ->set('current_password', 'password')
        ->set('password', 'Strong-New-Password-42!')
        ->set('password_confirmation', 'Strong-New-Password-42!')
        ->call('updatePassword');

    $response->assertHasNoErrors();

    expect(Hash::check('Strong-New-Password-42!', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    Http::fake(['https://api.pwnedpasswords.com/range/*' => Http::response("FFFFF:0\n", 200)]);
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Volt::test('settings.password')
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $response->assertHasErrors(['current_password']);
});
