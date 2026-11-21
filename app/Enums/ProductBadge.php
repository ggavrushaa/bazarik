<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductBadge: string implements HasLabel
{
    case New = 'new';
    case Hit = 'hit';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Новинка',
            self::Hit => 'Хіт',
        };
    }
}
