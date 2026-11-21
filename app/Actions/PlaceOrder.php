<?php

namespace App\Actions;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Оформлення замовлення з кошика гостя (MobileCheckout / DeskCheckout).
 *
 * У транзакції блокуємо варіанти, ще раз перевіряємо залишок, створюємо замовлення зі знімком
 * товарів і списуємо залишок. Якщо щось закінчилось, поки людина заповнювала форму, —
 * виправляємо кошик і кидаємо помилку валідації `cart` з назвою товару.
 *
 * Доставку покупець платить Новій Пошті при отриманні (або вона безкоштовна від порогу),
 * комісію за післяплату теж бере НП — тому в нашу суму замовлення вони не входять.
 */
class PlaceOrder
{
    public function __construct(private readonly Cart $cart) {}

    /**
     * @param array{
     *     phone: string, name: string, delivery_method: DeliveryMethod, city: string,
     *     warehouse?: ?string, street?: ?string, building?: ?string, apartment?: ?string,
     *     payment_method: PaymentMethod, comment?: ?string, customer_id?: ?int
     * } $data
     */
    public function __invoke(array $data): Order
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Кошик порожній — додайте товари, щоб оформити замовлення.']);
        }

        $order = DB::transaction(function () use ($lines, $data) {
            $variants = ProductVariant::query()
                ->with('product')
                ->whereIn('id', $lines->pluck('variant.id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $problems = [];
            foreach ($lines as $line) {
                $variant = $variants->get($line['variant']->id);
                if ($variant && $variant->product?->is_active && $variant->isAvailable($line['quantity'])) {
                    continue;
                }

                $name = $line['product']->name.($line['variant']->label ? ' ('.$line['variant']->label.')' : '');
                $left = $variant && $variant->is_active && $variant->product?->is_active ? (int) $variant->stock : 0;
                $problems[] = $left > 0
                    ? "Поки ви оформлювали, «{$name}» лишилось тільки {$left} шт — ми змінили кількість у кошику."
                    : "Поки ви оформлювали, «{$name}» закінчився — ми прибрали його з кошика.";

                // Кошик — у сесії, не в БД, тож відкат транзакції його не зачепить
                $variant ? $this->cart->set($variant, $left) : $this->cart->remove($line['variant']->id);
            }

            if ($problems !== []) {
                throw ValidationException::withMessages(['cart' => $problems]);
            }

            [$firstName, $lastName] = $this->splitName($data['name']);
            $courier = $data['delivery_method'] === DeliveryMethod::NovaPoshtaCourier;

            $order = Order::create([
                'customer_id' => $data['customer_id'] ?? null,
                'status' => OrderStatus::New,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $data['phone'],
                'delivery_method' => $data['delivery_method'],
                'city' => $data['city'],
                // TODO(НП API): зберігати np_city_ref і np_warehouse_ref з автодоповнення
                'warehouse' => $courier ? null : ($data['warehouse'] ?? null),
                'street' => $courier ? ($data['street'] ?? null) : null,
                'building' => $courier ? ($data['building'] ?? null) : null,
                'apartment' => $courier ? ($data['apartment'] ?? null) : null,
                'payment_method' => $data['payment_method'],
                'payment_status' => PaymentStatus::Pending,
                'comment' => filled($data['comment'] ?? null) ? $data['comment'] : null,
                'discount' => $this->cart->bundleDiscount(), // «Разом дешевше»
                'delivery_cost' => 0,
            ]);

            foreach ($lines as $line) {
                $variant = $variants[$line['variant']->id];
                $price = (float) $variant->final_price;

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'variant_label' => $variant->label,
                    'price' => $price,
                    'quantity' => $line['quantity'],
                    'total' => $price * $line['quantity'],
                ]);

                $variant->decrement('stock', $line['quantity']);
            }

            $order->recalculateTotals();

            return $order;
        });

        $this->cart->clear();

        return $order->refresh();
    }

    /** «Олена Коваль» → ['Олена', 'Коваль'] */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/u', trim($name), 2);

        return [$parts[0], $parts[1] ?? null];
    }
}
