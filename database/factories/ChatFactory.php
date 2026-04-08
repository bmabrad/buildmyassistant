<?php

namespace Database\Factories;

use App\Models\Assistant;
use App\Models\Chat;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatFactory extends Factory
{
    protected $model = Chat::class;

    public function definition(): array
    {
        return [
            'task_id' => Assistant::factory(),
            'role' => fake()->randomElement(['user', 'assistant']),
            'content' => fake()->paragraph(),
            'phase' => 1,
            'is_deliverable' => false,
            'playbook_content' => null,
            'instructions_content' => null,
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

    public function deliverable(): static
    {
        return $this->state([
            'role' => 'assistant',
            'is_deliverable' => true,
            'playbook_content' => "## 1. Your Bottleneck\nTest bottleneck content.\n\n## 2. Your Process Map\nTest process map.",
            'instructions_content' => "# Test Assistant — AI Assistant for Test Client\n\n## Role\nYou are Test Assistant.",
        ]);
    }

    /**
     * @deprecated Use deliverable() instead
     */
    public function instructionSheet(): static
    {
        return $this->deliverable();
    }
}
