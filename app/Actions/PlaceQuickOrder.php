<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Phone;
use App\Support\Sms\SmsSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * «Купити в 1 клік» (QuickBuyGuest / QuickBuyUser / DeskQuickBuy / DeskQuickBuyUser): одне замовлення
 * на один варіант, кошик у сесії не чіпаємо.
 *
 * Гість лишає ім'я й телефон — доставку менеджер уточнює під час дзвінка, тож поля доставки порожні.
 * Покупець з акаунтом підтверджує збережену адресу (за замовчуванням або останню) й оплату при отриманні.
 *
 * Як і PlaceOrder: у транзакції блокуємо варіант, ще раз перевіряємо залишок, зберігаємо знімок товару й списуємо залишок.
 */
class PlaceQuickOrder
{
    public function __construct(private readonly SmsSender $sms) {}

    /**
     * @param array{
     *     variant_id: int, name: string, phone: string, customer_id?: ?int,
     *     address?: ?CustomerAddress, payment_method?: ?PaymentMethod
     * } $data
     */
    public function __invoke(array $data): Order
    {
        $address = $data['address'] ?? null;

        $order = DB::transaction(function () use ($data, $address) {
            $variant = ProductVariant::query()->with('product')->lockForUpdate()->find($data['variant_id']);

            if (! $variant || ! $variant->product?->is_active || ! $variant->isAvailable()) {
                $name = $variant?->product?->name.($variant?->label ? ' ('.$variant->label.')' : '');
                throw ValidationException::withMessages([
                    'stock' => $variant ? "Поки ви оформлювали, «{$name}» закінчився. Оберіть інший варіант або підпишіться на надходження." : 'Цього товару вже немає.',
                ]);
            }

            [$firstName, $lastName] = self::splitName($data['name']);

            $order = Order::create([
                'customer_id' => $data['customer_id'] ?? null,
                'status' => OrderStatus::New,
                'is_quick_buy' => true,
                'dont_call' => false,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $data['phone'],
                'delivery_method' => $address?->delivery_method,
                'np_city_ref' => $address?->np_city_ref,
                'city' => $address?->city,
                'np_warehouse_ref' => $address?->np_warehouse_ref,
                'warehouse' => $address?->warehouse,
                'street' => $address?->street,
                'building' => $address?->building,
                'apartment' => $address?->apartment,
                'payment_method' => $address ? ($data['payment_method'] ?? PaymentMethod::CashOnDelivery) : null,
                'payment_status' => PaymentStatus::Pending,
                'discount' => 0,
                'delivery_cost' => 0,
            ]);

            $price = (float) $variant->final_price;
            $order->items()->create([
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'name' => $variant->product->name,
                'sku' => $variant->sku,
                'variant_label' => $variant->label,
                'price' => $price,
                'quantity' => 1,
                'total' => $price,
            ]);

            $variant->decrement('stock');
            $order->recalculateTotals();

            return $order;
        });

        $order->refresh();

        // Гостю — підтвердження, що заявку отримали й чекати дзвінка
        if (! $address) {
            $this->sms->send($order->phone, "Bazarik: замовлення {$order->number} прийнято. Менеджер передзвонить на ".Phone::format($order->phone)
                .', щоб уточнити відділення. Графік: '.config('shop.hours'));
        }

        return $order;
    }

    /** «Олена Коваль» → ['Олена', 'Коваль'] */
    public static function splitName(string $name): array
    {
        $parts = preg_split('/\s+/u', trim($name), 2);

        return [$parts[0], $parts[1] ?? null];
    }
}
