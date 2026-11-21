{{-- Замовлення в кабінеті: AccountOrder (телефон); десктоп — бокове меню кабінету + те саме двома колонками --}}
@php
    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;
    use App\Http\Controllers\AccountController;
    use App\Http\Controllers\TrackingController;

    $steps = TrackingController::timeline($order);
    $deskSteps = TrackingController::timeline($order, transit: true);
    $payment = match (true) {
        $order->payment_method === PaymentMethod::CashOnDelivery => 'При отриманні',
        $order->payment_status === PaymentStatus::Paid => 'Онлайн, оплачено',
        default => 'Онлайн · очікує оплати',
    };
    $details = [
        ['Доставка', AccountController::place($order)],
        ['Отримувач', trim($order->first_name.' '.$order->last_name)],
        ['Оплата', $payment],
        ['Разом', money($order->total)],
    ];
    $h2 = 'margin: 0; font-family: var(--font-display); font-weight: 800; font-size: 20px; line-height: 26px';
@endphp
<x-layouts.app :title="'Замовлення № '.$order->number">
    @include('pages.account.partials.mob-bar', ['back' => route('account.orders'), 'title' => '№ '.$order->number])
    <div class="wrap pt-4! pb-8! md:pt-10! md:pb-[72px]!">
        <div class="md:grid md:grid-cols-[264px_1fr] md:gap-12">
            <div class="only-desk">@include('pages.account.partials.side', ['active' => 'orders'])</div>

            <div style="display: flex; flex-direction: column; gap: 16px; min-width: 0" class="md:gap-6!">
                <div class="only-desk" style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap">
                    <h1 class="h1" style="font-size: 36px; line-height: 42px">Замовлення № {{ $order->number }}</h1>
                    <span class="{{ AccountController::badge($order->status) }}">{{ $order->status->getLabel() }}</span>
                </div>

                <div class="flex flex-col gap-4 md:grid md:grid-cols-[1fr_360px] md:items-start md:gap-10">
                    <div style="display: flex; flex-direction: column; gap: 16px">
                        <div class="only-mob" style="padding: 16px 16px 2px; border-radius: 16px; border: 1px solid var(--line)">
                            @include('pages.account.partials.timeline', ['steps' => $steps, 'lines' => false])
                        </div>
                        <div class="only-desk">
                            @include('pages.account.partials.timeline', ['steps' => $deskSteps, 'desk' => true])
                        </div>

                        <h2 style="{{ $h2 }}">Товари</h2>
                        <div>
                            @foreach ($order->items as $item)
                                <div style="display: flex; gap: 12px; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--line)">
                                    <span class="img" style="width: 56px; height: 56px; border-radius: 10px; flex: none">
                                        @if ($item->product?->mainImage)<img src="{{ $item->product->mainImage->url }}" alt="">@endif
                                    </span>
                                    <span style="flex: 1; font-size: 14px; line-height: 19px; font-weight: 700">
                                        @if ($item->product?->is_active)
                                            <a href="{{ route('product.show', $item->product) }}" style="color: var(--ink); text-decoration: none">{{ $item->name }}</a>
                                        @else
                                            {{ $item->name }}
                                        @endif
                                        <br><span style="color: var(--ink-muted); font-weight: 600">{{ $item->variant_label ? $item->variant_label.' · ' : '' }}{{ $item->quantity }} шт</span>
                                    </span>
                                    <b class="tabular" style="font-size: 15px; white-space: nowrap">{{ money($item->total) }}</b>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 16px">
                        <section style="display: flex; flex-direction: column; gap: 8px; padding: 16px; border-radius: 16px; background: var(--surface)">
                            @foreach ($details as [$label, $value])
                                <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 14px">
                                    <span style="color: var(--ink-muted); font-weight: 600">{{ $label }}</span>
                                    <b style="text-align: right">{{ $value }}</b>
                                </div>
                            @endforeach
                            @if ($order->ttn)
                                <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 14px">
                                    <span style="color: var(--ink-muted); font-weight: 600">ТТН</span>
                                    <b style="text-align: right" class="tabular">{{ $order->ttn }}</b>
                                </div>
                            @endif
                        </section>
                        <livewire:account-reorder :order="$order" label="Повторити замовлення" btn="bz-btn bz-btn--secondary bz-btn--l bz-btn--block" />
                        <a class="bz-btn bz-btn--ghost bz-btn--block" href="{{ link_to_route_or_hash('info.returns') }}">Повернути товар</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
