<?php

namespace App\Livewire;

use App\Actions\PlaceOrder;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Support\Cart;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Оформлення без акаунта на одній сторінці: MobileCheckout / DeskCheckout.
 * 1. Контакти → 2. Доставка Новою Поштою → 3. Оплата; підсумок і кнопка підтвердження.
 *
 * TODO(НП API): місто й відділення/поштомат зараз — звичайні текстові поля.
 * Коли підключимо API Нової Пошти — автодоповнення міста (Address/searchSettlements)
 * і відділень/поштоматів міста (Address/getWarehouses), зберігати np_city_ref / np_warehouse_ref.
 */
class CheckoutForm extends Component
{
    /** 9 цифр після +380, як вводить людина: «67 123 45 67» */
    public string $phone = '';

    public string $name = '';

    public string $city = '';

    public string $delivery = 'np_warehouse';

    public string $warehouse = '';

    public string $street = '';

    public string $building = '';

    public string $apartment = '';

    /** wallet — Apple Pay / Google Pay, card — карткою онлайн, cod — при отриманні */
    public string $payment = 'cod';

    public bool $withComment = false;

    public string $comment = '';

    public function mount(Cart $cart)
    {
        if ($cart->isEmpty()) {
            return $this->redirectRoute('cart');
        }
    }

    /** «0671234567», «+380 67 123-45-67», «671234567» → «+380671234567»; null, якщо не схоже на номер */
    public static function normalizePhone(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        $digits = match (true) {
            strlen($digits) === 12 && str_starts_with($digits, '380') => substr($digits, 3),
            strlen($digits) === 10 && str_starts_with($digits, '0') => substr($digits, 1),
            default => $digits,
        };

        return preg_match('/^[1-9]\d{8}$/', $digits) ? '+380'.$digits : null;
    }

    /** Після введення показуємо номер як у макеті: «67 123 45 67» */
    public function updatedPhone(string $value): void
    {
        if ($normalized = self::normalizePhone($value)) {
            $d = substr($normalized, 4);
            $this->phone = substr($d, 0, 2).' '.substr($d, 2, 3).' '.substr($d, 5, 2).' '.substr($d, 7, 2);
        }
    }

    protected function rules(): array
    {
        $courier = $this->delivery === DeliveryMethod::NovaPoshtaCourier->value;

        return [
            'phone' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                if (! self::normalizePhone($value)) {
                    $fail('Перевірте номер: після +380 потрібно 9 цифр, наприклад 67 123 45 67');
                }
            }],
            'name' => ['required', 'string', 'max:120', function (string $attribute, mixed $value, Closure $fail) {
                if (count(preg_split('/\s+/u', trim($value))) < 2) {
                    $fail('Вкажіть ім’я та прізвище — посилку видадуть за документом');
                }
            }],
            'city' => ['required', 'string', 'max:120'],
            'delivery' => ['required', Rule::in($this->deliveryOptions())],
            'warehouse' => [Rule::requiredIf(! $courier), 'nullable', 'string', 'max:255'],
            'street' => [Rule::requiredIf($courier), 'nullable', 'string', 'max:255'],
            'building' => [Rule::requiredIf($courier), 'nullable', 'string', 'max:20'],
            'apartment' => ['nullable', 'string', 'max:20'],
            'payment' => ['required', Rule::in(['wallet', 'card', 'cod'])],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'phone.required' => 'Вкажіть телефон — надішлемо SMS з номером ТТН',
            'name.required' => 'Вкажіть ім’я та прізвище отримувача',
            'name.max' => 'Задовге ім’я — до 120 символів',
            'city.required' => 'Вкажіть місто',
            'warehouse.required' => $this->delivery === DeliveryMethod::NovaPoshtaLocker->value ? 'Вкажіть поштомат' : 'Вкажіть відділення',
            'street.required' => 'Вкажіть вулицю',
            'building.required' => 'Вкажіть номер будинку',
            'comment.max' => 'Коментар — до 1000 символів',
        ];
    }

    public function place(PlaceOrder $placeOrder, Cart $cart)
    {
        if ($cart->isEmpty()) {
            return $this->redirectRoute('cart');
        }

        $data = $this->validate();

        $order = $placeOrder([
            'phone' => self::normalizePhone($data['phone']),
            'name' => preg_replace('/\s+/u', ' ', trim($data['name'])),
            'delivery_method' => DeliveryMethod::from($data['delivery']),
            'city' => trim($data['city']),
            'warehouse' => $data['warehouse'] ?? null,
            'street' => $data['street'] ?? null,
            'building' => $data['building'] ?? null,
            'apartment' => $data['apartment'] ?? null,
            'payment_method' => $data['payment'] === 'cod' ? PaymentMethod::CashOnDelivery : PaymentMethod::Online,
            'comment' => $this->withComment ? ($data['comment'] ?? null) : null,
            'customer_id' => auth('customer')->id(),
        ]);

        $this->dispatch('cart-updated');

        // TODO(оплата онлайн): для wallet/card тут редірект на платіжного провайдера
        // (Apple Pay / Google Pay / картка), а з його callback — payment_status Paid або Failed.
        // Якщо оплата не пройшла — StatePaymentFailed: тримаємо замовлення config('shop.payment_hold_hours') год,
        // пропонуємо повторити або оплату при отриманні. Поки провайдера немає — замовлення лишається Pending.
        return $this->redirectRoute('order.success', $order);
    }

    public function render(Cart $cart): View
    {
        $subtotal = $cart->subtotal();
        $total = $cart->total();
        $freeDelivery = $cart->freeDeliveryRemaining() <= 0;

        return view('livewire.checkout-form', [
            'cart' => $cart,
            'lines' => $cart->lines(),
            'subtotal' => $subtotal,
            'bundleDiscount' => $cart->bundleDiscount(),
            'total' => $total,
            'deliveryOptions' => $cards = $this->deliveryCards($freeDelivery),
            'deliveryCost' => $cards[$this->delivery]['cost'] ?? null,
            'codTerms' => rtrim(rtrim(number_format((float) config('shop.cod_fee_percent'), 1, ',', ''), '0'), ',').'% + '.money(config('shop.cod_fee_fixed')),
            'codFee' => round($total * config('shop.cod_fee_percent') / 100 + config('shop.cod_fee_fixed')),
            'confirmLabel' => match ($this->payment) {
                'wallet' => 'Сплатити '.money($total),
                'card' => 'Перейти до оплати · '.money($total),
                default => 'Підтвердити замовлення',
            },
        ]);
    }

    /** @return list<string> */
    private function deliveryOptions(): array
    {
        return [
            DeliveryMethod::NovaPoshtaWarehouse->value,
            DeliveryMethod::NovaPoshtaLocker->value,
            DeliveryMethod::NovaPoshtaCourier->value,
        ];
    }

    /**
     * Картки способів доставки. cost — орієнтовна ціна НП (платить покупець при отриманні), null — безкоштовно.
     * Безкоштовна доставка від порогу — до відділення й поштомата; кур'єр — завжди за тарифом НП.
     */
    private function deliveryCards(bool $freeDelivery): array
    {
        $date = delivery_label();
        $day = Str::before($date, ','); // «Завтра»
        $branch = $freeDelivery ? null : (float) config('shop.delivery_cost');

        return [
            'np_warehouse' => ['title' => 'Відділення', 'mob' => $date, 'desk' => $day, 'cost' => $branch],
            'np_locker' => ['title' => 'Поштомат', 'mob' => $date.' · до 30 кг', 'desk' => $day, 'cost' => $branch],
            'np_courier' => ['title' => 'Кур’єр', 'mob' => $date.', 9:00–21:00', 'desk' => $day, 'cost' => (float) config('shop.courier_cost', 120)],
        ];
    }
}
