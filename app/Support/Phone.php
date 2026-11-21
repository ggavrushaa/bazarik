<?php

namespace App\Support;

/** Українські мобільні номери: зберігаємо як +380XXXXXXXXX, показуємо як «+380 67 123 45 67». */
final class Phone
{
    /** «0671234567», «+380 67 123-45-67», «671234567» → «+380671234567»; null, якщо не схоже на номер */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        $national = match (true) {
            strlen($digits) === 12 && str_starts_with($digits, '380') => substr($digits, 3),
            strlen($digits) === 10 && str_starts_with($digits, '0') => substr($digits, 1),
            strlen($digits) === 9 => $digits,
            default => null,
        };

        return $national !== null && $national[0] !== '0' ? '+380'.$national : null;
    }

    /** «+380671234567» → «+380 67 123 45 67»; national: true → «67 123 45 67» */
    public static function format(?string $phone, bool $national = false): string
    {
        $normalized = self::normalize($phone);
        if ($normalized === null) {
            return (string) $phone;
        }

        $d = substr($normalized, 4);
        $local = sprintf('%s %s %s %s', substr($d, 0, 2), substr($d, 2, 3), substr($d, 5, 2), substr($d, 7, 2));

        return $national ? $local : '+380 '.$local;
    }
}
