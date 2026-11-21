<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cart;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Шари поверх сторінки, спільні для всієї вітрини:
 *  - вибір кольору й розміру з картки товару (StateSizeSheet / DeskSizePicker);
 *  - «Додано в кошик» (StateAddedToCart / DeskAddedToCart).
 * Телефон — шторка знизу, десктоп — модалка / правий drawer. Користувач лишається на сторінці.
 *
 * З будь-якого місця: Livewire.dispatch('pick-product', { id }) — відкрити вибір розміру,
 * Livewire.dispatch('add-product', { id }) — додати товар без варіантів,
 * Livewire.dispatch('add-variant', { id, quantity }) — додати конкретний варіант.
 */
class CartOverlays extends Component
{
    /** null | pick | added */
    #[Locked]
    public ?string $mode = null;

    #[Locked]
    public ?int $productId = null;

    #[Locked]
    public ?int $variantId = null;

    #[Locked]
    public int $quantity = 1;

    public ?string $error = null;

    #[On('pick-product')]
    public function pick(int $id, ?string $size = null): void
    {
        $product = Product::query()->active()->with('variants')->find($id);
        if (! $product) {
            return;
        }

        // Товар без вибору — одразу в кошик
        if ($product->variants->where('is_active', true)->count() <= 1) {
            $this->addProduct($id);

            return;
        }

        $this->reset('variantId', 'error');
        $this->productId = $id;
        $this->mode = 'pick';
        $this->dispatch('picker-opened', size: $size);
    }

    #[On('add-product')]
    public function addProduct(int $id): void
    {
        $variant = ProductVariant::query()
            ->where('product_id', $id)->where('is_active', true)
            ->orderByDesc('stock')->first();

        if ($variant) {
            $this->addVariant($variant->id);
        }
    }

    #[On('add-variant')]
    public function addVariant(int $id, int $quantity = 1): void
    {
        $quantity = max(1, min($quantity, 99));
        $variant = ProductVariant::query()->with('product')->find($id);

        if (! $variant || ! $variant->product?->is_active || ! $variant->isAvailable()) {
            $this->error = 'Цього варіанта вже немає в наявності. Оберіть інший.';

            return;
        }

        $cart = app(Cart::class);
        $before = $cart->raw()[$variant->id] ?? 0;
        $after = $cart->add($variant, $quantity);

        $this->productId = $variant->product_id;
        $this->variantId = $variant->id;
        $this->quantity = max(1, $after - $before);
        $this->error = null;
        $this->mode = 'added';

        $this->dispatch('cart-updated');
    }

    public function close(): void
    {
        $this->reset();
    }

    public function render(Cart $cart): View
    {
        $product = $this->productId
            ? Product::query()->active()->with(['mainImage', 'variants'])->find($this->productId)
            : null;

        return view('livewire.cart-overlays', [
            'product' => $product,
            'variant' => $this->variantId ? $product?->variants->firstWhere('id', $this->variantId) : null,
            'cart' => $cart,
            'related' => $this->mode === 'added' && $product ? $this->related($product) : collect(),
        ]);
    }

    /** «До цього товару беруть» — два інші товари з тієї ж гілки каталогу */
    private function related(Product $product): Collection
    {
        $category = $product->category;
        $ids = collect([$category?->id, $category?->parent_id])->filter();
        $siblings = $category?->parent_id
            ? Category::query()->where('parent_id', $category->parent_id)->pluck('id')
            : collect();

        return Product::query()->active()->inStock()
            ->with('mainImage')
            ->whereIn('category_id', $ids->merge($siblings)->unique())
            ->whereKeyNot($product->id)
            ->orderBy('sort')
            ->limit(2)
            ->get();
    }
}
