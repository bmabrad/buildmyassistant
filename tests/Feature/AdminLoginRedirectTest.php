<?php

use App\Models\MagicLink;
use App\Models\User;

it('redirects admin users to /admin after password login', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password123'),
    ]);

    $this->post('/login', [
        'email' => 'admin@test.com',
        'password' => 'password123',
    ])->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin);
});

it('redirects regular users to /dashboard after password login', function () {
    $user = User::factory()->create([
        'email' => 'user@test.com',
        'password' => bcrypt('password123'),
    ]);

    $this->post('/login', [
        'email' => 'user@test.com',
        'password' => 'password123',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('redirects admin users to /admin after magic link login', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@test.com',
    ]);

    $magicLink = MagicLink::generateFor('admin@test.com');

    $this->get('/auth/magic/' . $magicLink->token)
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin);
});

it('redirects regular users to /dashboard after magic link login', function () {
    $user = User::factory()->magicLinkOnly()->create([
        'email' => 'user@test.com',
    ]);

    $magicLink = MagicLink::generateFor('user@test.com');

    $this->get('/auth/magic/' . $magicLink->token)
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});
