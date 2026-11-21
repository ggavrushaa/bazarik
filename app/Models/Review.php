<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Тримаємо кеш рейтингу в товарі актуальним
        $refresh = function (Review $review): void {
            $product = $review->product;
            $published = $product->reviews()->where('is_published', true);
            $product->forceFill([
                'rating' => $published->avg('rating'),
                'reviews_count' => $published->count(),
            ])->saveQuietly();
        };

        static::saved($refresh);
        static::deleted($refresh);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Відгук від того, хто справді купив товар */
    public function isVerified(): bool
    {
        return $this->order_id !== null;
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }
}
