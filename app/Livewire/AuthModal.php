<?php

namespace App\Livewire;

use App\Actions\Auth\CompleteLogin;
use App\Actions\Auth\MergeGuestData;
use App\Models\Customer;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Десктопна модалка входу поверх сторінки (DeskAuth → DeskAuthCode → DeskAuthName).
 * Відкривається з будь-якого місця: Livewire.dispatch('open-auth').
 *
 * Рендериться макетом на кожній сторінці, тому заодно синхронізує сердечка для покупця,
 * що увійшов: обране з localStorage ($store.favorites) ↔ customer->favorites у БД.
 */
class AuthModal extends Component
{
    use AuthSteps;

    public bool $open = false;

    /** phone | code | name */
    #[Locked]
    public string $step = 'phone';

    /** Сторінка, на якій відкрили модалку — після входу повертаємося на неї */
    #[Locked]
    public string $returnUrl = '/';

    public function mount(): void
    {
        $this->returnUrl = request()->routeIs('login*') ? route('account') : request()->fullUrl();
    }

    #[On('open-auth')]
    public function show(): void
    {
        if (auth('customer')->check()) {
            $this->redirectRoute('account');

            return;
        }

        $this->resetErrorBag();
        $this->reset('code', 'fullName', 'mergeNotice');
        $this->step = 'phone';
        $this->open = true;
    }

    public function close(): void
    {
        // Уже увійшли (крок з іменем) — перезавантажуємо сторінку, щоб шапка показала кабінет
        if (auth('customer')->check()) {
            $this->redirect($this->returnUrl);

            return;
        }

        $this->open = false;
    }

    public function changeNumber(): void
    {
        $this->resetErrorBag();
        $this->step = 'phone';
    }

    protected function afterCodeSent(): void
    {
        $this->step = 'code';
    }

    protected function afterLogin(Customer $customer): void
    {
        if (CompleteLogin::needsName($customer)) {
            $this->step = 'name';

            return;
        }

        session()->forget(CompleteLogin::MERGED_KEY);
        $this->redirect(session()->pull('url.intended', $this->returnUrl));
    }

    protected function afterNameSaved(): void
    {
        $this->redirect(session()->pull('url.intended', $this->returnUrl));
    }

    /**
     * Сердечка на пристрої змінилися (Alpine $watch на $store.favorites.ids) — переносимо в кабінет.
     *
     * @return array<int> актуальні ids обраного покупця
     */
    public function syncFavorites(array $added = [], array $removed = []): array
    {
        $customer = auth('customer')->user();
        if (! $customer) {
            return [];
        }

        MergeGuestData::attachFavorites($customer, $added);

        $removed = collect($removed)->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->take(200)->all();
        if ($removed) {
            $customer->favorites()->detach($removed);
        }

        return $customer->favorites()->pluck('products.id')->all();
    }

    public function render(): View
    {
        $customer = auth('customer')->user();

        return view('livewire.auth-modal', [
            'customer' => $customer,
            'favoriteIds' => $customer?->favorites()->pluck('products.id')->all() ?? [],
        ]);
    }
}
