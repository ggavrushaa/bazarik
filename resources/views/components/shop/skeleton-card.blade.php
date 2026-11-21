{{--
    Скелетон картки товару: до 768px — як StateLoading (квадратне фото, 3 рядки),
    від 768px — як DeskLoading (фото 280px, 3 рядки, кнопка 44px).
--}}
<div aria-hidden="true" {{ $attributes->class(['flex flex-col gap-2 md:gap-2.5']) }}>
    <div class="aspect-square rounded-(--radius-l) bg-surface md:aspect-auto md:h-[280px]"></div>
    <div class="h-3.5 w-[90%] rounded-md bg-surface md:h-4 md:rounded-(--radius-s)"></div>
    <div class="h-3.5 w-3/5 rounded-md bg-surface md:h-4 md:rounded-(--radius-s)"></div>
    <div class="h-[22px] w-2/5 rounded-md bg-surface md:h-6 md:rounded-(--radius-s)"></div>
    <div class="hidden h-11 w-full rounded-(--radius-m) bg-surface md:block"></div>
</div>
