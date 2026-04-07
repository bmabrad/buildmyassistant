<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends WebhookController
{
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'];

        $paymentId = $session['id'];
        $email = $session['customer_details']['email'] ?? 'unknown@example.com';
        $name = $session['customer_details']['name'] ?? 'Unknown';
        $stripeCustomerId = $session['customer'] ?? null;

        // Find or create the user by email
        $user = null;
        if ($email !== 'unknown@example.com') {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => null,
                    'email_verified_at' => now(),
                ]);
            }

            // Set or update Stripe customer ID
            if ($stripeCustomerId && $user->stripe_id !== $stripeCustomerId) {
                $user->stripe_id = $stripeCustomerId;
                $user->save();
            }
        }

        // Idempotency: skip task creation if already exists for this payment
        if (! Assistant::where('stripe_payment_id', $paymentId)->exists()) {
            Assistant::create([
                'token' => (string) Str::uuid(),
                'stripe_payment_id' => $paymentId,
                'stripe_customer_id' => $stripeCustomerId,
                'name' => $name,
                'email' => $email,
                'status' => 'pending',
                'phase' => 1,
                'phase_1_complete' => false,
                'user_id' => $user?->id,
            ]);
        }

        return $this->successMethod();
    }
}
