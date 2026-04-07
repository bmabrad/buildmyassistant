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
            'phase_1_complete' => false,
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
}
