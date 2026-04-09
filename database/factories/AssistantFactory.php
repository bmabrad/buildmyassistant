<?php

namespace Database\Factories;

use App\Models\Assistant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AssistantFactory extends Factory
{
    protected $model = Assistant::class;

    public function definition(): array
    {
        return [
            'token' => (string) Str::uuid(),
            'stripe_payment_id' => 'cs_test_' . Str::random(24),
            'stripe_customer_id' => 'cus_' . Str::random(14),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'status' => 'pending',
            'phase' => 1,
            'playbook_delivered' => false,
            'in_post_playbook' => false,
            'session_completed_at' => null,
            'fast_track_nudge_count' => 0,
            'total_input_tokens' => 0,
            'total_output_tokens' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }

    public function postPlaybook(): static
    {
        return $this->state([
            'status' => 'completed',
            'playbook_delivered' => true,
            'in_post_playbook' => true,
            'session_completed_at' => now(),
        ]);
    }

    public function expiredSupport(): static
    {
        return $this->state([
            'status' => 'completed',
            'playbook_delivered' => true,
            'in_post_playbook' => true,
            'session_completed_at' => now()->subDays(8),
        ]);
    }
}
