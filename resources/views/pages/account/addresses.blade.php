{{-- Адреси доставки: AccountAddresses (телефон) / DeskAddresses (десктоп) --}}
<x-layouts.app title="Адреси доставки">
    @include('pages.account.partials.mob-bar', ['back' => route('account'), 'title' => 'Адреси доставки'])
    <div class="wrap pt-4! pb-8! md:pt-10! md:pb-[72px]!">
        <div class="md:grid md:grid-cols-[264px_1fr] md:gap-12">
            <div class="only-desk">@include('pages.account.partials.side', ['active' => 'addresses'])</div>
            <div style="display: flex; flex-direction: column; gap: 24px; min-width: 0">
                <h1 class="h1 only-desk" style="font-size: 36px; line-height: 42px">Адреси доставки</h1>
                <livewire:account-addresses />
            </div>
        </div>
    </div>
</x-layouts.app>
