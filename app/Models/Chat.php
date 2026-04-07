<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'launchpad_messages';

    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'role',
        'content',
        'phase',
        'is_instruction_sheet',
        'created_at',
    ];

    protected $casts = [
        'phase' => 'integer',
        'is_instruction_sheet' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Chat $chat) {
            if (is_null($chat->created_at)) {
                $chat->created_at = now();
            }
        });
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Assistant::class, 'task_id');
    }

    public function toClaudeFormat(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }
}
