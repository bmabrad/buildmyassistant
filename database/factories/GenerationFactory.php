<?php

namespace Database\Factories;

use App\Models\Generation;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'product' => 'launchpad',
            'model' => 'claude-sonnet-4-20250514',
            'status' => 'pending',
            'input_payload' => ['buyer_name' => fake()->firstName()],
        ];
    }

    public function success(): static
    {
        return $this->state(fn () => [
            'status' => 'success',
            'output' => [
                'business_type' => 'Coach',
                'assistant_pick' => ['role' => 'Exec', 'reason' => 'Inbox pain dominates.'],
            ],
            'latency_ms' => fake()->numberBetween(800, 3500),
            'input_tokens' => fake()->numberBetween(2000, 4000),
            'output_tokens' => fake()->numberBetween(800, 1800),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error' => 'Schema validation failed',
        ]);
    }
}
