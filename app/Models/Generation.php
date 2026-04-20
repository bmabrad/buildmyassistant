<?php

namespace App\Models;

use Database\Factories\GenerationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lead_id', 'prompt_version_id', 'retry_of_id',
    'product', 'model', 'status',
    'input_payload', 'output', 'raw_response',
    'latency_ms', 'input_tokens', 'output_tokens', 'error',
])]
class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory;

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function promptVersion(): BelongsTo
    {
        return $this->belongsTo(PromptVersion::class);
    }

    public function retryOf(): BelongsTo
    {
        return $this->belongsTo(Generation::class, 'retry_of_id');
    }

    protected function casts(): array
    {
        return [
            'input_payload' => 'array',
            'output' => 'array',
            'latency_ms' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
        ];
    }
}
