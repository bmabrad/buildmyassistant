<?php

use App\Models\Assistant;
use App\Models\User;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

beforeEach(function () {
    $this->withoutMiddleware(VerifyWebhookSignature::class);
});

function webhookPayload(string $paymentId, string $email, string $name, ?string $customer = 'cus_test_123'): array
{
    return [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $paymentId,
                'customer' => $customer,
                'customer_details' => [
                    'name' => $name,
                    'email' => $email,
                ],
            ],
        ],
    ];
}

it('creates a user when no account exists for the email', function () {
    $this->postJson('/stripe/webhook', webhookPayload('cs_new_user', 'alice@example.com', 'Alice'))
        ->assertOk();

    $user = User::where('email', 'alice@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Alice')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->password)->toBeNull();
});

it('links to existing user when account already exists', function () {
    $existing = User::factory()->magicLinkOnly()->create([
        'email' => 'bob@example.com',
        'name' => 'Bob',
    ]);

    $this->postJson('/stripe/webhook', webhookPayload('cs_existing', 'bob@example.com', 'Bob Smith'))
        ->assertOk();

    expect(User::where('email', 'bob@example.com')->count())->toBe(1);

    $task = Assistant::where('stripe_payment_id', 'cs_existing')->first();
    expect($task->user_id)->toBe($existing->id);
});

it('sets correct stripe customer id on user', function () {
    $this->postJson('/stripe/webhook', webhookPayload('cs_stripe_id', 'carol@example.com', 'Carol', 'cus_carol_123'))
        ->assertOk();

    $user = User::where('email', 'carol@example.com')->first();
    expect($user->stripe_id)->toBe('cus_carol_123');
});

it('sets user_id on new launchpad task', function () {
    $this->postJson('/stripe/webhook', webhookPayload('cs_user_link', 'dave@example.com', 'Dave'))
        ->assertOk();

    $task = Assistant::where('stripe_payment_id', 'cs_user_link')->first();
    $user = User::where('email', 'dave@example.com')->first();

    expect($task->user_id)->toBe($user->id);
});

it('updates stripe id if it changed', function () {
    $user = User::factory()->magicLinkOnly()->create([
        'email' => 'eve@example.com',
        'stripe_id' => 'cus_old_123',
    ]);

    $this->postJson('/stripe/webhook', webhookPayload('cs_stripe_update', 'eve@example.com', 'Eve', 'cus_new_456'))
        ->assertOk();

    $user->refresh();
    expect($user->stripe_id)->toBe('cus_new_456');
});

it('user has many launchpad tasks', function () {
    $user = User::factory()->create();

    Assistant::factory()->create(['user_id' => $user->id]);
    Assistant::factory()->create(['user_id' => $user->id]);

    expect($user->assistants)->toHaveCount(2);
});

it('task belongs to user', function () {
    $user = User::factory()->create();
    $task = Assistant::factory()->create(['user_id' => $user->id]);

    expect($task->user->id)->toBe($user->id);
});
