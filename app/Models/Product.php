<?php

namespace App\Models;

use App\Enums\ProductBadge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $guarded = ['id', 'rating', 'reviews_count'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'rating' => 'decimal:1',
            'badge' => ProductBadge::class,
            'specs' => 'array',
            'highlights' => 'array',
            'size_chart' => 'array',
            'is_apparel' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->orderBy('sort');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort');
    }

    /** Товар для блоку «Разом дешевше» */
    public function bundleProduct(): BelongsTo
    {
        return $this->belongsTo(self::class, 'bundle_product_id');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->withPivot('sort');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Є хоча б один варіант із залишком */
    public function scopeInStock(Builder $query): void
    {
        $query->whereHas('variants', fn (Builder $q) => $q->where('is_active', true)->where('stock', '>', 0));
    }

    /** Знижка у відсотках для бейджа «−20%» */
    protected function discountPercent(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (! $this->old_price || $this->old_price <= $this->price) {
                return null;
            }

            return (int) round(100 - $this->price / $this->old_price * 100);
        });
    }

    /** «S–XXL» — розміри, які є на складі (для карток одягу) */
    protected function sizeRange(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->is_apparel) {
                return null;
            }

            $order = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
            $sizes = $this->variants
                ->filter(fn (ProductVariant $v) => $v->is_active && $v->stock > 0 && $v->size)
                ->pluck('size')->unique()
                ->sortBy(fn (string $s) => array_search($s, $order, true) ?? 99)
                ->values();

            if ($sizes->isEmpty()) {
                return null;
            }

            return $sizes->count() === 1 ? $sizes->first() : $sizes->first().'–'.$sizes->last();
        });
    }

    /** Сума залишків по активних варіантах */
    protected function totalStock(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->variants->where('is_active', true)->sum('stock'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
