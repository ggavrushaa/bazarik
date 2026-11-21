<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case CashOnDelivery = 'cod';   // накладений платіж — оплата у відділенні після огляду
    case Online = 'online';        // Apple Pay / Google Pay / картка

    public function getLabel(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'При отриманні',
            self::Online => 'Онлайн: Apple Pay, Google Pay, картка',
        };
    }
}
