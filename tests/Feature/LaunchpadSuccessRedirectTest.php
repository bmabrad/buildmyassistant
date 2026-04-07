<?php

use App\Models\Assistant;
use App\Models\User;

it('redirects to chat url when task exists', function () {
    $task = Assistant::factory()->create([
        'stripe_payment_id' => 'cs_test_redirect_success',
    ]);

    // Mock the Stripe session retrieval by directly testing the redirect logic.
    // The success handler looks up the task by stripe_payment_id.
    $response = $this->get('/launchpad/success?session_id=cs_test_redirect_success');

    $response->assertRedirect("/launchpad/{$task->token}");
});

it('redirects to sales page when no session id provided', function () {
    $response = $this->get('/launchpad/success');

    $response->assertRedirect(route('launchpad'));
});

it('auto-logs in new user on first purchase success redirect', function () {
    $user = User::factory()->create();

    $task = Assistant::factory()->create([
        'stripe_payment_id' => 'cs_test_auto_login',
        'user_id' => $user->id,
    ]);

    $this->assertGuest();

    $response = $this->get('/launchpad/success?session_id=cs_test_auto_login');

    $response->assertRedirect("/launchpad/{$task->token}");
    $this->assertAuthenticatedAs($user);
});

it('does not override existing auth on success redirect', function () {
    $existingUser = User::factory()->create();
    $otherUser = User::factory()->create();

    $task = Assistant::factory()->create([
        'stripe_payment_id' => 'cs_test_existing_auth',
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($existingUser)
        ->get('/launchpad/success?session_id=cs_test_existing_auth');

    $this->assertAuthenticatedAs($existingUser);
});

it('redirects to sales page with error when task not found', function () {
    // Use a session_id that matches no task. The retry loop will
    // exhaust attempts. We need to make this test faster by mocking sleep.
    // For now, we accept the test takes ~5 seconds due to retry logic.
    $response = $this->get('/launchpad/success?session_id=cs_nonexistent');

    $response->assertRedirect(route('launchpad'));
});
