<?php

namespace Tests\Feature\Admin;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);

        $this->admin = User::factory()->create(['email' => 'manager@bazarik.com.ua']);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /** Замовлення як з вітрини: зі знімком товару й списаним залишком */
    protected function placeOrder(ProductVariant $variant, int $quantity = 2, array $attributes = []): Order
    {
        $order = Order::create([
            'status' => OrderStatus::New,
            'first_name' => 'Олена',
            'last_name' => 'Коваль',
            'phone' => '+380671234567',
            'delivery_method' => DeliveryMethod::NovaPoshtaWarehouse,
            'city' => 'Київ',
            'warehouse' => 'Відділення №12',
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            ...$attributes,
        ]);

        $price = (float) $variant->final_price;
        $order->items()->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'name' => $variant->product->name,
            'sku' => $variant->sku,
            'variant_label' => $variant->label,
            'price' => $price,
            'quantity' => $quantity,
            'total' => $price * $quantity,
        ]);
        $variant->decrement('stock', $quantity);
        $order->recalculateTotals();

        return $order->refresh();
    }
}
