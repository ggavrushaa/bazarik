<?php

namespace App\Actions\Auth;

use App\Models\Customer;
use App\Models\Product;
use App\Support\Cart;

/**
 * Після входу переносимо те, що гість зібрав на пристрої:
 *  - кошик лежить у сесії й після входу там і лишається (нічого робити не треба, лише рахуємо);
 *  - обране з localStorage (ids з Alpine $store.favorites) додаємо до customer->favorites.
 */
class MergeGuestData
{
    public function __construct(private readonly Cart $cart) {}

    /**
     * @param  array<int|string>  $favoriteIds
     * @return array{cart: int, favorites: int} скільки перенесли — для плашки на кроці «Як до вас звертатися?»
     */
    public function handle(Customer $customer, array $favoriteIds): array
    {
        return [
            'cart' => $this->cart->lines()->count(),
            'favorites' => self::attachFavorites($customer, $favoriteIds),
        ];
    }

    /** Додає товари в обране покупця; повертає, скільки з них були новими */
    public static function attachFavorites(Customer $customer, array $ids): int
    {
        $ids = collect($ids)->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->take(200);
        if ($ids->isEmpty()) {
            return 0;
        }

        $existing = Product::query()->active()->whereIn('id', $ids)->pluck('id');
        $new = $existing->diff($customer->favorites()->pluck('products.id'))->values();
        if ($new->isEmpty()) {
            return 0;
        }

        $customer->favorites()->attach($new->mapWithKeys(fn (int $id) => [$id => ['created_at' => now()]])->all());

        return $new->count();
    }
}
