{{-- Мої замовлення: телефон — картки (AccountOrders), десктоп — рядки (DeskAccount) --}}
@php
    use App\Http\Controllers\AccountController;
    $tz = config('app.timezone');
    $chips = ['all' => 'Усі', 'active' => 'Активні · '.$activeCount, 'returns' => 'Повернення'];
@endphp
<div style="display: flex; flex-direction: column; gap: 14px" class="md:gap-6!">
    <div class="bz-chips" role="group" aria-label="Фільтр замовлень">
        @foreach ($chips as $key => $label)
            <button class="bz-chip" type="button" wire:click="setFilter('{{ $key }}')" aria-pressed="{{ $current === $key ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
    </div>

    @forelse ($orders as $order)
        @php
            $active = in_array($order->status, AccountController::ACTIVE, true);
            $thumbs = $order->items->map(fn ($i) => $i->product?->mainImage?->url)->filter()->take(3);
            $date = $order->created_at->copy()->tz($tz)->translatedFormat('j F');
        @endphp
        {{-- Телефон --}}
        <article wire:key="om-{{ $order->id }}" class="only-mob" style="display: flex; flex-direction: column; gap: 12px; padding: 16px; border-radius: 16px; border: 1px solid var(--line)">
            <div style="display: flex; justify-content: space-between; align-items: center">
                <span style="display: flex; flex-direction: column">
                    <a href="{{ route('account.order', $order) }}" style="font-size: 15px; font-weight: 800; color: var(--ink); text-decoration: none">№ {{ $order->number }}</a>
                    <span style="font-size: 13px; color: var(--ink-muted); font-weight: 600">{{ $date }}</span>
                </span>
                <span class="{{ AccountController::badge($order->status) }}">{{ $order->status->getLabel() }}</span>
            </div>
            @if ($thumbs->isNotEmpty())
                <div style="display: flex; gap: 6px">
                    @foreach ($thumbs as $url)
                        <span class="img" style="width: 52px; height: 52px; border-radius: 10px"><img src="{{ $url }}" alt="" loading="lazy"></span>
                    @endforeach
                </div>
            @endif
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap">
                <b class="tabular" style="font-size: 17px">{{ money($order->total) }}</b>
                @if ($active)
                    <a class="bz-btn bz-btn--secondary bz-btn--s" href="{{ route('account.order', $order) }}">Відстежити</a>
                @else
                    <livewire:account-reorder :order="$order" btn="bz-btn bz-btn--secondary bz-btn--s" :wire:key="'rm-'.$order->id" />
                @endif
            </div>
        </article>

        {{-- Десктоп --}}
        <article wire:key="od-{{ $order->id }}" class="only-desk" style="display: grid; grid-template-columns: 200px 1fr 120px 150px; gap: 20px; align-items: center; padding: 20px 24px; border: 1px solid var(--line); border-radius: var(--radius-l)">
            <span style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start">
                <a href="{{ route('account.order', $order) }}" style="font-size: 17px; font-weight: 800; color: var(--ink); text-decoration: none">№ {{ $order->number }}</a>
                <span class="mut">{{ $date }}</span>
                <span class="{{ AccountController::badge($order->status) }}">{{ $order->status->getLabel() }}</span>
            </span>
            <span style="display: flex; gap: 8px">
                @foreach ($thumbs as $url)
                    <span class="img" style="width: 64px; height: 64px; border-radius: 10px"><img src="{{ $url }}" alt="" loading="lazy"></span>
                @endforeach
            </span>
            <b class="tabular" style="font-size: 18px; font-weight: 800; text-align: right">{{ money($order->total) }}</b>
            <span style="display: flex; flex-direction: column; gap: 4px">
                @if ($active)
                    <a class="bz-btn bz-btn--dark" href="{{ route('tracking.show', $order) }}">Відстежити</a>
                @else
                    <livewire:account-reorder :order="$order" btn="bz-btn bz-btn--secondary" :wire:key="'rd-'.$order->id" />
                @endif
            </span>
        </article>
    @empty
        <div style="display: flex; flex-direction: column; gap: 12px; align-items: flex-start; padding: 24px 16px; border-radius: 16px; background: var(--surface)">
            <b style="font-size: 16px">{{ $current === 'all' ? 'Замовлень поки немає' : 'Тут поки порожньо' }}</b>
            <span style="font-size: 14px; line-height: 20px; color: var(--ink-2); font-weight: 500">Усе, що замовите, з’явиться тут разом зі статусом доставки.</span>
            <a class="bz-btn bz-btn--primary" href="{{ route('home') }}">До каталогу</a>
        </div>
    @endforelse
</div>
