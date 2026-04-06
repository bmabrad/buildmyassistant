<?php

namespace Database\Factories;

use App\Models\LaunchpadMessage;
use App\Models\LaunchpadTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaunchpadMessageFactory extends Factory
{
    protected $model = LaunchpadMessage::class;

    public function definition(): array
    {
        return [
            'task_id' => LaunchpadTask::factory(),
            'role' => fake()->randomElement(['user', 'assistant']),
            'content' => fake()->paragraph(),
            'phase' => 1,
            'is_instruction_sheet' => false,
        ];
    }

    public function fromUser(): static
    {
        return $this->state(['role' => 'user']);
    }

    public function fromAssistant(): static
    {
        return $this->state(['role' => 'assistant']);
    }

    public function instructionSheet(): static
    {
        return $this->state([
            'role' => 'assistant',
            'is_instruction_sheet' => true,
        ]);
    }
}
