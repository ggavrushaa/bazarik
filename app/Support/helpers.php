<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

if (! function_exists('money')) {
    /** 1499.00 → «1 499 ₴» */
    function money(int|float|string|null $amount): string
    {
        return number_format((float) $amount, 0, ',', "\u{00A0}")."\u{00A0}".config('shop.currency_symbol');
    }
}

if (! function_exists('delivery_date')) {
    /**
     * Коли замовлення буде у відділенні Нової Пошти, якщо оформити його зараз.
     * До config('shop.same_day_cutoff') відправляємо сьогодні, після — наступного дня;
     * у неділю не відправляємо й НП не доставляє. Доставка — 1 день.
     * TODO: брати реальний графік НП для міста покупця.
     */
    function delivery_date(?Carbon $now = null): Carbon
    {
        $now ??= now(config('app.timezone'));
        [$h, $m] = array_map('intval', explode(':', (string) config('shop.same_day_cutoff')));

        $ship = $now->copy()->startOfDay();
        if ($now->copy()->setTime($h, $m)->lte($now) || $ship->isSunday()) {
            $ship->addDay();
        }
        while ($ship->isSunday()) {
            $ship->addDay();
        }

        $arrive = $ship->copy()->addDay();
        while ($arrive->isSunday()) {
            $arrive->addDay();
        }

        return $arrive;
    }
}

if (! function_exists('delivery_label')) {
    /** «Завтра, 13 жовтня» · «Післязавтра, 14 жовтня» · «Понеділок, 17 жовтня» */
    function delivery_label(?Carbon $date = null, bool $lower = false): string
    {
        $date ??= delivery_date();
        $today = now(config('app.timezone'))->startOfDay();
        $days = (int) $today->diffInDays($date->copy()->startOfDay());

        $prefix = match ($days) {
            0 => 'Сьогодні',
            1 => 'Завтра',
            2 => 'Післязавтра',
            default => mb_convert_case($date->translatedFormat('l'), MB_CASE_TITLE),
        };
        $label = $prefix.', '.$date->translatedFormat('j F');

        return $lower ? mb_strtolower(mb_substr($label, 0, 1)).mb_substr($label, 1) : $label;
    }
}

if (! function_exists('plural_uk')) {
    /** plural_uk(41, 'відгук', 'відгуки', 'відгуків') → «41 відгук» */
    function plural_uk(int $n, string $one, string $few, string $many, bool $withNumber = true): string
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        $word = match (true) {
            $mod10 === 1 && $mod100 !== 11 => $one,
            $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) => $few,
            default => $many,
        };

        return $withNumber ? number_format($n, 0, ',', "\u{00A0}").' '.$word : $word;
    }
}

if (! function_exists('rating')) {
    /** 4.8 → «4,8» */
    function rating(int|float|string|null $value): string
    {
        return number_format((float) $value, 1, ',', '');
    }
}

if (! function_exists('link_to_route_or_hash')) {
    /** Посилання на сторінку, якої ще може не бути (інфосторінки, кабінет — наступні етапи) */
    function link_to_route_or_hash(string $name, mixed $parameters = []): string
    {
        return Route::has($name) ? route($name, $parameters) : '#';
    }
}
