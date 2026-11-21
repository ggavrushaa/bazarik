<?php

namespace App\Filament\Support;

use Illuminate\Support\Str;

/**
 * Українські мітки моделей пишемо з малої («товар», щоб «Створити товар»), а заголовок
 * списку й хлібні крихти — з великої лише першу літеру: «Запити про наявність».
 */
trait CapitalizedPluralLabel
{
    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }
}
