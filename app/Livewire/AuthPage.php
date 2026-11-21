<?php

namespace App\Livewire;

use App\Actions\Auth\CompleteLogin;
use App\Models\Customer;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Вхід сторінками (телефон і без JS-модалки): /login → /login/code → /login/name.
 * AuthPhone / AuthCode / AuthName. Номер між кроками — у сесії auth.phone.
 */
class AuthPage extends Component
{
    use AuthSteps;

    public const PHONE_KEY = 'auth.phone';

    /** phone | code | name */
    #[Locked]
    public string $step = 'phone';

    public function mount(string $step = 'phone'): void
    {
        $this->step = $step;

        if ($step === 'code') {
            $this->sentTo = session(self::PHONE_KEY);
        }

        if ($step === 'name') {
            $this->mergeNotice = CompleteLogin::mergeNotice(session(CompleteLogin::MERGED_KEY));
        }

        if ($step === 'phone' && session(self::PHONE_KEY)) {
            // Повернулися «Змінити номер» — підставляємо попередній
            $this->phone = substr((string) session(self::PHONE_KEY), 4);
        }
    }

    protected function afterCodeSent(): void
    {
        session()->put(self::PHONE_KEY, $this->sentTo);
        $this->redirectRoute('login.code');
    }

    protected function afterLogin(Customer $customer): void
    {
        session()->forget(self::PHONE_KEY);

        if (CompleteLogin::needsName($customer)) {
            $this->redirectRoute('login.name');

            return;
        }

        session()->forget(CompleteLogin::MERGED_KEY);
        $this->redirectIntended(route('account'));
    }

    protected function afterNameSaved(): void
    {
        $this->redirectIntended(route('account'));
    }

    public function render(): View
    {
        return view('livewire.auth-page');
    }
}
