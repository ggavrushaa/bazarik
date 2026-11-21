{{-- «Оплата не пройшла»: StatePaymentFailed (до 768px) / DeskPaymentFailed (від 768px — картка 560px з рамкою) --}}
<section class="mx-auto flex w-full max-w-[560px] flex-col gap-[14px] md:items-center md:gap-4 md:rounded-(--radius-xl) md:border md:border-line md:px-10 md:pt-8 md:pb-10 md:text-center">
    <span class="self-center" style="width: 72px; height: 72px; border-radius: 50%; background: #fde7e5; color: var(--sale); display: inline-flex; align-items: center; justify-content: center">
        <x-bz.icon name="card" size="34" stroke="2.2" />
    </span>
    <h1 class="h1 text-center text-[26px] leading-8 md:text-[32px] md:leading-[38px]">Оплата не пройшла</h1>

    @if ($active)
        <p class="p text-center text-[15px]! leading-[22px]! md:text-base! md:leading-6!">Банк відхилив платіж. Гроші не списано, замовлення збережене — товар за вами ще <span style="white-space: nowrap">{{ \App\Livewire\PaymentRetry::remainingLabel($order) }}</span>.</p>
    @else
        <p class="p text-center text-[15px]! leading-[22px]! md:text-base! md:leading-6!">Банк відхилив платіж. Гроші не списано. Час, на який ми тримали товар, минув — напишіть нам, перевіримо, чи він ще є на складі.</p>
    @endif
    <p class="mut tabular" style="margin: 0; text-align: center; font-size: 14px">Замовлення № {{ $order->number }} · {{ money($order->total) }}@if ($active) · <span style="white-space: nowrap">тримаємо до {{ $holdUntil->format('H:i') }}</span>@endif</p>

    @if ($active)
        <div class="flex flex-col gap-[14px] self-stretch md:mt-2 md:gap-2.5" style="margin-top: 8px">
            {{-- TODO: онлайн-оплату (LiqPay / WayForPay / Monobank) ще не підключено — тут буде перехід на сторінку платіжного провайдера --}}
            <a class="bz-btn bz-btn--primary bz-btn--l bz-btn--block only-mob" href="#">Спробувати ще раз</a>
            <a class="bz-btn bz-btn--dark bz-btn--l bz-btn--block only-desk" href="#">Спробувати ще раз</a>
            <button class="bz-btn bz-btn--secondary bz-btn--l bz-btn--block" type="button" wire:click="payOnDelivery" wire:loading.attr="disabled">Оплатити при отриманні</button>
        </div>
    @endif

    <a class="bz-btn bz-btn--ghost bz-btn--block only-mob" href="{{ config('shop.viber') }}"><x-bz.icon name="chat" size="20" />Написати нам</a>
    <a class="only-desk" href="{{ link_to_route_or_hash('info.contacts') }}" style="font-size: 15px; font-weight: 700">Написати нам</a>
</section>
