<?php

namespace App\Livewire;

use App\Models\Order;
use App\Support\Phone;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Component;

/**
 * «Де моє замовлення» (StateTracking): номер замовлення + телефон, з яким оформлювали.
 * Обидва мають збігтися — інакше чужі замовлення можна було б перебирати за номером.
 * Знайшли — ведемо на /track/{public_token}.
 */
class TrackingForm extends Component
{
    public string $number = '';

    public string $phone = '';

    public function mount(): void
    {
        $customer = auth('customer')->user();
        if ($customer) {
            $this->phone = Phone::format($customer->phone, national: true);
        }
    }

    public function find(): void
    {
        $this->resetErrorBag();
        $this->validate(
            ['number' => ['required', 'string', 'max:20'], 'phone' => ['required', 'string', 'max:20']],
            ['number.required' => 'Вкажіть номер замовлення з SMS', 'phone.required' => 'Вкажіть телефон, з яким оформлювали'],
        );

        $key = 'tracking:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('number', 'Забагато спроб. Спробуйте за '.max(1, (int) ceil(RateLimiter::availableIn($key) / 60)).' хв.');

            return;
        }
        RateLimiter::hit($key, 600);

        $phone = Phone::normalize($this->phone);
        if ($phone === null) {
            $this->addError('phone', 'Перевірте номер: 9 цифр після +380');

            return;
        }

        $order = self::lookup($this->number, $phone);
        if (! $order) {
            $this->addError('number', 'Не знайшли замовлення з таким номером і телефоном. Перевірте дані з SMS.');

            return;
        }

        $this->redirectRoute('tracking.show', $order);
    }

    /** «B-100001», «b100001», «100001», «№ 100001» → замовлення з цим номером і телефоном */
    public static function lookup(string $number, string $phone): ?Order
    {
        $digits = preg_replace('/\D+/', '', $number);
        if ($digits === '') {
            return null;
        }

        return Order::query()->where('number', 'B-'.$digits)->where('phone', $phone)->first();
    }

    public function render(): View
    {
        return view('livewire.tracking-form');
    }
}
