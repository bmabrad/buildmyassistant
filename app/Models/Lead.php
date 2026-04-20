<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id', 'source', 'status',
    'buyer_name', 'buyer_email',
    'business_description', 'business_role', 'who_they_serve',
    'tools_used', 'time_drains', 'tedious_work', 'one_handoff_today',
    'ai_usage_level',
    'business_type', 'assistant_pick',
    'completed_at', 'generated_at', 'delivered_at',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class)->orderByDesc('created_at');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class)->orderByDesc('created_at');
    }

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'generated_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }
}
