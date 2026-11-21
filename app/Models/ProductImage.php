<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    protected $guarded = ['id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            Str::startsWith($this->path, ['http://', 'https://']) => $this->path,
            Str::startsWith($this->path, '/') => asset(ltrim($this->path, '/')), // файли з public/, напр. демо-фото
            default => Storage::disk('public')->url($this->path),
        });
    }
}
