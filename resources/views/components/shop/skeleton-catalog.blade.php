{{--
    Скелетон сторінки каталогу цілком (StateLoading / DeskLoading): крихти й заголовок, бокові фільтри (десктоп), сітка карток з навісом.
    Наприклад, як заглушка lazy-компонента: public function placeholder() { return view('components.shop.skeleton-catalog'); }
--}}
<div class="pt-5 md:pt-8" aria-busy="true">
<div class="wrap flex flex-col gap-4 md:gap-8">
    <div class="only-desk" style="display: flex; flex-direction: column; gap: 12px">
        <x-shop.skeleton class="h-3.5 w-[200px]" />
        <x-shop.skeleton class="h-10 w-[260px]" />
    </div>
    <div class="flex flex-col md:grid md:grid-cols-[220px_1fr] md:gap-8 lg:grid-cols-[264px_1fr] lg:gap-10">
        <div class="only-desk" style="display: flex; flex-direction: column; gap: 14px">
            @for ($i = 0; $i < 8; $i++)
                <x-shop.skeleton class="h-5 w-full" />
            @endfor
        </div>
        <x-shop.skeleton-grid :count="6" />
    </div>
</div>
</div>
