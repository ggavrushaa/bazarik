{{-- Обране: AccountFavorites. Гостям — плашка «увійдіть, щоб не зникло» і товари з цього пристрою --}}
<div @unless ($customer) x-data x-init="$wire.show(Alpine.store('favorites')?.ids ?? [])" @endunless>
    @include('pages.account.partials.mob-bar', ['back' => $customer ? route('account') : route('home'), 'title' => 'Обране'.($ready && $products->isNotEmpty() ? ' · '.$products->count() : '')])

    <div class="wrap pt-4! pb-8! md:pt-10! md:pb-[72px]!">
        <div @class(['md:grid md:grid-cols-[264px_1fr] md:gap-12' => $customer])>
            @if ($customer)
                <div class="only-desk">@include('pages.account.partials.side', ['active' => 'favorites'])</div>
            @endif

            <div style="display: flex; flex-direction: column; gap: 16px; min-width: 0" class="md:gap-6!">
                <h1 class="h1 only-desk" style="font-size: 36px; line-height: 42px">Обране{{ $ready && $products->isNotEmpty() ? ' · '.$products->count() : '' }}</h1>

                @unless ($customer)
                    <section class="md:max-w-[640px]" style="display: flex; flex-direction: column; gap: 10px; padding: 14px 16px; border-radius: 16px; background: var(--surface-warm)">
                        <b style="font-size: 15px">Обране збережене лише на цьому пристрої</b>
                        <span style="font-size: 14px; line-height: 20px; color: var(--ink-2); font-weight: 500">Увійдіть за номером телефону, щоб воно не зникло й було на всіх пристроях.</span>
                        <a class="bz-btn bz-btn--dark bz-btn--block md:self-start" href="{{ route('login') }}"
                           x-data x-on:click="if (window.matchMedia('(min-width: 768px)').matches) { $event.preventDefault(); Livewire.dispatch('open-auth') }">Увійти</a>
                    </section>
                    <p style="margin: 0; font-size: 13px; line-height: 19px; font-weight: 500; color: var(--ink-muted)">Повідомимо, якщо на збережений товар знизиться ціна.</p>
                @endunless

                @if (! $ready)
                    <div class="grid grid-cols-2 gap-x-3 gap-y-5 md:grid-cols-4 md:gap-5" aria-busy="true">
                        @for ($i = 0; $i < 4; $i++)
                            <div style="aspect-ratio: 3 / 4; border-radius: var(--radius-m); background: var(--surface)"></div>
                        @endfor
                    </div>
                @elseif ($products->isEmpty())
                    <div style="display: flex; flex-direction: column; gap: 12px; align-items: flex-start; padding: 24px 16px; border-radius: 16px; background: var(--surface)">
                        <b style="font-size: 16px">Тут поки порожньо</b>
                        <span style="font-size: 14px; line-height: 20px; color: var(--ink-2); font-weight: 500">Натискайте сердечко на товарі — він збережеться тут.</span>
                        <a class="bz-btn bz-btn--primary" href="{{ route('home') }}">До каталогу</a>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-x-3 gap-y-5 md:grid-cols-3 md:gap-5 lg:grid-cols-4">
                        @foreach ($products as $product)
                            <x-shop.product-card :product="$product" :sizes="true" wire:key="fav-{{ $product->id }}" />
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
