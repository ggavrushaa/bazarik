{{-- Мої замовлення: AccountOrders (телефон) / DeskAccount без блоку обраного (десктоп) --}}
<x-layouts.app title="Мої замовлення">
    @include('pages.account.partials.mob-bar', ['back' => route('account'), 'title' => 'Мої замовлення'])
    <div class="wrap pt-4! pb-8! md:pt-10! md:pb-[72px]!">
        <div class="md:grid md:grid-cols-[264px_1fr] md:gap-12">
            <div class="only-desk">@include('pages.account.partials.side', ['active' => 'orders'])</div>
            <div style="display: flex; flex-direction: column; gap: 24px; min-width: 0">
                <h1 class="h1 only-desk" style="font-size: 36px; line-height: 42px">Мої замовлення</h1>
                <livewire:account-orders />
            </div>
        </div>
    </div>
</x-layouts.app>
