<?php

namespace App\Livewire;

use App\Actions\Auth\CompleteLogin;
use App\Actions\Auth\SendLoginCode;
use App\Actions\Auth\VerifyLoginCode;
use App\Models\Customer;
use App\Support\Phone;
use Livewire\Attributes\Locked;

/**
 * Спільна логіка трьох кроків входу (телефон → код → ім'я) для сторінок /login… (AuthPage)
 * і десктопної модалки (AuthModal). Різниться лише те, куди переходимо після кроку.
 */
trait AuthSteps
{
    /** Номер, як ввели в поле (без +380) */
    public string $phone = '';

    /** Нормалізований номер, на який надіслали код */
    #[Locked]
    public ?string $sentTo = null;

    public string $code = '';

    public string $fullName = '';

    public bool $viber = false;

    #[Locked]
    public ?string $mergeNotice = null;

    abstract protected function afterCodeSent(): void;

    abstract protected function afterLogin(Customer $customer): void;

    abstract protected function afterNameSaved(): void;

    public function sendCode(): void
    {
        $this->resetErrorBag();
        $this->sentTo = app(SendLoginCode::class)->handle($this->phone, request()->ip());
        $this->code = '';
        $this->afterCodeSent();
    }

    public function resend(): void
    {
        $this->resetErrorBag();
        if (! $this->sentTo) {
            return;
        }
        app(SendLoginCode::class)->handle($this->sentTo, request()->ip(), field: 'code', resend: true);
        $this->code = '';
        $this->dispatch('code-resent', seconds: $this->resendIn());
    }

    /** @param  array<int>  $favorites  обране гостя з localStorage ($store.favorites.ids) */
    public function verify(array $favorites = []): void
    {
        $this->resetErrorBag();
        if (! $this->sentTo) {
            return;
        }

        $customer = app(VerifyLoginCode::class)->handle($this->sentTo, $this->code);
        $merged = app(CompleteLogin::class)->handle($customer, $favorites);
        $this->mergeNotice = CompleteLogin::mergeNotice($merged);

        $this->afterLogin($customer);
    }

    public function saveName(): void
    {
        $this->validate(
            ['fullName' => ['required', 'string', 'min:2', 'max:100']],
            ['fullName.required' => 'Напишіть, як до вас звертатися', 'fullName.min' => 'Напишіть, як до вас звертатися', 'fullName.max' => 'Задовго — до 100 символів'],
        );

        $customer = auth('customer')->user();
        if ($customer) {
            [$first, $last] = array_pad(preg_split('/\s+/u', trim($this->fullName), 2), 2, null);
            $customer->forceFill(['first_name' => $first, 'last_name' => $last, 'viber_opt_in' => $this->viber])->save();
        }
        session()->forget(CompleteLogin::MERGED_KEY);

        $this->afterNameSaved();
    }

    /** Секунди до «Надіслати ще раз» — для таймера в Alpine */
    public function resendIn(): int
    {
        return $this->sentTo ? SendLoginCode::secondsUntilResend($this->sentTo) : 0;
    }

    public function sentToLabel(): string
    {
        return Phone::format($this->sentTo);
    }
}
