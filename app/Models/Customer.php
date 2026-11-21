<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Покупець. Вхід — номер телефону + код з SMS, пароля немає. */
class Customer extends Authenticatable
{
    use Notifiable;

    protected $guarded = ['id', 'phone_verified_at'];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return ['phone_verified_at' => 'datetime', 'viber_opt_in' => 'boolean'];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withPivot('created_at');
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->first_name} {$this->last_name}") ?: $this->phone);
    }

    // Пароля немає — Laravel усе одно питає це поле в Authenticatable
    public function getAuthPassword(): string
    {
        return '';
    }
}
