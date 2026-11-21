<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Сторінка кошика: MobileCart / DeskCart, порожній — StateCartEmpty / DeskCartEmpty.
 * Кількість не більша за залишок (Cart::set обрізає), «Часто беруть разом» додає через CartOverlays.
 */
class CartPage extends Component
{
    public function increment(int $variantId, Cart $cart): void
    {
        $this->change($cart, $variantId, +1);
    }

    public function decrement(int $variantId, Cart $cart): void
    {
        $this->change($cart, $variantId, -1);
    }

    public function remove(int $variantId, Cart $cart): void
    {
        $cart->remove($variantId);
        $this->dispatch('cart-updated');
    }

    /** Товар додали з крос-селу (CartOverlays) — перемальовуємо */
    #[On('cart-updated')]
    public function refreshCart(): void {}

    public function render(Cart $cart): View
    {
        $lines = $cart->lines();

        return view('livewire.cart-page', [
            'cart' => $cart,
            'lines' => $lines,
            'crossSell' => $lines->isEmpty() ? collect() : $this->crossSell($lines),
            'popular' => $lines->isEmpty() ? $this->popular() : collect(),
            'catalogUrl' => $this->catalogUrl(),
        ]);
    }

    private function change(Cart $cart, int $variantId, int $delta): void
    {
        $line = $cart->lines()->firstWhere('variant.id', $variantId);
        if (! $line) {
            return;
        }

        // Мінус на одиниці не видаляє — для цього є «Видалити»
        $cart->set($line['variant'], max(1, $line['quantity'] + $delta));
        $this->dispatch('cart-updated');
    }

    /** «Часто беруть разом»: товари в наявності з тих самих гілок каталогу, яких ще немає в кошику */
    private function crossSell(Collection $lines): Collection
    {
        $inCart = $lines->pluck('product.id')->unique();
        $categories = $lines->pluck('product.category_id')->filter()->unique();

        $base = fn () => Product::query()->active()->inStock()
            ->with(['mainImage', 'variants'])
            ->whereNotIn('id', $inCart)
            ->orderBy('sort');

        $products = $base()->whereIn('category_id', $categories)->limit(4)->get();
        if ($products->count() < 4) {
            $products = $products->concat($base()->whereNotIn('id', $products->pluck('id'))->limit(4 - $products->count())->get());
        }

        return $products;
    }

    /** «Що беруть цього тижня» для порожнього кошика — як на головній */
    private function popular(): Collection
    {
        $collection = ProductCollection::query()->active()->where('show_on_home', true)->orderBy('sort')->first();

        return ($collection ? $collection->products() : Product::query()->orderBy('sort'))
            ->active()->with(['mainImage', 'variants'])->limit(4)->get();
    }

    private function catalogUrl(): string
    {
        $category = Category::query()->active()->root()->orderBy('sort')->first();

        return $category ? route('catalog', $category) : route('home');
    }
}
