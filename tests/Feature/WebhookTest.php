<?php

use App\Mail\PostPurchaseMail;
use App\Models\Assistant;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

beforeEach(function () {
    $this->withoutMiddleware(VerifyWebhookSignature::class);
    Mail::fake();
});

it('creates a task from checkout.session.completed webhook', function () {
    $payload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_123456',
                'customer' => 'cus_test_789',
                'customer_details' => [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                ],
            ],
        ],
    ];

    $this->postJson('/stripe/webhook', $payload)->assertOk();

    $task = Assistant::where('stripe_payment_id', 'cs_test_123456')->first();

    expect($task)->not->toBeNull()
        ->and($task->name)->toBe('Jane Smith')
        ->and($task->email)->toBe('jane@example.com')
        ->and($task->stripe_customer_id)->toBe('cus_test_789')
        ->and($task->status)->toBe('pending')
        ->and($task->phase)->toBe(1)
        ->and($task->phase_1_complete)->toBeFalse()
        ->and($task->token)->toMatch('/^[0-9a-f]{8}-/i');
});

it('is idempotent for duplicate webhook events', function () {
    $payload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_duplicate',
                'customer' => 'cus_test_dup',
                'customer_details' => [
                    'name' => 'Duplicate Test',
                    'email' => 'dup@example.com',
                ],
            ],
        ],
    ];

    $this->postJson('/stripe/webhook', $payload);
    $this->postJson('/stripe/webhook', $payload);

    expect(Assistant::where('stripe_payment_id', 'cs_test_duplicate')->count())->toBe(1);
});

it('sends post-purchase email on checkout completed', function () {
    $payload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_email_send',
                'customer' => 'cus_test_email',
                'customer_details' => [
                    'name' => 'Brad Test',
                    'email' => 'brad@example.com',
                ],
            ],
        ],
    ];

    $this->postJson('/stripe/webhook', $payload)->assertOk();

    Mail::assertQueued(PostPurchaseMail::class, function ($mail) {
        return $mail->hasTo('brad@example.com')
            && $mail->buyerName === 'Brad Test';
    });
});

it('does not send post-purchase email for unknown email', function () {
    $payload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_no_email',
                'customer_details' => [],
            ],
        ],
    ];

    $this->postJson('/stripe/webhook', $payload)->assertOk();

    Mail::assertNotQueued(PostPurchaseMail::class);
});

it('handles missing customer details gracefully', function () {
    $payload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_minimal',
                'customer_details' => [],
            ],
        ],
    ];

    $this->postJson('/stripe/webhook', $payload);

    $task = Assistant::where('stripe_payment_id', 'cs_test_minimal')->first();

    expect($task)->not->toBeNull()
        ->and($task->name)->toBe('Unknown')
        ->and($task->email)->toBe('unknown@example.com')
        ->and($task->stripe_customer_id)->toBeNull();
});
