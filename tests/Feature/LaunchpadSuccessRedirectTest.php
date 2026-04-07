<?php

use App\Models\Assistant;

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

it('redirects to sales page with error when task not found', function () {
    // Use a session_id that matches no task. The retry loop will
    // exhaust attempts. We need to make this test faster by mocking sleep.
    // For now, we accept the test takes ~5 seconds due to retry logic.
    $response = $this->get('/launchpad/success?session_id=cs_nonexistent');

    $response->assertRedirect(route('launchpad'));
});
