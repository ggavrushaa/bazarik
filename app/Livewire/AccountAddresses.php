<?php

namespace App\Livewire;

use App\Enums\DeliveryMethod;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Адреси доставки (AccountAddresses / DeskAddresses): додати, змінити, видалити, зробити основною.
 * Основна адреса підставляється в «Купити в 1 клік». Основна завжди одна.
 */
class AccountAddresses extends Component
{
    /** null — форма закрита; 0 — нова адреса; інакше id адреси */
    #[Locked]
    public ?int $editing = null;

    public string $delivery = 'np_warehouse';

    public string $city = '';

    public string $warehouse = '';

    public string $street = '';

    public string $building = '';

    public string $apartment = '';

    public string $recipientName = '';

    public string $recipientPhone = '';

    public bool $isDefault = false;

    public function create(): void
    {
        $customer = $this->customer();
        $this->resetErrorBag();
        $this->reset('delivery', 'city', 'warehouse', 'street', 'building', 'apartment');
        $this->recipientName = $customer->name !== $customer->phone ? $customer->name : '';
        $this->recipientPhone = Phone::format($customer->phone, national: true);
        $this->isDefault = ! $customer->addresses()->exists();
        $this->editing = 0;
    }

    public function edit(int $id): void
    {
        $address = $this->find($id);
        $this->resetErrorBag();
        $this->editing = $address->id;
        $this->delivery = $address->delivery_method->value;
        $this->city = (string) $address->city;
        $this->warehouse = (string) $address->warehouse;
        $this->street = (string) $address->street;
        $this->building = (string) $address->building;
        $this->apartment = (string) $address->apartment;
        $this->recipientName = (string) $address->recipient_name;
        $this->recipientPhone = Phone::format($address->recipient_phone, national: true);
        $this->isDefault = $address->is_default;
    }

    public function cancel(): void
    {
        $this->editing = null;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        if ($this->editing === null) {
            return;
        }

        $courier = $this->delivery === DeliveryMethod::NovaPoshtaCourier->value;
        $this->validate([
            'delivery' => ['required', Rule::in([DeliveryMethod::NovaPoshtaWarehouse->value, DeliveryMethod::NovaPoshtaLocker->value, DeliveryMethod::NovaPoshtaCourier->value])],
            'city' => ['required', 'string', 'max:100'],
            'warehouse' => [$courier ? 'nullable' : 'required', 'string', 'max:255'],
            'street' => [$courier ? 'required' : 'nullable', 'string', 'max:255'],
            'building' => [$courier ? 'required' : 'nullable', 'string', 'max:20'],
            'apartment' => ['nullable', 'string', 'max:20'],
            'recipientName' => ['required', 'string', 'min:2', 'max:100'],
            'recipientPhone' => ['required', function (string $attr, mixed $value, \Closure $fail) {
                if (Phone::normalize($value) === null) {
                    $fail('Перевірте номер: 9 цифр після +380');
                }
            }],
        ], [
            'city.required' => 'Вкажіть місто',
            'warehouse.required' => $this->delivery === DeliveryMethod::NovaPoshtaLocker->value ? 'Вкажіть поштомат' : 'Вкажіть відділення',
            'street.required' => 'Вкажіть вулицю',
            'building.required' => 'Вкажіть будинок',
            'recipientName.required' => 'Вкажіть ім’я та прізвище отримувача',
            'recipientName.min' => 'Вкажіть ім’я та прізвище отримувача',
            'recipientPhone.required' => 'Вкажіть телефон отримувача',
        ]);

        $customer = $this->customer();
        $address = $this->editing ? $this->find($this->editing) : new CustomerAddress(['customer_id' => $customer->id]);

        $address->fill([
            'delivery_method' => $this->delivery,
            'city' => trim($this->city),
            'warehouse' => $courier ? null : trim($this->warehouse),
            'street' => $courier ? trim($this->street) : null,
            'building' => $courier ? trim($this->building) : null,
            'apartment' => $courier ? (trim($this->apartment) ?: null) : null,
            'recipient_name' => trim($this->recipientName),
            'recipient_phone' => Phone::normalize($this->recipientPhone),
        ]);

        DB::transaction(function () use ($customer, $address) {
            $address->is_default = $this->isDefault || ! $customer->addresses()->whereKeyNot($address->id ?? 0)->exists();
            $address->save();
            if ($address->is_default) {
                self::makeDefault($customer, $address);
            } else {
                self::ensureDefault($customer);
            }
        });

        $this->editing = null;
    }

    public function makePrimary(int $id): void
    {
        $address = $this->find($id);
        DB::transaction(fn () => self::makeDefault($this->customer(), $address));
    }

    public function delete(int $id): void
    {
        $address = $this->find($id);
        DB::transaction(function () use ($address) {
            $address->delete();
            self::ensureDefault($this->customer());
        });

        if ($this->editing === $id) {
            $this->editing = null;
        }
    }

    /** Робимо адресу основною, з усіх інших знімаємо позначку */
    public static function makeDefault(Customer $customer, CustomerAddress $address): void
    {
        $customer->addresses()->whereKeyNot($address->id)->where('is_default', true)->update(['is_default' => false]);
        if (! $address->is_default) {
            $address->forceFill(['is_default' => true])->save();
        }
    }

    /** Якщо основної не лишилося (видалили чи зняли позначку) — основною стає найновіша */
    public static function ensureDefault(Customer $customer): void
    {
        if (! $customer->addresses()->where('is_default', true)->exists()) {
            $customer->addresses()->latest('id')->first()?->forceFill(['is_default' => true])->save();
        }
    }

    private function customer(): Customer
    {
        $customer = auth('customer')->user();
        abort_unless($customer, 403);

        return $customer;
    }

    private function find(int $id): CustomerAddress
    {
        return $this->customer()->addresses()->findOrFail($id);
    }

    public function render(): View
    {
        return view('livewire.account-addresses', [
            'addresses' => $this->customer()->addresses()->orderByDesc('is_default')->latest('id')->get(),
        ]);
    }
}
