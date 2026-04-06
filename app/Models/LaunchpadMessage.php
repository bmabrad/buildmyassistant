<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaunchpadMessage extends Model
{
    use HasFactory;

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
        static::creating(function (LaunchpadMessage $message) {
            if (is_null($message->created_at)) {
                $message->created_at = now();
            }
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(LaunchpadTask::class, 'task_id');
    }

    public function toClaudeFormat(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }
}
