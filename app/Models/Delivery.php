<?php

namespace App\Models;

use Database\Factories\DeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lead_id', 'channel', 'provider', 'provider_message_id',
    'to_email', 'subject', 'status', 'attachments', 'error', 'sent_at',
])]
class Delivery extends Model
{
    /** @use HasFactory<DeliveryFactory> */
    use HasFactory;

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'sent_at' => 'datetime',
        ];
    }
}
