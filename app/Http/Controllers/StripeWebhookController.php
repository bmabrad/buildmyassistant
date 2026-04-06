<?php

namespace App\Http\Controllers;

use App\Models\LaunchpadTask;
use Illuminate\Support\Str;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends WebhookController
{
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'];

        $paymentId = $session['id'];

        // Idempotency: skip if task already exists for this payment
        if (! LaunchpadTask::where('stripe_payment_id', $paymentId)->exists()) {
            LaunchpadTask::create([
                'token' => (string) Str::uuid(),
                'stripe_payment_id' => $paymentId,
                'stripe_customer_id' => $session['customer'] ?? null,
                'name' => $session['customer_details']['name'] ?? 'Unknown',
                'email' => $session['customer_details']['email'] ?? 'unknown@example.com',
                'status' => 'pending',
                'phase' => 1,
                'phase_1_complete' => false,
            ]);
        }

        return $this->successMethod();
    }
}
