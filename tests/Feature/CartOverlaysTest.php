<?php

namespace Tests\Feature;

use App\Livewire\CartBadge;
use App\Livewire\CartOverlays;
use App\Models\Product;
use App\Support\Cart;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartOverlaysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_product_without_variants_goes_straight_to_cart(): void
    {
        $powerbank = Product::query()->where('slug', 'powerbank-20000')->firstOrFail();

        Livewire::test(CartOverlays::class)
            ->dispatch('pick-product', id: $powerbank->id)
            ->assertSet('mode', 'added')
            ->assertSee('Додано в кошик')
            ->assertSee('Додайте ще на '.money(601).' — доставка буде безкоштовною', false)
            ->assertDispatched('cart-updated');

        $this->assertSame(1, app(Cart::class)->count());
    }

    public function test_apparel_opens_size_picker_then_adds_chosen_variant(): void
    {
        $hoodie = Product::query()->where('slug', 'hoodie-oversize')->with('variants')->firstOrFail();
        $m = $hoodie->variants->where('color', 'Оливковий')->firstWhere('size', 'M');

        Livewire::test(CartOverlays::class)
            ->dispatch('pick-product', id: $hoodie->id)
            ->assertSet('mode', 'pick')
            ->assertSee('Таблиця розмірів')
            ->dispatch('add-variant', id: $m->id)
            ->assertSet('mode', 'added')
            ->assertSee('Оливковий · M');

        $this->assertSame([$m->id => 1], app(Cart::class)->raw());
    }

    public function test_sold_out_variant_is_not_added(): void
    {
        $hoodie = Product::query()->where('slug', 'hoodie-oversize')->with('variants')->firstOrFail();
        $xs = $hoodie->variants->firstWhere('size', 'XS');

        Livewire::test(CartOverlays::class)
            ->dispatch('add-variant', id: $xs->id)
            ->assertSet('mode', null)
            ->assertSet('error', 'Цього варіанта вже немає в наявності. Оберіть інший.');

        $this->assertTrue(app(Cart::class)->isEmpty());
    }

    public function test_cart_never_exceeds_stock(): void
    {
        $hoodie = Product::query()->where('slug', 'hoodie-oversize')->with('variants')->firstOrFail();
        $l = $hoodie->variants->where('color', 'Оливковий')->firstWhere('size', 'L'); // 2 шт

        $cart = app(Cart::class);
        $cart->add($l, 5);

        $this->assertSame(2, $cart->count());
    }

    public function test_badge_shows_count_and_total(): void
    {
        $powerbank = Product::query()->where('slug', 'powerbank-20000')->with('variants')->firstOrFail();
        app(Cart::class)->add($powerbank->variants->first(), 2);

        Livewire::test(CartBadge::class, ['variant' => 'desk'])
            ->assertSee(money(1798), false)
            ->assertSee('Кошик: 2 товари', false);
    }
}
