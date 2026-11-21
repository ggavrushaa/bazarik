<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $guarded = ['id', 'number', 'public_token'];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'delivery_method' => DeliveryMethod::class,
            'is_quick_buy' => 'boolean',
            'dont_call' => 'boolean',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'delivery_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->public_token ??= (string) Str::uuid();
        });

        // Номер B-100001 будуємо від id, щоб не було колізій
        static::created(function (Order $order): void {
            $order->forceFill(['number' => 'B-'.(100000 + $order->id)])->saveQuietly();
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('total');
        $this->total = max(0, $this->subtotal - $this->discount + $this->delivery_cost);
        $this->save();
    }
}
