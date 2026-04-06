<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LaunchpadTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'stripe_payment_id',
        'stripe_customer_id',
        'name',
        'email',
        'status',
        'phase',
        'phase_1_complete',
        'user_id',
    ];

    protected $casts = [
        'phase' => 'integer',
        'phase_1_complete' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (LaunchpadTask $task) {
            if (empty($task->token)) {
                $task->token = (string) Str::uuid();
            }
        });
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LaunchpadMessage::class, 'task_id')->orderBy('created_at', 'asc');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }
}
