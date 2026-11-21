<?php

namespace App\Actions\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

/** Код підтверджено: входимо (запам'ятовуємо на пристрої), оновлюємо сесію, зливаємо дані гостя. */
class CompleteLogin
{
    public const MERGED_KEY = 'auth.merged';

    public function __construct(private readonly MergeGuestData $merge) {}

    /** @return array{cart: int, favorites: int} */
    public function handle(Customer $customer, array $favoriteIds = []): array
    {
        Auth::guard('customer')->login($customer, remember: true);
        session()->regenerate();

        $merged = $this->merge->handle($customer, $favoriteIds);
        session()->put(self::MERGED_KEY, $merged);

        return $merged;
    }

    /** Новому покупцю спершу показуємо крок з іменем */
    public static function needsName(Customer $customer): bool
    {
        return blank($customer->first_name);
    }

    /** «Перенесли в кабінет 2 товари з кошика й 3 з обраного на цьому пристрої»; null — нічого не переносили */
    public static function mergeNotice(?array $merged): ?string
    {
        $cart = (int) ($merged['cart'] ?? 0);
        $favorites = (int) ($merged['favorites'] ?? 0);

        $parts = array_filter([
            $cart ? plural_uk($cart, 'товар', 'товари', 'товарів').' з кошика' : null,
            $favorites ? ($cart ? $favorites : plural_uk($favorites, 'товар', 'товари', 'товарів')).' з обраного' : null,
        ]);

        return $parts ? 'Перенесли в кабінет '.implode(' й ', $parts).' на цьому пристрої' : null;
    }
}
