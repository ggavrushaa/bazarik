<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case New = 'new';               // щойно оформлене
    case Confirmed = 'confirmed';   // підтвердили (дзвінок або автоматично)
    case Packing = 'packing';       // збираємо на складі
    case Shipped = 'shipped';       // передали в Нову Пошту, є ТТН
    case Arrived = 'arrived';       // чекає у відділенні
    case Completed = 'completed';   // отримали
    case Returned = 'returned';     // відмова у відділенні або повернення
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Нове',
            self::Confirmed => 'Підтверджене',
            self::Packing => 'Збираємо',
            self::Shipped => 'Відправлене',
            self::Arrived => 'У відділенні',
            self::Completed => 'Отримане',
            self::Returned => 'Повернення',
            self::Cancelled => 'Скасоване',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Confirmed, self::Packing => 'info',
            self::Shipped, self::Arrived => 'primary',
            self::Completed => 'success',
            self::Returned, self::Cancelled => 'danger',
        };
    }
}
