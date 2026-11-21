<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Livewire\TrackingForm;
use App\Models\Order;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        RateLimiter::clear('tracking:127.0.0.1');

        $this->order = Order::query()->create([
            'status' => OrderStatus::Shipped,
            'first_name' => 'Олена',
            'phone' => '+380671234567',
            'delivery_method' => DeliveryMethod::NovaPoshtaWarehouse,
            'city' => 'Київ',
            'warehouse' => '25',
            'payment_method' => PaymentMethod::CashOnDelivery,
            'ttn' => '20450000001234',
            'shipped_at' => now(),
            'total' => 1348,
        ]);
        $this->order->items()->create(['name' => 'Павербанк 20 000 мАг', 'price' => 899, 'quantity' => 1, 'total' => 899]);
        $this->order->refresh();
    }

    public function test_form_renders(): void
    {
        $this->get('/tracking')->assertOk()->assertSee('Де моє замовлення')->assertSee('Знайти');
    }

    public function test_number_and_phone_must_match(): void
    {
        Livewire::test(TrackingForm::class)
            ->set('number', $this->order->number)
            ->set('phone', '50 111 22 33')
            ->call('find')
            ->assertHasErrors('number')
            ->assertNoRedirect();

        Livewire::test(TrackingForm::class)
            ->set('number', 'B-999999')
            ->set('phone', '67 123 45 67')
            ->call('find')
            ->assertHasErrors('number');
    }

    public function test_finds_order_by_number_and_phone_in_any_format(): void
    {
        $digits = substr($this->order->number, 2);

        foreach ([$this->order->number, $digits, '№ '.$digits] as $number) {
            Livewire::test(TrackingForm::class)
                ->set('number', $number)
                ->set('phone', '067 123-45-67')
                ->call('find')
                ->assertHasNoErrors()
                ->assertRedirect(route('tracking.show', $this->order));
        }
    }

    public function test_lookup_is_throttled(): void
    {
        for ($i = 0; $i < 10; $i++) {
            Livewire::test(TrackingForm::class)->set('number', 'B-1')->set('phone', '671234567')->call('find');
        }

        Livewire::test(TrackingForm::class)
            ->set('number', $this->order->number)
            ->set('phone', '671234567')
            ->call('find')
            ->assertHasErrors('number')
            ->assertNoRedirect();
    }

    public function test_tracking_page_shows_timeline_ttn_and_pickup_date(): void
    {
        $this->get('/track/'.$this->order->public_token)
            ->assertOk()
            ->assertSee('№ '.$this->order->number)
            ->assertSee('Прийнято')
            ->assertSee('Відправлено')
            ->assertSee('У відділенні')
            ->assertSee('ТТН 20450000001234')
            ->assertSee('Відділення № 25, Київ')
            ->assertSee('Буде у відділенні')
            ->assertSee('Відкрити в Новій Пошті')
            ->assertSee('До сплати при отриманні')
            ->assertSee('Павербанк 20 000 мАг');
    }

    public function test_unknown_token_is_404(): void
    {
        $this->get('/track/00000000-0000-0000-0000-000000000000')->assertNotFound();
    }

    public function test_cancelled_order_timeline(): void
    {
        $this->order->update(['status' => OrderStatus::Cancelled]);

        $this->get('/track/'.$this->order->public_token)->assertOk()->assertSee('Скасоване')
            ->assertDontSee('очікується');
    }
}
