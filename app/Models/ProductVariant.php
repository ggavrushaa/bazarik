<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Ціна варіанта або базова ціна товару */
    protected function finalPrice(): Attribute
    {
        return Attribute::get(fn () => $this->price ?? $this->product->price);
    }

    /** «Оливковий, M» */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): ?string => collect([$this->color, $this->size])->filter()->implode(', ') ?: null);
    }

    public function isAvailable(int $quantity = 1): bool
    {
        return $this->is_active && $this->stock >= $quantity;
    }
}
