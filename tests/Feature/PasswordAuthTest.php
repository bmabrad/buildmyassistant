<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('can set a password when none exists', function () {
    $user = User::factory()->magicLinkOnly()->create();

    $this->actingAs($user)
        ->post('/dashboard/password', [
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])
        ->assertRedirect()
        ->assertSessionHas('password_updated');

    $user->refresh();
    expect(Hash::check('newsecret123', $user->password))->toBeTrue();
});

it('does not require current password when setting for the first time', function () {
    $user = User::factory()->magicLinkOnly()->create();

    $this->actingAs($user)
        ->post('/dashboard/password', [
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])
        ->assertRedirect()
        ->assertSessionHas('password_updated');
});

it('requires current password when changing an existing password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldsecret'),
    ]);

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/password', [
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])
        ->assertRedirect('/dashboard')
        ->assertSessionHasErrors('current_password');
});

it('can change password with correct current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldsecret'),
    ]);

    $this->actingAs($user)
        ->post('/dashboard/password', [
            'current_password' => 'oldsecret',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])
        ->assertRedirect()
        ->assertSessionHas('password_updated');

    $user->refresh();
    expect(Hash::check('newsecret123', $user->password))->toBeTrue();
});

it('rejects wrong current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldsecret'),
    ]);

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])
        ->assertRedirect('/dashboard')
        ->assertSessionHasErrors('current_password');
});

it('requires password confirmation', function () {
    $user = User::factory()->magicLinkOnly()->create();

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/password', [
            'password' => 'newsecret123',
            'password_confirmation' => 'different',
        ])
        ->assertRedirect('/dashboard')
        ->assertSessionHasErrors('password');
});

it('requires minimum 8 character password', function () {
    $user = User::factory()->magicLinkOnly()->create();

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/password', [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertRedirect('/dashboard')
        ->assertSessionHasErrors('password');
});

it('requires authentication to set password', function () {
    $this->post('/dashboard/password', [
        'password' => 'newsecret123',
        'password_confirmation' => 'newsecret123',
    ])->assertRedirect('/login');
});
