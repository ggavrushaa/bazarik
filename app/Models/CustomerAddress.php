<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'delivery_method' => DeliveryMethod::class,
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
