<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Підбірка товарів («на блекаут», «подарунок до 500 ₴») */
class Collection extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'show_on_home' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('sort')->orderByPivot('sort');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
