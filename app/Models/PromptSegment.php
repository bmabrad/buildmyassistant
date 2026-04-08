<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PromptSegment extends Model
{
    protected $fillable = [
        'key',
        'label',
        'category',
        'step_number',
        'content',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'step_number' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBase(Builder $query): Builder
    {
        return $query->where('category', 'base');
    }

    public function scopeStep(Builder $query, int $stepNumber): Builder
    {
        return $query->where('category', 'step')->where('step_number', $stepNumber);
    }

    public function scopeContext(Builder $query): Builder
    {
        return $query->where('category', 'context');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
