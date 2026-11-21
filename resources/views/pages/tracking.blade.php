{{-- «Де моє замовлення»: форма (StateTracking) --}}
<x-layouts.app title="Де моє замовлення">
    @include('pages.account.partials.mob-bar', ['back' => route('home'), 'title' => 'Де моє замовлення'])
    <div class="wrap pt-5! pb-8! md:pt-14! md:pb-[72px]!">
        <div class="md:mx-auto md:flex md:max-w-[480px] md:flex-col md:gap-6">
            <h1 class="h1 only-desk" style="font-size: 36px; line-height: 42px">Де моє замовлення</h1>
            <livewire:tracking-form />
            @guest('customer')
                <p style="margin: 16px 0 0; font-size: 14px; line-height: 20px; font-weight: 600; color: var(--ink-muted)">Є кабінет? <a href="{{ route('login') }}">Увійдіть</a> — усі замовлення й статуси там.</p>
            @endguest
        </div>
    </div>
</x-layouts.app>
