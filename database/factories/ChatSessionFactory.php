<?php

namespace Database\Factories;

use App\Models\ChatSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChatSession>
 */
class ChatSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'session_token' => (string) Str::uuid(),
            'source' => 'launchpad',
            'current_step' => 0,
            'answers' => [],
            'last_activity_at' => now(),
        ];
    }
}
