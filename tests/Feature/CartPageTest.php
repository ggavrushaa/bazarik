<?php

namespace Tests\Feature;

use App\Livewire\CartPage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cart;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    private function variant(string $slug, ?string $size = null): ProductVariant
    {
        $product = Product::query()->where('slug', $slug)->with('variants')->firstOrFail();

        return $size ? $product->variants->where('size', $size)->where('stock', '>', 0)->firstOrFail() : $product->variants->firstOrFail();
    }

    public function test_cart_page_shows_lines_totals_and_free_delivery_hint(): void
    {
        $cart = app(Cart::class);
        $cart->add($this->variant('powerbank-20000'));
        $cart->add($this->variant('thermos-1l'));

        $this->get('/cart')
            ->assertOk()
            ->assertSee('Кошик · 2 товари')
            ->assertSee('Павербанк 20 000 мАг', false)
            ->assertSee('Термос 1 л', false)
            ->assertSee('Додайте ще на '.money(152).' — доставка буде безкоштовною', false)
            ->assertSee('Товари (2)')
            ->assertSee(money(1898), false)
            ->assertSee('−'.money(550), false)
            ->assertSee(money(1348), false)
            ->assertSee('за тарифом Нової Пошти')
            ->assertSee('Часто беруть разом')
            ->assertSee('Оформити замовлення');
    }

    public function test_free_delivery_reached(): void
    {
        app(Cart::class)->add($this->variant('powerbank-30000'));

        Livewire::test(CartPage::class)
            ->assertSee('Доставка буде безкоштовною')
            ->assertDontSee('Додайте ще на')
            ->assertSee('Безкоштовно');
    }

    public function test_stepper_clamps_to_stock(): void
    {
        $l = $this->variant('hoodie-oversize', 'L'); // залишок 2
        app(Cart::class)->add($l);

        $component = Livewire::test(CartPage::class)
            ->call('increment', $l->id)
            ->assertDispatched('cart-updated')
            ->call('increment', $l->id)
            ->call('increment', $l->id);

        $this->assertSame([$l->id => $l->stock], app(Cart::class)->raw());
        $component->assertSeeHtml('aria-label="Більше" wire:click="increment('.$l->id.')" disabled');

        // мінус не опускає нижче одиниці
        $component->call('decrement', $l->id)->call('decrement', $l->id)->call('decrement', $l->id);
        $this->assertSame([$l->id => 1], app(Cart::class)->raw());
    }

    public function test_remove_line(): void
    {
        $powerbank = $this->variant('powerbank-20000');
        $thermos = $this->variant('thermos-1l');
        app(Cart::class)->add($powerbank);
        app(Cart::class)->add($thermos);

        Livewire::test(CartPage::class)
            ->call('remove', $powerbank->id)
            ->assertDispatched('cart-updated')
            ->assertDontSeeHtml('wire:key="line-'.$powerbank->id.'"')
            ->assertSeeHtml('wire:key="line-'.$thermos->id.'"');

        $this->assertSame([$thermos->id => 1], app(Cart::class)->raw());
    }

    public function test_empty_cart(): void
    {
        $this->get('/cart')
            ->assertOk()
            ->assertSee('Прилавок поки порожній')
            ->assertSee('До каталогу')
            ->assertSee('Що беруть цього тижня')
            ->assertDontSee('Оформити замовлення');
    }
}
