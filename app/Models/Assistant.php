<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Assistant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'launchpad_tasks';

    protected $fillable = [
        'token',
        'stripe_payment_id',
        'stripe_customer_id',
        'stripe_invoice_url',
        'name',
        'email',
        'status',
        'assistant_name',
        'bottleneck_summary',
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
        static::creating(function (Assistant $assistant) {
            if (empty($assistant->token)) {
                $assistant->token = (string) Str::uuid();
            }
        });

        static::deleting(function (Assistant $assistant) {
            if ($assistant->isForceDeleting()) {
                $assistant->chats()->withTrashed()->forceDelete();
            } else {
                $assistant->chats()->each(fn ($msg) => $msg->delete());
            }
        });

        static::restoring(function (Assistant $assistant) {
            $assistant->chats()->onlyTrashed()->each(fn ($msg) => $msg->restore());
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'task_id')->orderBy('created_at', 'asc');
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
