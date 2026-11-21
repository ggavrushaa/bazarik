<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\QuickBuyForm;
use App\Livewire\QuickBuyModal;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cart;
use App\Support\Sms\SmsSender;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class QuickBuyTest extends TestCase
{
    use RefreshDatabase;

    private Product $powerbank;

    private ProductVariant $powerbankVariant;

    private Product $hoodie;

    private object $sms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);

        $this->powerbank = Product::query()->where('slug', 'powerbank-20000')->firstOrFail();
        $this->powerbankVariant = $this->powerbank->variants()->firstOrFail();
        $this->hoodie = Product::query()->where('slug', 'hoodie-oversize')->firstOrFail();

        $this->sms = new class implements SmsSender
        {
            /** @var list<array{string, string}> */
            public array $sent = [];

            public function send(string $phone, string $text): void
            {
                $this->sent[] = [$phone, $text];
            }
        };
        $this->app->instance(SmsSender::class, $this->sms);
    }

    public function test_page_renders_for_simple_product(): void
    {
        $this->get('/product/powerbank-20000/quick-buy')
            ->assertOk()
            ->assertSee('Купити в 1 клік')
            ->assertSee($this->powerbank->name)
            ->assertSee('Залиште номер — менеджер передзвонить за 10 хвилин, уточнить відділення й оформить замовлення.')
            ->assertSee('Увійти — і замовлення оформиться без дзвінка');
    }

    public function test_apparel_without_variant_asks_for_size(): void
    {
        $this->get('/product/hoodie-oversize/quick-buy')
            ->assertRedirect(route('product.show', $this->hoodie).'#sizes');
    }

    public function test_apparel_with_variant_shows_colour_and_size(): void
    {
        $variant = $this->hoodie->variants()->where('stock', '>', 0)->whereNotNull('size')->firstOrFail();

        $this->get('/product/hoodie-oversize/quick-buy?variant='.$variant->id)
            ->assertOk()
            ->assertSee($variant->color.' · '.$variant->size);
    }

    public function test_inactive_product_is_404(): void
    {
        $this->powerbank->update(['is_active' => false]);

        $this->get('/product/powerbank-20000/quick-buy')->assertNotFound();
    }

    public function test_guest_places_quick_order(): void
    {
        app(Cart::class)->add(Product::query()->where('slug', 'thermos-1l')->firstOrFail()->variants()->firstOrFail());
        $stock = $this->powerbankVariant->stock;

        Livewire::test(QuickBuyForm::class, ['variantId' => $this->powerbankVariant->id])
            ->set('name', 'Олена')
            ->set('phone', '067 123-45-67')
            ->call('place')
            ->assertHasNoErrors()
            ->assertSee('Дякуємо, замовлення прийнято');

        $order = Order::query()->sole();
        $this->assertTrue($order->is_quick_buy);
        $this->assertFalse($order->dont_call);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame('Олена', $order->first_name);
        $this->assertSame('+380671234567', $order->phone);
        $this->assertNull($order->delivery_method);
        $this->assertNull($order->city);
        $this->assertNull($order->customer_id);
        $this->assertEquals($this->powerbank->price, $order->total);

        $item = $order->items()->sole();
        $this->assertSame($this->powerbank->name, $item->name);
        $this->assertSame(1, (int) $item->quantity);

        $this->assertSame($stock - 1, $this->powerbankVariant->fresh()->stock);
        $this->assertCount(1, app(Cart::class)->lines(), 'кошик не чіпаємо');

        $this->assertCount(1, $this->sms->sent);
        $this->assertSame('+380671234567', $this->sms->sent[0][0]);
        $this->assertStringContainsString($order->number, $this->sms->sent[0][1]);
    }

    public function test_signed_in_customer_confirms_with_default_address(): void
    {
        $customer = Customer::query()->create(['phone' => '+380501112233', 'first_name' => 'Олена', 'last_name' => 'Коваль']);
        $customer->addresses()->create([
            'recipient_name' => 'Інша Людина', 'recipient_phone' => '+380509998877',
            'delivery_method' => DeliveryMethod::NovaPoshtaWarehouse, 'city' => 'Львів', 'warehouse' => '3', 'is_default' => false,
        ]);
        $customer->addresses()->create([
            'recipient_name' => 'Олена Коваль', 'recipient_phone' => '+380501112233',
            'delivery_method' => DeliveryMethod::NovaPoshtaWarehouse, 'city' => 'Київ', 'warehouse' => '25', 'is_default' => true,
        ]);

        $this->actingAs($customer, 'customer');

        $this->get('/product/powerbank-20000/quick-buy')
            ->assertOk()
            ->assertSee('Відділення № 25, Київ')
            ->assertSee('При отриманні')
            ->assertSee('Підтвердити · '.money($this->powerbank->price))
            ->assertDontSee('Увійти — і замовлення оформиться без дзвінка');

        Livewire::test(QuickBuyForm::class, ['variantId' => $this->powerbankVariant->id])
            ->call('place')
            ->assertHasNoErrors()
            ->assertRedirect(route('order.success', Order::query()->sole()));

        $order = Order::query()->sole();
        $this->assertTrue($order->is_quick_buy);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame(['Олена', 'Коваль'], [$order->first_name, $order->last_name]);
        $this->assertSame(DeliveryMethod::NovaPoshtaWarehouse, $order->delivery_method);
        $this->assertSame(['Київ', '25'], [$order->city, $order->warehouse]);
        $this->assertSame(PaymentMethod::CashOnDelivery, $order->payment_method);
        $this->assertSame([], $this->sms->sent);
    }

    public function test_signed_in_without_address_gets_prefilled_guest_form(): void
    {
        $customer = Customer::query()->create(['phone' => '+380501112233', 'first_name' => 'Олена']);
        $this->actingAs($customer, 'customer');

        Livewire::test(QuickBuyForm::class, ['variantId' => $this->powerbankVariant->id])
            ->assertSet('name', 'Олена')
            ->assertSet('phone', '50 111 22 33')
            ->call('place')
            ->assertHasNoErrors();

        $order = Order::query()->sole();
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertNull($order->delivery_method);
    }

    public function test_validation(): void
    {
        Livewire::test(QuickBuyForm::class, ['variantId' => $this->powerbankVariant->id])
            ->call('place')
            ->assertHasErrors(['name' => 'required', 'phone' => 'required'])
            ->set('name', 'Олена')
            ->set('phone', '12345')
            ->call('place')
            ->assertHasErrors('phone')
            ->assertSee('Перевірте номер');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_out_of_stock_shows_error_and_creates_nothing(): void
    {
        $component = Livewire::test(QuickBuyForm::class, ['variantId' => $this->powerbankVariant->id])
            ->set('name', 'Олена')
            ->set('phone', '671234567');

        $this->powerbankVariant->update(['stock' => 0]);

        $component->call('place')
            ->assertHasErrors('stock')
            ->assertSee('закінчився');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, $this->powerbankVariant->fresh()->stock);
    }

    public function test_submissions_are_rate_limited_per_phone(): void
    {
        RateLimiter::clear('quick-buy:phone:+380671234567');

        for ($i = 0; $i < QuickBuyForm::PER_PHONE; $i++) {
            Livewire::test(QuickBuyForm::class, ['variantId' => $this->powerbankVariant->id])
                ->set('name', 'Олена')->set('phone', '671234567')
                ->call('place')->assertHasNoErrors();
        }

        Livewire::test(QuickBuyForm::class, ['variantId' => $this->powerbankVariant->id])
            ->set('name', 'Олена')->set('phone', '671234567')
            ->call('place')
            ->assertHasErrors('stock')
            ->assertSee('Забагато заявок');

        $this->assertSame(QuickBuyForm::PER_PHONE, Order::query()->count());
    }

    public function test_modal_opens_for_variant_and_places_order(): void
    {
        $variant = $this->hoodie->variants()->where('stock', '>', 0)->whereNotNull('size')->firstOrFail();

        Livewire::test(QuickBuyModal::class)
            ->assertDontSee('Купити в 1 клік')
            ->dispatch('open-quick-buy', productId: $this->hoodie->id, variantId: $variant->id)
            ->assertSet('open', true)
            ->assertSee($this->hoodie->name)
            ->assertSee($variant->color.' · '.$variant->size)
            ->set('name', 'Олена')
            ->set('phone', '671234567')
            ->call('place')
            ->assertHasNoErrors()
            ->assertSee('Дякуємо, замовлення прийнято')
            ->call('close')
            ->assertSet('open', false);

        $this->assertSame($variant->label, Order::query()->sole()->items()->sole()->variant_label);
    }

    public function test_product_page_links_to_quick_buy_with_variant(): void
    {
        $this->get('/product/powerbank-20000')
            ->assertOk()
            ->assertSee(route('quick-buy', $this->powerbank).'?variant='.$this->powerbankVariant->id, false)
            ->assertSee('open-quick-buy', false);
    }
}
