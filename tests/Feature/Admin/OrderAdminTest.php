<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

class OrderAdminTest extends AdminTestCase
{
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->variant = Product::query()->where('slug', 'powerbank-20000')->firstOrFail()->variants()->firstOrFail();
    }

    public function test_list_shows_orders_newest_first_and_searches_by_phone_and_ttn(): void
    {
        $old = $this->placeOrder($this->variant, 1, ['phone' => '+380501111111']);
        $old->forceFill(['created_at' => now()->subDay()])->save();
        $new = $this->placeOrder($this->variant, 1, ['phone' => '+380672222222', 'ttn' => '20450000123456']);

        Livewire::test(ListOrders::class)
            ->assertCanSeeTableRecords([$new, $old], inOrder: true)
            ->assertSee(money($new->total))
            ->searchTable('0672222222')
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$old])
            ->searchTable('20450000123456')
            ->assertCanSeeTableRecords([$new])
            ->searchTable($old->number)
            ->assertCanSeeTableRecords([$old])
            ->assertCanNotSeeTableRecords([$new]);
    }

    public function test_filters_by_status_and_quick_buy(): void
    {
        $quick = $this->placeOrder($this->variant, 1, ['is_quick_buy' => true]);
        $confirmed = $this->placeOrder($this->variant, 1, ['status' => OrderStatus::Confirmed]);

        Livewire::test(ListOrders::class)
            ->filterTable('is_quick_buy', true)
            ->assertCanSeeTableRecords([$quick])
            ->assertCanNotSeeTableRecords([$confirmed])
            ->resetTableFilters()
            ->filterTable('status', [OrderStatus::Confirmed->value])
            ->assertCanSeeTableRecords([$confirmed])
            ->assertCanNotSeeTableRecords([$quick]);
    }

    public function test_navigation_badge_counts_new_orders(): void
    {
        $this->assertNull(OrderResource::getNavigationBadge());

        $this->placeOrder($this->variant);
        $this->placeOrder($this->variant, 1, ['status' => OrderStatus::Shipped]);

        $this->assertSame('1', OrderResource::getNavigationBadge());
    }

    public function test_orders_cannot_be_created_from_admin(): void
    {
        $this->assertFalse(OrderResource::canCreate());
        $this->assertArrayNotHasKey('create', OrderResource::getPages());
    }

    public function test_confirm_then_ship_requires_ttn(): void
    {
        $order = $this->placeOrder($this->variant);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('confirm')
            ->assertHasNoActionErrors();
        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('ship', data: ['ttn' => ''])
            ->assertHasActionErrors(['ttn' => 'required']);
        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('ship', data: ['ttn' => '20450000123456'])
            ->assertHasNoActionErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertSame('20450000123456', $order->ttn);
        $this->assertNotNull($order->shipped_at);
    }

    public function test_cancel_returns_stock_once(): void
    {
        $stockBefore = $this->variant->stock;
        $order = $this->placeOrder($this->variant, 3);
        $this->assertSame($stockBefore - 3, $this->variant->refresh()->stock);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertActionVisible('cancel')
            ->callAction('cancel')
            ->assertHasNoActionErrors()
            ->assertActionHidden('cancel');

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame($stockBefore, $this->variant->refresh()->stock);

        // Скасоване вже не змінити формою — повторно на склад не повернемо
        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertFormFieldIsDisabled('status');
    }

    public function test_cancel_from_the_table_row(): void
    {
        $stockBefore = $this->variant->stock;
        $order = $this->placeOrder($this->variant, 2);

        Livewire::test(ListOrders::class)->callTableAction('cancel', $order);

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame($stockBefore, $this->variant->refresh()->stock);
    }

    public function test_status_change_in_form_returns_stock_and_saves_manager_fields(): void
    {
        $stockBefore = $this->variant->stock;
        $order = $this->placeOrder($this->variant, 2);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm([
                'status' => OrderStatus::Cancelled->value,
                'manager_note' => 'Покупець передумав',
                'dont_call' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame('Покупець передумав', $order->manager_note);
        $this->assertTrue($order->dont_call);
        $this->assertSame($stockBefore, $this->variant->refresh()->stock);
    }

    public function test_shipped_status_in_form_requires_ttn(): void
    {
        $order = $this->placeOrder($this->variant);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['status' => OrderStatus::Shipped->value, 'ttn' => null])
            ->call('save')
            ->assertHasFormErrors(['ttn' => 'required']);
    }

    public function test_completing_cod_order_marks_it_paid(): void
    {
        $order = $this->placeOrder($this->variant, 1, [
            'status' => OrderStatus::Shipped,
            'ttn' => '20450000123456',
            'payment_method' => PaymentMethod::CashOnDelivery,
        ]);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('complete');

        $order->refresh();
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertNotNull($order->delivered_at);
    }

    public function test_items_snapshot_is_shown_on_order_page(): void
    {
        $order = $this->placeOrder($this->variant, 2);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertOk()
            ->assertSee($this->variant->sku)
            ->assertSee($order->items->first()->name)
            ->assertSee(money($order->total));
    }
}
