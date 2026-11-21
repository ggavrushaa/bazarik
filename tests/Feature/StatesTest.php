<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\PaymentRetry;
use App\Models\Order;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Tests\TestCase;

class StatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    private function order(array $attributes = []): Order
    {
        return Order::query()->create([
            'phone' => '+380671234567',
            'first_name' => 'Олена',
            'payment_method' => PaymentMethod::Online,
            'payment_status' => PaymentStatus::Failed,
            'total' => 1348,
            ...$attributes,
        ])->refresh();
    }

    public function test_unknown_url_shows_closed_stall(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('Ця ятка зачинена')
            ->assertSee('До каталогу')
            ->assertSee('Кухня'); // категорії з шапки (view composer макета) і плитки
    }

    public function test_unknown_product_shows_closed_stall(): void
    {
        $this->get('/product/no-such-product')
            ->assertNotFound()
            ->assertSee('Ця ятка зачинена');
    }

    public function test_loader_and_skeletons_render(): void
    {
        $html = Blade::render('<x-shop.loader /><x-shop.skeleton-catalog />');

        $this->assertStringContainsString('class="bz-loader"', $html);
        $this->assertStringContainsString('Розкладаємо товар…', $html);
        $this->assertStringContainsString('role="status"', $html);
        $this->assertSame(6, substr_count($html, 'md:h-[280px]'));
    }

    public function test_payment_failed_page_shows_order_and_hold_time(): void
    {
        config(['shop.payment_hold_hours' => 2]);
        $this->freezeTime();
        $order = $this->order();

        $this->get(route('order.payment-failed', $order))
            ->assertOk()
            ->assertSee('Оплата не пройшла')
            ->assertSee('товар за вами ще <span style="white-space: nowrap">2 години</span>', false)
            ->assertSee('№ '.$order->number)
            ->assertSee(money(1348), false)
            ->assertSee('Спробувати ще раз')
            ->assertSee('Оплатити при отриманні');

        $this->travel(75)->minutes();
        $this->assertSame('45 хвилин', PaymentRetry::remainingLabel($order));
    }

    public function test_payment_failed_redirects_for_cod_and_paid_orders(): void
    {
        $cod = $this->order(['payment_method' => PaymentMethod::CashOnDelivery, 'payment_status' => PaymentStatus::Pending]);
        $this->get(route('order.payment-failed', $cod))->assertRedirect(route('order.success', $cod));

        $paid = $this->order(['payment_status' => PaymentStatus::Paid]);
        $this->get(route('order.payment-failed', $paid))->assertRedirect(route('order.success', $paid));
    }

    public function test_switch_to_cash_on_delivery_within_hold_window(): void
    {
        $order = $this->order();

        Livewire::test(PaymentRetry::class, ['order' => $order])
            ->call('payOnDelivery')
            ->assertRedirect(route('order.success', $order));

        $order->refresh();
        $this->assertSame(PaymentMethod::CashOnDelivery, $order->payment_method);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
    }

    public function test_cannot_switch_after_hold_window(): void
    {
        config(['shop.payment_hold_hours' => 2]);
        $order = $this->order();
        $this->travel(2)->hours();
        $this->travel(1)->minutes();

        $this->get(route('order.payment-failed', $order))
            ->assertOk()
            ->assertSee('Час, на який ми тримали товар, минув')
            ->assertDontSee('Оплатити при отриманні');

        Livewire::test(PaymentRetry::class, ['order' => $order])
            ->call('payOnDelivery')
            ->assertRedirect(route('order.payment-failed', $order));

        $this->assertSame(PaymentMethod::Online, $order->fresh()->payment_method);
    }
}
