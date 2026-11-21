<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Livewire\AccountAddresses;
use App\Livewire\AccountFavorites;
use App\Livewire\AccountOrders;
use App\Livewire\AccountReorder;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cart;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);

        $this->customer = Customer::query()->create(['phone' => '+380671234567', 'first_name' => 'Олена', 'last_name' => 'Коваль']);
    }

    private function variant(): ProductVariant
    {
        return ProductVariant::query()->where('is_active', true)->where('stock', '>', 2)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))->firstOrFail();
    }

    private function order(?Customer $customer, OrderStatus $status = OrderStatus::Shipped, ?ProductVariant $variant = null): Order
    {
        $variant ??= $this->variant();
        $order = Order::query()->create([
            'customer_id' => $customer?->id,
            'status' => $status,
            'first_name' => 'Олена',
            'last_name' => 'Коваль',
            'phone' => $customer?->phone ?? '+380501112233',
            'delivery_method' => DeliveryMethod::NovaPoshtaWarehouse,
            'city' => 'Київ',
            'warehouse' => '25',
            'payment_method' => PaymentMethod::CashOnDelivery,
            'ttn' => $status === OrderStatus::Shipped ? '20450000001234' : null,
            'shipped_at' => $status === OrderStatus::Shipped ? now() : null,
        ]);
        $order->items()->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'name' => 'Знімок назви товару',
            'variant_label' => $variant->label,
            'price' => 449,
            'quantity' => 2,
            'total' => 898,
        ]);
        $order->recalculateTotals();

        return $order->refresh();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/account', '/account/orders', '/account/addresses'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_account_home_shows_active_order_and_sections(): void
    {
        $order = $this->order($this->customer);
        $this->customer->favorites()->attach(Product::query()->active()->first()->id, ['created_at' => now()]);

        $this->actingAs($this->customer, 'customer')
            ->get('/account')
            ->assertOk()
            ->assertSee('Вітаємо, Олено')
            ->assertSee('+380 67 123 45 67')
            ->assertSee('Замовлення № '.$order->number)
            ->assertSee('Відправлене')
            ->assertSee('Мої замовлення')
            ->assertSee('Адреси доставки')
            ->assertSee('Вийти');
    }

    public function test_orders_list_and_filter(): void
    {
        $active = $this->order($this->customer, OrderStatus::Shipped);
        $done = $this->order($this->customer, OrderStatus::Completed);
        $returned = $this->order($this->customer, OrderStatus::Returned);
        $foreign = $this->order(Customer::query()->create(['phone' => '+380991112233']));

        $this->actingAs($this->customer, 'customer')->get('/account/orders')->assertOk()
            ->assertSee('№ '.$active->number)->assertSee('Відстежити')->assertSee('Повторити')
            ->assertDontSee('№ '.$foreign->number);

        Livewire::actingAs($this->customer, 'customer')->test(AccountOrders::class)
            ->assertSee('Активні · 1')
            ->call('setFilter', 'active')
            ->assertSee('№ '.$active->number)->assertDontSee('№ '.$done->number)
            ->call('setFilter', 'returns')
            ->assertSee('№ '.$returned->number)->assertDontSee('№ '.$active->number);
    }

    public function test_order_details_show_snapshot_timeline_and_delivery(): void
    {
        $order = $this->order($this->customer);

        $this->actingAs($this->customer, 'customer')
            ->get('/account/orders/'.$order->number)
            ->assertOk()
            ->assertSee('Знімок назви товару')
            ->assertSee('Прийнято')
            ->assertSee('ТТН 20450000001234')
            ->assertSee('Відділення № 25, Київ')
            ->assertSee('При отриманні')
            ->assertSee('Повторити замовлення')
            ->assertSee('Повернути товар');
    }

    public function test_customer_cannot_see_someone_elses_order(): void
    {
        $order = $this->order(Customer::query()->create(['phone' => '+380991112233']));
        $guestOrder = $this->order(null);

        $this->actingAs($this->customer, 'customer')->get('/account/orders/'.$order->number)->assertNotFound();
        $this->actingAs($this->customer, 'customer')->get('/account/orders/'.$guestOrder->number)->assertNotFound();
    }

    public function test_reorder_adds_available_items_to_cart(): void
    {
        $variant = $this->variant();
        $order = $this->order($this->customer, OrderStatus::Completed, $variant);

        Livewire::actingAs($this->customer, 'customer')
            ->test(AccountReorder::class, ['order' => $order])
            ->call('reorder')
            ->assertRedirect(route('cart'));

        $this->assertSame([$variant->id => 2], app(Cart::class)->raw());
    }

    public function test_reorder_skips_unavailable_items(): void
    {
        $variant = $this->variant();
        $order = $this->order($this->customer, OrderStatus::Completed, $variant);
        $variant->update(['stock' => 0]);

        Livewire::actingAs($this->customer, 'customer')
            ->test(AccountReorder::class, ['order' => $order])
            ->call('reorder')
            ->assertNoRedirect()
            ->assertSee('Цих товарів зараз немає в наявності');

        $this->assertSame([], app(Cart::class)->raw());
    }

    public function test_reorder_of_foreign_order_is_rejected(): void
    {
        $order = $this->order(Customer::query()->create(['phone' => '+380991112233']), OrderStatus::Completed);

        Livewire::actingAs($this->customer, 'customer')
            ->test(AccountReorder::class, ['order' => $order])
            ->call('reorder')
            ->assertNotFound();
    }

    public function test_addresses_crud_and_single_default(): void
    {
        $this->actingAs($this->customer, 'customer')->get('/account/addresses')->assertOk()->assertSee('Додати адресу');

        $page = Livewire::actingAs($this->customer, 'customer')->test(AccountAddresses::class);

        // Перша адреса — одразу основна
        $page->call('create')
            ->assertSet('recipientName', 'Олена Коваль')
            ->set('city', 'Київ')->set('warehouse', '25')
            ->call('save')->assertHasNoErrors();

        // Друга, теж основна — з першої позначку знімаємо
        $page->call('create')
            ->set('delivery', 'np_locker')->set('city', 'Київ')->set('warehouse', '3412')->set('isDefault', true)
            ->call('save')->assertHasNoErrors()
            ->assertSee('Поштомат № 3412, Київ');

        $first = CustomerAddress::query()->where('warehouse', '25')->sole();
        $second = CustomerAddress::query()->where('warehouse', '3412')->sole();
        $this->assertFalse($first->is_default);
        $this->assertTrue($second->is_default);
        $this->assertSame(1, $this->customer->addresses()->where('is_default', true)->count());

        $page->call('makePrimary', $first->id);
        $this->assertTrue($first->refresh()->is_default);
        $this->assertFalse($second->refresh()->is_default);

        // Кур'єр: потрібні вулиця й будинок
        $page->call('edit', $second->id)->set('delivery', 'np_courier')->set('street', '')->call('save')->assertHasErrors(['street', 'building']);
        $page->set('street', 'вул. Хрещатик')->set('building', '12')->set('apartment', '3')->call('save')->assertHasNoErrors();
        $this->assertSame(DeliveryMethod::NovaPoshtaCourier, $second->refresh()->delivery_method);
        $this->assertNull($second->warehouse);

        // Видалили основну — основною стає інша
        $page->call('delete', $first->id);
        $this->assertModelMissing($first);
        $this->assertTrue($second->refresh()->is_default);
    }

    public function test_cannot_edit_someone_elses_address(): void
    {
        $other = Customer::query()->create(['phone' => '+380991112233']);
        $address = $other->addresses()->create([
            'recipient_name' => 'Інший', 'recipient_phone' => '+380991112233',
            'delivery_method' => DeliveryMethod::NovaPoshtaWarehouse, 'city' => 'Львів', 'warehouse' => '1', 'is_default' => true,
        ]);

        Livewire::actingAs($this->customer, 'customer')->test(AccountAddresses::class)->call('delete', $address->id)->assertNotFound();
        $this->assertModelExists($address);
    }

    public function test_favorites_for_guest_show_device_items_and_sign_in_prompt(): void
    {
        $product = Product::query()->active()->first();

        $this->get('/account/favorites')->assertOk()->assertSee('Обране збережене лише на цьому пристрої');

        Livewire::test(AccountFavorites::class)
            ->call('show', [$product->id, 999999])
            ->assertSee($product->name)
            ->assertSee('Обране · 1');
    }

    public function test_favorites_for_customer_come_from_account(): void
    {
        [$a, $b] = Product::query()->active()->take(2)->get();
        $this->customer->favorites()->attach($a->id, ['created_at' => now()]);

        $this->actingAs($this->customer, 'customer')->get('/account/favorites')->assertOk()
            ->assertSee($a->name)
            ->assertDontSee('Обране збережене лише на цьому пристрої');

        // ids з пристрою покупцю не потрібні — показуємо кабінет
        Livewire::actingAs($this->customer, 'customer')->test(AccountFavorites::class)
            ->call('show', [$b->id])
            ->assertSee($a->name)
            ->assertDontSee($b->name);
    }
}
