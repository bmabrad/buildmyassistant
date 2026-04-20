<?php

namespace App\Models;

use Database\Factories\ChatSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'session_token', 'lead_id', 'source',
    'current_step', 'answers', 'last_activity_at',
])]
class ChatSession extends Model
{
    /** @use HasFactory<ChatSessionFactory> */
    use HasFactory;

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'last_activity_at' => 'datetime',
            'current_step' => 'integer',
        ];
    }
}
