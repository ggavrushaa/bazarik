<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeliveryMethod: string implements HasLabel
{
    case NovaPoshtaWarehouse = 'np_warehouse';
    case NovaPoshtaLocker = 'np_locker';
    case NovaPoshtaCourier = 'np_courier';
    case Pickup = 'pickup'; // самовивіз — поки під питанням у прототипі

    public function getLabel(): string
    {
        return match ($this) {
            self::NovaPoshtaWarehouse => 'Нова Пошта: відділення',
            self::NovaPoshtaLocker => 'Нова Пошта: поштомат',
            self::NovaPoshtaCourier => "Нова Пошта: кур'єр",
            self::Pickup => 'Самовивіз',
        };
    }
}
