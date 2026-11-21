/*
 * Alpine приходить разом із Livewire — тут лише реєструємо наші компоненти.
 */

// Вибір кольору й розміру (шторка/модалка з картки та сторінка товару одягу)
function sizePicker(variants, firstColor = null) {
    return {
        variants,
        color: firstColor,
        size: null,
        needSize: false,

        hasColors() {
            return this.variants.some((v) => v.color);
        },
        hasSizes() {
            return this.variants.some((v) => v.size);
        },
        current(size = this.size) {
            return this.variants.find((v) => (!this.hasColors() || v.color === this.color) && (!this.hasSizes() || v.size === size)) ?? null;
        },
        stockOf(size) {
            return this.current(size)?.stock ?? 0;
        },
        sizeLabel(size) {
            const stock = this.stockOf(size);
            if (stock === 0) return `${size}, немає в наявності`;
            if (stock <= 3) return `${size}, залишилось ${stock}`;
            return size;
        },
        setColor(color) {
            this.color = color;
            if (this.size && this.stockOf(this.size) === 0) this.size = null;
        },
        preset(size) {
            if (size && this.stockOf(size) > 0) this.size = size;
        },
        buttonText() {
            return this.size || !this.hasSizes() ? `В кошик${this.size ? ' · ' + this.size : ''}` : 'Оберіть розмір';
        },
        submit() {
            const variant = this.current();
            if (!variant || variant.stock === 0) {
                this.needSize = true;
                return;
            }
            this.needSize = false;
            Livewire.dispatch('add-variant', { id: variant.id, quantity: 1 });
        },
    };
}

// Обране гостя — на пристрої (localStorage); після входу зіллємо з акаунтом
function favorites() {
    const KEY = 'bz:favorites';
    const read = () => {
        try {
            return JSON.parse(localStorage.getItem(KEY) ?? '[]');
        } catch {
            return [];
        }
    };

    return {
        ids: read(),
        has(id) {
            return this.ids.includes(id);
        },
        toggle(id) {
            this.ids = this.has(id) ? this.ids.filter((x) => x !== id) : [...this.ids, id];
            try {
                localStorage.setItem(KEY, JSON.stringify(this.ids));
            } catch {
                // приватний режим — просто не запам'ятовуємо
            }
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('sizePicker', sizePicker);
    window.Alpine.store('favorites', favorites());
});
