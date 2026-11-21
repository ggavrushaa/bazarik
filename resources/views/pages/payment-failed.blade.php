{{-- «Оплата не пройшла» (StatePaymentFailed / DeskPaymentFailed): скорочена шапка оформлення, без підвалу --}}
<x-layouts.app title="Оплата не пройшла" header="checkout" header-title="Оплата" :header-back="route('home')" :footer="false">
    <div class="pt-10 pb-8 md:pt-16 md:pb-[72px]">
        <div class="wrap">
            <livewire:payment-retry :order="$order" />
        </div>
    </div>
</x-layouts.app>
