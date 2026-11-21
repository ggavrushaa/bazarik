<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\CheckoutForm;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cart;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $powerbank;

    private ProductVariant $thermos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);

        $this->powerbank = Product::query()->where('slug', 'powerbank-20000')->firstOrFail()->variants()->firstOrFail();
        $this->thermos = Product::query()->where('slug', 'thermos-1l')->firstOrFail()->variants()->firstOrFail();
    }

    private function fillCart(): void
    {
        app(Cart::class)->add($this->powerbank);
        app(Cart::class)->add($this->thermos);
    }

    private function filledForm()
    {
        return Livewire::test(CheckoutForm::class)
            ->set('phone', '067 123-45-67')
            ->set('name', 'Олена Коваль')
            ->set('city', 'Київ')
            ->set('warehouse', 'Відділення № 25');
    }

    public function test_empty_cart_redirects_to_cart(): void
    {
        $this->get('/checkout')->assertRedirect(route('cart'));
    }

    public function test_checkout_page_renders(): void
    {
        $this->fillCart();

        $this->get('/checkout')
            ->assertOk()
            ->assertSee('Оформлення замовлення')
            ->assertSee('1. Контакти')
            ->assertSee('2. Доставка Новою Поштою')
            ->assertSee('3. Оплата')
            ->assertSee('≈ '.money(70).' при отриманні', false)
            ->assertSee('Комісія за післяплату')
            ->assertSee('≈ '.money(47), false)
            ->assertSee('Підтвердити замовлення');
    }

    public function test_validation_errors(): void
    {
        $this->fillCart();

        Livewire::test(CheckoutForm::class)
            ->set('phone', '12345')
            ->set('name', 'Олена')
            ->call('place')
            ->assertHasErrors(['phone', 'name', 'city', 'warehouse'])
            ->assertSee('Перевірте номер')
            ->set('delivery', DeliveryMethod::NovaPoshtaCourier->value)
            ->call('place')
            ->assertHasErrors(['street', 'building'])
            ->assertHasNoErrors('warehouse');

        $this->assertSame(0, Order::count());
    }

    public function test_confirm_button_depends_on_payment(): void
    {
        $this->fillCart();

        Livewire::test(CheckoutForm::class)
            ->assertSee('Підтвердити замовлення')
            ->set('payment', 'wallet')
            ->assertSee('Сплатити '.money(1348), false)
            ->assertDontSee('Комісія за післяплату')
            ->set('payment', 'card')
            ->assertSee('Перейти до оплати · '.money(1348), false)
            ->set('payment', 'cod')
            ->assertSee('Підтвердити замовлення')
            ->assertSee('Комісія за післяплату');
    }

    public function test_cod_order_is_placed(): void
    {
        $this->fillCart();
        $stockBefore = $this->powerbank->stock;

        $component = $this->filledForm()->call('place')->assertHasNoErrors()->assertDispatched('cart-updated');

        $order = Order::with('items')->sole();
        $component->assertRedirect(route('order.success', $order));
        $this->assertStringContainsString($order->public_token, route('order.success', $order));

        $this->assertNull($order->customer_id);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame(PaymentMethod::CashOnDelivery, $order->payment_method);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(DeliveryMethod::NovaPoshtaWarehouse, $order->delivery_method);
        $this->assertSame('+380671234567', $order->phone);
        $this->assertSame(['Олена', 'Коваль'], [$order->first_name, $order->last_name]);
        $this->assertSame(['Київ', 'Відділення № 25'], [$order->city, $order->warehouse]);
        $this->assertEquals(1348, $order->total);
        $this->assertMatchesRegularExpression('/^B-\d{6}$/', $order->number);

        $this->assertCount(2, $order->items);
        $item = $order->items->firstWhere('product_variant_id', $this->powerbank->id);
        $this->assertSame('Павербанк 20 000 мАг, 22.5 Вт, три виходи', $item->name);
        $this->assertSame($this->powerbank->sku, $item->sku);
        $this->assertEquals(899, $item->price);
        $this->assertSame(1, $item->quantity);
        $this->assertEquals(899, $item->total);

        $this->assertSame($stockBefore - 1, $this->powerbank->fresh()->stock);
        $this->assertTrue(app(Cart::class)->isEmpty());

        // Сторінка «Дякуємо» — за токеном, без входу
        $this->get(route('order.success', $order))
            ->assertOk()
            ->assertSee('Дякуємо, замовлення прийнято')
            ->assertSee('№ '.$order->number)
            ->assertSee('+380 67 123 45 67')
            ->assertSee('Нова Пошта, відділення № 25')
            ->assertSee('При отриманні')
            ->assertSee('SMS з номером ТТН')
            ->assertSee(money(1348).' + комісія Нової Пошти', false);
    }

    public function test_online_payment_order_is_pending(): void
    {
        $this->fillCart();

        $this->filledForm()->set('payment', 'card')->call('place')->assertHasNoErrors();

        $order = Order::sole();
        $this->assertSame(PaymentMethod::Online, $order->payment_method);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
    }

    public function test_item_sold_out_while_checking_out(): void
    {
        $this->fillCart();
        $form = $this->filledForm();

        // Хтось інший купив останній павербанк
        $this->powerbank->update(['stock' => 0]);

        $form->call('place')
            ->assertHasErrors('cart')
            ->assertSee('Павербанк 20 000 мАг, 22.5 Вт, три виходи')
            ->assertNoRedirect();

        $this->assertSame(0, Order::count());
        $this->assertSame(30, $this->thermos->fresh()->stock);
        $this->assertSame([$this->thermos->id => 1], app(Cart::class)->raw());
    }

    public function test_unknown_token_is_404(): void
    {
        $this->get('/order/'.fake()->uuid())->assertNotFound();
    }
}
