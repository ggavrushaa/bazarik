{{-- «Контакти»: MobileContacts (до 768px) / DeskContacts (від 768px) --}}
@php
    $phone = config('shop.phone');
    $tel = preg_replace('/[^\d+]/', '', (string) $phone);
    $telHref = strlen($tel) >= 10 ? 'tel:'.$tel : '#tel';
    $schedule = config('shop.hours').' · Нд — вихідний';
    // TODO: shop.legal_address — юридична адреса (на погодженні)
    $legal = config('shop.company').' · [юридична адреса] · '.config('shop.email');
    // TODO: shop.pickup — чи є самовивіз (на погодженні)
    $pickup = 'Самовивозу немає: відправляємо лише Новою Поштою. [уточнити]';
@endphp
@component('pages.info.shell', ['title' => 'Контакти', 'active' => 'info.contacts',
    'description' => 'Viber, Telegram, телефон і графік роботи Bazarik. Відповідаємо до 10 хвилин у робочий час.'])
    @slot('mobile')
        <p style="margin: 0; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--ink-2)">Найшвидше — в месенджерах: відповідаємо до 10 хвилин у робочий час.</p>
        <div style="display: flex; flex-direction: column; gap: 10px">
            <a class="bz-btn bz-btn--secondary bz-btn--l bz-btn--block" href="{{ config('shop.viber') }}"><x-bz.icon name="chat" size="20" />Viber</a>
            <a class="bz-btn bz-btn--secondary bz-btn--l bz-btn--block" href="{{ config('shop.telegram') }}"><x-bz.icon name="chat" size="20" />Telegram</a>
            <a class="bz-btn bz-btn--ghost bz-btn--block" href="{{ $telHref }}"><x-bz.icon name="phone" size="20" />{{ $phone }}</a>
        </div>
        <section style="display: flex; flex-direction: column; gap: 8px; padding: 16px; border-radius: 16px; background: var(--surface); font-size: 14px; line-height: 21px; font-weight: 600; color: var(--ink-2)">
            <b style="font-size: 15px; color: var(--ink)">Графік</b>
            <span>{{ $schedule }}<br>Замовлення на сайті приймаємо цілодобово.</span>
        </section>
        <livewire:contact-form prefix="contact-m" />
        <div style="font-size: 12px; line-height: 18px; font-weight: 600; color: var(--ink-muted)">{{ $legal }}<br>{{ $pickup }}</div>
    @endslot

    <div style="display: flex; flex-direction: column; gap: 12px">
        <h1 class="h1" style="font-size: 40px; line-height: 46px">Контакти</h1>
        <p class="p">Найшвидше — в месенджерах: відповідаємо до 10 хвилин у робочий час.</p>
    </div>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div style="display: flex; flex-direction: column; gap: 16px; padding: 28px; border-radius: var(--radius-xl); background: var(--surface)">
            <div style="display: flex; gap: 10px"><a class="bz-btn bz-btn--dark bz-btn--l" href="{{ config('shop.viber') }}" style="flex: 1">Viber</a><a class="bz-btn bz-btn--dark bz-btn--l" href="{{ config('shop.telegram') }}" style="flex: 1">Telegram</a></div>
            <a href="{{ $telHref }}" style="display: flex; align-items: center; gap: 10px; font-size: 24px; font-weight: 800; color: var(--ink); text-decoration: none"><x-bz.icon name="phone" size="24" />{{ $phone }}</a>
            <div class="hr"></div>
            <b style="font-size: 16px; font-weight: 800">Графік</b>
            <span class="p" style="font-size: 15px">{{ $schedule }}</span>
            <span class="mut">Замовлення на сайті приймаємо цілодобово.</span>
        </div>
        {{-- Друга копія форми для десктопної розмітки (мобільна прихована), id полів — з іншим префіксом --}}
        <livewire:contact-form prefix="contact-d" />
    </section>

    <div style="display: flex; flex-direction: column; gap: 6px; padding-top: 24px; border-top: 1px solid var(--line)">
        <span class="mut" style="font-size: 14px">{{ $legal }}</span>
        <span class="mut" style="font-size: 14px">{{ $pickup }}</span>
    </div>
@endcomponent
