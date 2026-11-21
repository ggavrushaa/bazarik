<?php

namespace App\Livewire;

use App\Actions\PlaceQuickOrder;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Phone;
use Closure;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * «Купити в 1 клік» на окремій сторінці (телефон і без модалки): QuickBuyGuest / QuickBuyUser.
 * QuickBuyModal — те саме поверх сторінки товару на десктопі (DeskQuickBuy / DeskQuickBuyUser).
 *
 * Гість: ім'я + телефон → менеджер передзвонить. Покупець з акаунтом і збереженою адресою — одна кнопка «Підтвердити».
 */
class QuickBuyForm extends Component
{
    /** Скільки заявок за 10 хвилин з однієї IP-адреси та за годину на один номер */
    public const PER_IP = 5;

    public const PER_PHONE = 3;

    #[Locked]
    public ?int $variantId = null;

    public string $name = '';

    /** 9 цифр після +380, як вводить людина: «67 123 45 67» */
    public string $phone = '';

    /** Номер створеного замовлення гостя — показуємо підтвердження на місці */
    #[Locked]
    public ?string $placed = null;

    public function mount(?int $variantId = null): void
    {
        $this->variantId = $variantId;
        $this->prefill();
    }

    protected function prefill(): void
    {
        $customer = $this->customer();
        if ($customer && $this->name === '' && $this->phone === '') {
            $this->name = trim("{$customer->first_name} {$customer->last_name}");
            $this->phone = Phone::format($customer->phone, national: true);
        }
    }

    public function updatedPhone(string $value): void
    {
        if (Phone::normalize($value)) {
            $this->phone = Phone::format($value, national: true);
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                if (! Phone::normalize($value)) {
                    $fail('Перевірте номер: після +380 потрібно 9 цифр, наприклад 67 123 45 67');
                }
            }],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Вкажіть ім’я — так менеджер до вас звернеться',
            'name.max' => 'Задовге ім’я — до 120 символів',
            'phone.required' => 'Вкажіть телефон — менеджер передзвонить на нього',
        ];
    }

    public function place(PlaceQuickOrder $placeQuickOrder)
    {
        $variant = $this->variant();
        abort_unless($variant, 404);

        $customer = $this->customer();
        $address = $customer ? $this->address($customer) : null;

        if ($address) {
            $name = $address->recipient_name;
            $phone = Phone::normalize($address->recipient_phone) ?? $customer->phone;
        } else {
            $data = $this->validate();
            $name = preg_replace('/\s+/u', ' ', trim($data['name']));
            $phone = Phone::normalize($data['phone']);
        }

        $this->throttle($phone);

        $order = $placeQuickOrder([
            'variant_id' => $variant->id,
            'name' => $name,
            'phone' => $phone,
            'customer_id' => $customer?->id,
            'address' => $address,
            'payment_method' => PaymentMethod::CashOnDelivery,
        ]);

        // З адресою — звичайне «Замовлення прийнято»; гостю — підтвердження тут же: доставку уточнить менеджер
        if ($address) {
            return $this->redirectRoute('order.success', $order);
        }

        $this->placed = $order->number;
    }

    /** Захист від повторних і автоматичних заявок: на IP і на номер */
    private function throttle(string $phone): void
    {
        $keys = [
            ['quick-buy:ip:'.request()->ip(), self::PER_IP, 600],
            ['quick-buy:phone:'.$phone, self::PER_PHONE, 3600],
        ];

        foreach ($keys as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));
                throw ValidationException::withMessages([
                    'stock' => "Забагато заявок поспіль. Спробуйте за {$minutes} хв або зателефонуйте нам: ".config('shop.phone'),
                ]);
            }
        }

        foreach ($keys as [$key, , $decay]) {
            RateLimiter::hit($key, $decay);
        }
    }

    protected function customer(): ?Customer
    {
        $user = auth('customer')->user();

        return $user instanceof Customer ? $user : null;
    }

    /** Адреса за замовчуванням, інакше — остання збережена */
    protected function address(Customer $customer): ?CustomerAddress
    {
        return $customer->addresses()->orderByDesc('is_default')->latest('id')->first();
    }

    protected function variant(): ?ProductVariant
    {
        if (! $this->variantId) {
            return null;
        }

        return ProductVariant::query()
            ->with(['product.mainImage'])
            ->whereKey($this->variantId)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->first();
    }

    /** Дані для шаблону: товар, адреса, підсумки */
    protected function viewData(): array
    {
        $variant = $this->variant();
        $customer = $this->customer();
        $address = $customer ? $this->address($customer) : null;

        return [
            'variant' => $variant,
            'product' => $variant?->product,
            'available' => $variant?->isAvailable() ?? false,
            'address' => $address,
            'addressLine' => $address ? self::addressLine($address) : null,
            'order' => $this->placed ? Order::query()->where('number', $this->placed)->first() : null,
        ];
    }

    /** «Відділення № 25, Київ» · «Поштомат № 3021, Львів» · «вул. Січових Стрільців, 12, кв. 4, Київ» */
    public static function addressLine(CustomerAddress $address): string
    {
        $point = (string) $address->warehouse;
        $place = match ($address->delivery_method) {
            DeliveryMethod::NovaPoshtaCourier => collect([$address->street, $address->building, $address->apartment ? 'кв. '.$address->apartment : null])->filter()->implode(', '),
            DeliveryMethod::NovaPoshtaLocker => ctype_digit($point) ? 'Поштомат № '.$point : $point,
            default => ctype_digit($point) ? 'Відділення № '.$point : $point,
        };

        return collect([$place, $address->city])->filter()->implode(', ');
    }

    public function render(): View
    {
        return view('livewire.quick-buy-form', $this->viewData());
    }
}
