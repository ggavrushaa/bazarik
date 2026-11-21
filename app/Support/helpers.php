<?php

if (! function_exists('money')) {
    /** 1499.00 → «1 499 ₴» */
    function money(int|float|string|null $amount): string
    {
        return number_format((float) $amount, 0, ',', "\u{00A0}")."\u{00A0}".config('shop.currency_symbol');
    }
}
