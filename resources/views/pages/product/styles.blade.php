{{-- Стилі сторінки товару (pp-*). Лише токени з design-system/variables.css; розміри — з екранів --}}
<style>
    .pp { display: flex; flex-direction: column; gap: 24px; padding-top: 12px; }
    .pp-crumbs { gap: 6px; }
    .pp-h2 { font-size: 20px; line-height: 26px; }
    .pp-title { margin: 0; font-size: 22px; line-height: 28px; font-weight: 800; letter-spacing: -0.01em; text-wrap: balance; }
    .pp-rating { font-size: 13px; flex-wrap: wrap; }
    .pp-top { display: flex; flex-direction: column; gap: 24px; }

    /* Галерея: телефон */
    .pp-mgal { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; border-radius: var(--radius-l); background: var(--surface); scrollbar-width: none; }
    .pp-mgal::-webkit-scrollbar { display: none; }
    .pp-mgal__slide { position: relative; flex: 0 0 100%; scroll-snap-align: start; }
    .pp-mgal__slide img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .pp-mthumbs { display: flex; gap: 8px; overflow-x: auto; padding: 2px; }
    .pp-mthumb { position: relative; flex: none; width: 56px; height: 56px; padding: 0; overflow: hidden; border-radius: var(--radius-m); background: var(--surface); border: 2px solid transparent; cursor: pointer; }
    .pp-mthumb[aria-current="true"] { border-color: var(--focus-ring); }
    .pp-mthumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .pp-video { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; border-radius: var(--radius-full); background: var(--ink); color: var(--on-ink); font: 800 13px/1 var(--font-sans); text-decoration: none; }
    a.pp-video:hover { color: var(--on-ink); background: var(--ink-2); }

    /* Галерея: десктоп */
    .pp-dgal { display: flex; flex-direction: column-reverse; gap: 12px; }
    .pp-dthumbs { display: flex; gap: 12px; overflow-x: auto; padding: 2px; }
    .pp-dthumb { flex: none; width: 80px; padding: 0; border: 0; border-radius: var(--radius-m); cursor: pointer; }
    .pp-dthumb[aria-current="true"] { box-shadow: 0 0 0 2px var(--ink); }
    .pp-dthumb:focus-visible { outline: 2px solid var(--focus-ring); outline-offset: 2px; }
    .pp-dmain { border-radius: var(--radius-xl); }

    /* Блок покупки */
    .pp-buy { display: flex; flex-direction: column; gap: 12px; min-width: 0; }
    .pp-buy--apparel { gap: 24px; }
    .pp-head { display: flex; flex-direction: column; gap: 10px; }
    .pp-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; }
    .pp-group { display: flex; flex-direction: column; gap: 10px; }
    .pp-actions { display: flex; gap: 12px; }
    .pp-chart-toggle { border: 0; background: transparent; padding: 0; min-height: 44px; font: 700 14px/20px var(--font-sans); color: var(--brand-ink); text-decoration: underline; cursor: pointer; }
    .pp-promises { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin: 4px 0 0; padding: 14px 4px; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
    .pp-promises > div { display: flex; flex-direction: column; align-items: center; gap: 4px; text-align: center; }
    .pp-promises svg { color: var(--ink); }
    .pp-promises b { font-size: 13px; line-height: 16px; font-weight: 800; }
    .pp-promises span { font-size: 12px; line-height: 16px; font-weight: 600; color: var(--ink-muted); }
    .pp-promise-list { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 12px; }
    .pp-promise-list li { display: flex; gap: 12px; align-items: center; font-size: 15px; line-height: 22px; font-weight: 600; }
    .pp-promise-list svg { flex: none; color: var(--ink); }
    .pp-promise-list b { font-weight: 800; }

    /* Опис / характеристики */
    .pp-highlights { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .pp-highlights > div { display: flex; flex-direction: column; gap: 4px; padding: 14px; border-radius: var(--radius-l); background: var(--surface); }
    .pp-highlights b { font-size: 20px; line-height: 24px; font-weight: 800; }
    .pp-highlights span { font-size: 13px; line-height: 18px; font-weight: 600; color: var(--ink-muted); }
    .pp-details { border-bottom: 1px solid var(--line); padding: 12px 0; }
    .pp-details-boxed .pp-details { padding: 14px 0; }
    .pp-details-boxed .pp-details:first-child { border-top: 1px solid var(--line); }
    .pp-details summary { cursor: pointer; font-size: 15px; font-weight: 800; }
    .pp-details p { margin: 8px 0 0; font-size: 14px; line-height: 21px; font-weight: 500; color: var(--ink-2); }
    .pp-specs th { width: 45%; white-space: normal; color: var(--ink-2); font-weight: 700; }
    .pp-specs td { white-space: normal; font-weight: 700; }

    /* Набір (bundle) */
    .pp-bundle { display: flex; flex-direction: column; gap: 12px; padding: 16px; border-radius: var(--radius-xl); background: var(--surface-warm); }
    .pp-bundle__img { flex: none; width: 72px; height: 72px; border-radius: var(--radius-m); background: var(--bg); }

    /* Відгуки */
    .pp-reviews { display: flex; flex-direction: column; gap: 12px; scroll-margin-top: 16px; }
    .pp-reviews__list { display: grid; gap: 12px; }
    .pp-review { display: flex; flex-direction: column; gap: 8px; padding: 16px; border-radius: var(--radius-l); border: 1px solid var(--line); }
    .pp-review__head { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; font-weight: 800; }
    .pp-review__tag { align-self: flex-start; padding: 4px 8px; border-radius: var(--radius-s); background: var(--surface); font-size: 12px; font-weight: 700; color: var(--ink-2); }
    .pp-review p { margin: 0; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--ink-2); }

    .pp-related { display: flex; flex-direction: column; gap: 16px; }
    .pp-buybar { z-index: 30; margin: 0 -16px; }

    /* Немає в наявності */
    .pp--oos { gap: 14px; }
    .pp-oos { display: flex; flex-direction: column; gap: 14px; }
    .pp-oos__img { aspect-ratio: 1 / 1; border-radius: var(--radius-l); }
    .pp-oos__info { display: flex; flex-direction: column; gap: 14px; }
    .pp-oos__pill { align-self: flex-start; padding: 6px 12px; border-radius: var(--radius-full); background: var(--surface); font-size: 13px; font-weight: 800; color: var(--ink-2); }
    .pp-notify { display: flex; flex-direction: column; gap: 10px; padding: 16px; border-radius: var(--radius-l); background: var(--surface-warm); }
    .pp-notify__title { display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 800; }
    .pp-notify__text { margin: 0; font-size: 14px; line-height: 20px; font-weight: 500; color: var(--ink-2); }
    .pp-notify-form { display: flex; flex-direction: column; gap: 10px; }
    .pp-notify-form .bz-btn { width: 100%; }
    .pp-notify-label { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
    .pp-notify-done { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: var(--radius-m); background: var(--success-soft); color: var(--success-strong); font-size: 14px; line-height: 20px; font-weight: 600; }

    @media (min-width: 768px) {
        .pp { gap: 56px; padding-top: 32px; }
        .pp-crumbs { gap: 8px; }
        .pp-h2 { font-size: 28px; line-height: 34px; }
        .pp-title { font-size: 32px; line-height: 40px; }
        .pp-rating { font-size: 14px; }
        .pp-top { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 32px; align-items: start; margin-top: -32px; }
        .pp-buy, .pp-buy--apparel { gap: 24px; }
        .pp-related { gap: 24px; }

        .pp-info { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 48px; scroll-margin-top: 16px; }
        .pp-info--single { grid-template-columns: minmax(0, 1fr); max-width: 760px; }
        .pp-reviews { display: grid; grid-template-columns: 240px minmax(0, 1fr); gap: 32px; align-items: start; }
        .pp-reviews__list { gap: 16px; }
        .pp-review { gap: 10px; padding: 20px; }
        .pp-review__head { font-size: 15px; }

        .pp--oos { gap: 56px; }
        .pp-oos { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 32px; align-items: start; margin-top: -32px; }
        .pp-oos__img { aspect-ratio: 664 / 560; border-radius: var(--radius-xl); filter: grayscale(.35); opacity: .85; }
        .pp-oos__info { gap: 20px; }
        .pp-notify { gap: 14px; padding: 24px; background: var(--surface); }
        .pp-notify__title { font-size: 18px; }
        .pp-notify__text { font-size: 15px; line-height: 22px; }
        .pp-notify-form { flex-direction: row; align-items: flex-start; }
        .pp-notify-field { flex: 1; min-width: 0; }
        .pp-notify-form .bz-btn { width: auto; padding: 0 24px; margin-top: 26px; }
        .pp-notify-label { position: static; width: auto; height: auto; overflow: visible; clip: auto; }
    }

    @media (min-width: 1024px) {
        .pp-top { grid-template-columns: minmax(0, 664fr) minmax(0, 512fr); gap: 24px; }
        .pp-buy { padding-left: 24px; }
        .pp-dgal.has-thumbs { display: grid; grid-template-columns: 80px minmax(0, 1fr); gap: 24px; align-items: start; }
        .pp-dgal.has-thumbs .pp-dthumbs { flex-direction: column; overflow: visible; padding: 0; }
        .pp-reviews { grid-template-columns: 280px minmax(0, 1fr); gap: 48px; }
        .pp-reviews__list { grid-template-columns: 1fr 1fr; }
        .pp-oos { grid-template-columns: minmax(0, 664fr) minmax(0, 488fr); gap: 48px; }
    }
</style>
