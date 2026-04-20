<?php

namespace Database\Factories;

use App\Models\PromptVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromptVersion>
 */
class PromptVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product' => 'launchpad',
            'name' => 'v' . fake()->numerify('###'),
            'system_prompt' => fake()->paragraphs(3, true),
            'notes' => null,
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }
}
