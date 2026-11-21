<?php

namespace App\Support;

use App\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Кошик гостя: [variant_id => кількість] у сесії (кука на пристрої).
 * Після входу за телефоном його треба злити з кошиком акаунта (етап «Вхід»).
 *
 * Рядок кошика: ['variant' => ProductVariant, 'product' => Product, 'quantity' => int, 'price' => float, 'total' => float]
 */
class Cart
{
    private const KEY = 'cart.items';

    private ?Collection $lines = null;

    public function __construct(private readonly Session $session) {}

    /** @return array<int, int> */
    public function raw(): array
    {
        return $this->session->get(self::KEY, []);
    }

    /** Додати; повертає фактичну кількість у кошику (не більше залишку) */
    public function add(ProductVariant $variant, int $quantity = 1): int
    {
        $items = $this->raw();

        return $this->set($variant, ($items[$variant->id] ?? 0) + $quantity);
    }

    public function set(ProductVariant $variant, int $quantity): int
    {
        $items = $this->raw();
        $quantity = max(0, min($quantity, $variant->is_active ? $variant->stock : 0));

        if ($quantity === 0) {
            unset($items[$variant->id]);
        } else {
            $items[$variant->id] = $quantity;
        }

        $this->session->put(self::KEY, $items);
        $this->lines = null;

        return $quantity;
    }

    public function remove(int $variantId): void
    {
        $items = $this->raw();
        unset($items[$variantId]);
        $this->session->put(self::KEY, $items);
        $this->lines = null;
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
        $this->lines = null;
    }

    /** @return Collection<int, array> */
    public function lines(): Collection
    {
        if ($this->lines !== null) {
            return $this->lines;
        }

        $items = $this->raw();
        if ($items === []) {
            return $this->lines = collect();
        }

        $variants = ProductVariant::query()
            ->with(['product.mainImage', 'product.variants'])
            ->whereIn('id', array_keys($items))
            ->get()
            ->keyBy('id');

        // Порядок — як додавали; товари, яких уже немає, тихо прибираємо
        return $this->lines = collect($items)
            ->filter(fn (int $qty, int $id) => $variants->has($id) && $variants[$id]->product?->is_active)
            ->map(function (int $qty, int $id) use ($variants) {
                $variant = $variants[$id];
                $price = (float) $variant->final_price;

                return [
                    'variant' => $variant,
                    'product' => $variant->product,
                    'quantity' => $qty,
                    'price' => $price,
                    'old_price' => $variant->product->old_price ? (float) $variant->product->old_price : null,
                    'total' => $price * $qty,
                ];
            })
            ->values();
    }

    public function count(): int
    {
        return (int) $this->lines()->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    public function subtotal(): float
    {
        return (float) $this->lines()->sum('total');
    }

    /** Сума без знижок — для рядка «Знижка» в підсумку */
    public function fullPrice(): float
    {
        return (float) $this->lines()->sum(fn (array $l) => ($l['old_price'] ?? $l['price']) * $l['quantity']);
    }

    public function discount(): float
    {
        return max(0, $this->fullPrice() - $this->subtotal());
    }

    /** Скільки ще додати до безкоштовної доставки (0 — вже безкоштовно) */
    public function freeDeliveryRemaining(): float
    {
        return max(0, config('shop.free_delivery_from') - $this->subtotal());
    }

    /** 0–100 для смужки прогресу */
    public function freeDeliveryProgress(): int
    {
        $from = (float) config('shop.free_delivery_from');

        return $from <= 0 ? 100 : (int) min(100, round($this->subtotal() / $from * 100));
    }
}
