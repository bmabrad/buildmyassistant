<?php

namespace Database\Factories;

use App\Models\Delivery;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'channel' => 'email',
            'provider' => 'resend',
            'to_email' => fake()->safeEmail(),
            'subject' => 'Your starter prompt',
            'status' => 'pending',
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => 'sent',
            'provider_message_id' => 'msg_' . fake()->uuid(),
            'sent_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error' => 'Provider error',
        ]);
    }
}
