<?php

namespace App\Models;

use Database\Factories\PromptVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'product', 'name', 'system_prompt', 'notes', 'is_active',
])]
class PromptVersion extends Model
{
    /** @use HasFactory<PromptVersionFactory> */
    use HasFactory;

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    public static function activeFor(string $product): ?self
    {
        return static::where('product', $product)->where('is_active', true)->latest()->first();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
