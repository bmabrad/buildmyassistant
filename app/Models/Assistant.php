<?php

namespace App\Models;

use Carbon\Carbon;
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
        'playbook_delivered',
        'in_post_playbook',
        'session_completed_at',
        'fast_track_nudge_count',
        'total_input_tokens',
        'total_output_tokens',
        'user_id',
        'flow_state',
    ];

    protected $casts = [
        'phase' => 'integer',
        'flow_state' => 'array',
        'playbook_delivered' => 'boolean',
        'in_post_playbook' => 'boolean',
        'session_completed_at' => 'datetime',
        'fast_track_nudge_count' => 'integer',
        'total_input_tokens' => 'integer',
        'total_output_tokens' => 'integer',
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

    /**
     * A return visit is when the buyer comes back to an existing session
     * that already has messages beyond the initial greeting exchange.
     */
    public function isReturnVisit(): bool
    {
        return $this->chats()->count() > 2;
    }

    public function isPostPlaybook(): bool
    {
        return (bool) $this->playbook_delivered;
    }

    /**
     * The 7-day support window is open if the Playbook has been delivered
     * and fewer than 168 hours (7 days) have passed since delivery.
     */
    public function isSupportWindowOpen(): bool
    {
        if (! $this->session_completed_at) {
            return false;
        }

        return $this->session_completed_at->addDays(7)->isFuture();
    }

    /**
     * Calculate whole days remaining in the support window.
     * Returns null if the Playbook has not been delivered.
     */
    public function supportDaysRemaining(): ?int
    {
        if (! $this->session_completed_at) {
            return null;
        }

        $expiresAt = $this->session_completed_at->addDays(7);
        $hoursLeft = now()->diffInHours($expiresAt, false);

        if ($hoursLeft <= 0) {
            return 0;
        }

        if ($hoursLeft < 24) {
            return -1; // sentinel for "less than 1 day"
        }

        return (int) ceil($hoursLeft / 24);
    }

    public function isTokenLimitReached(): bool
    {
        $limit = (int) config('services.launchpad.token_limit', 0);

        if ($limit === 0) {
            return false;
        }

        return ($this->total_input_tokens + $this->total_output_tokens) >= $limit;
    }

    /**
     * The chat is locked when the support window has expired or the token limit is reached.
     * Pre-Playbook chats are never locked (window hasn't started yet).
     */
    public function isChatLocked(): bool
    {
        if (! $this->session_completed_at) {
            return false;
        }

        if (! $this->isSupportWindowOpen()) {
            return true;
        }

        return $this->isTokenLimitReached();
    }

    /**
     * Returns the reason the chat is locked: 'expired', 'tokens', or null.
     */
    public function lockReason(): ?string
    {
        if (! $this->session_completed_at) {
            return null;
        }

        if (! $this->isSupportWindowOpen()) {
            return 'expired';
        }

        if ($this->isTokenLimitReached()) {
            return 'tokens';
        }

        return null;
    }

    public function recordTokenUsage(int $inputTokens, int $outputTokens): void
    {
        $this->increment('total_input_tokens', $inputTokens);
        $this->increment('total_output_tokens', $outputTokens);
    }
}
