<?php

use App\Models\User;

it('redirects to checkout when no saved payment method', function () {
    $user = User::factory()->magicLinkOnly()->create();

    $this->actingAs($user)
        ->get('/dashboard/new-build')
        ->assertRedirect(route('launchpad'));
});

it('requires authentication to start a new build', function () {
    $this->get('/dashboard/new-build')
        ->assertRedirect('/login');

    $this->post('/dashboard/new-build')
        ->assertRedirect('/login');
});

it('redirects charge to checkout when no saved payment method', function () {
    $user = User::factory()->magicLinkOnly()->create();

    $this->actingAs($user)
        ->post('/dashboard/new-build')
        ->assertRedirect(route('launchpad'));
});

it('redirects to launchpad when stripe customer is invalid', function () {
    $user = User::factory()->create();
    $user->forceFill(['stripe_id' => 'cus_fake_invalid'])->save();

    $this->actingAs($user)
        ->get('/dashboard/new-build')
        ->assertRedirect(route('launchpad'));
});

it('redirects post to launchpad when stripe customer is invalid', function () {
    $user = User::factory()->create();
    $user->forceFill(['stripe_id' => 'cus_fake_invalid'])->save();

    $this->actingAs($user)
        ->post('/dashboard/new-build')
        ->assertRedirect(route('launchpad'));
});
