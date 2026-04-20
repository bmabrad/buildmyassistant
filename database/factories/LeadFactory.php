<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source' => 'launchpad',
            'status' => 'partial',
            'buyer_name' => fake()->firstName(),
            'buyer_email' => fake()->unique()->safeEmail(),
            'business_description' => fake()->sentence(12),
            'business_role' => fake()->randomElement(['Executive coach', 'Management consultant', 'Financial advisor', 'Course creator', 'Brand designer']),
            'who_they_serve' => fake()->sentence(10),
            'tools_used' => fake()->randomElement(['Claude CoWork', 'Claude', 'ChatGPT', 'Gemini', 'Copilot', 'None yet']),
            'time_drains' => fake()->sentence(15),
            'tedious_work' => fake()->sentence(12),
            'one_handoff_today' => fake()->sentence(10),
            'ai_usage_level' => fake()->randomElement(['Light', 'Regular', 'Power user']),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function generated(): static
    {
        return $this->state(fn () => [
            'status' => 'generated',
            'completed_at' => now()->subMinutes(2),
            'generated_at' => now(),
            'business_type' => 'Coach',
            'assistant_pick' => 'Exec',
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status' => 'delivered',
            'completed_at' => now()->subMinutes(10),
            'generated_at' => now()->subMinutes(5),
            'delivered_at' => now(),
            'business_type' => 'Coach',
            'assistant_pick' => 'Exec',
        ]);
    }
}
