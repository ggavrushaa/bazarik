<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Customers\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\StockNotifications\Pages\ListStockNotifications;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Review;
use App\Models\StockNotification;
use Livewire\Livewire;

class CustomersAdminTest extends AdminTestCase
{
    public function test_customer_list_and_view_with_orders_and_addresses(): void
    {
        $customer = Customer::create(['phone' => '+380501112233', 'first_name' => 'Ірина', 'last_name' => 'Мельник']);
        $customer->addresses()->create([
            'recipient_name' => 'Ірина Мельник', 'recipient_phone' => '+380501112233',
            'delivery_method' => 'np_warehouse', 'city' => 'Львів', 'warehouse' => 'Відділення №3',
        ]);
        $order = $this->placeOrder(Product::query()->firstOrFail()->variants()->firstOrFail(), 1, ['customer_id' => $customer->id]);

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords([$customer])
            ->searchTable('0501112233')
            ->assertCanSeeTableRecords([$customer]);

        Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])->assertOk()->assertSee('Ірина Мельник');

        Livewire::test(OrdersRelationManager::class, ['ownerRecord' => $customer, 'pageClass' => ViewCustomer::class])
            ->assertCanSeeTableRecords([$order]);

        Livewire::test(AddressesRelationManager::class, ['ownerRecord' => $customer, 'pageClass' => ViewCustomer::class])
            ->assertSee('Відділення №3');
    }

    public function test_reviews_bulk_publish_updates_product_rating(): void
    {
        $product = Product::query()->firstOrFail();
        $product->reviews()->delete();
        $reviews = collect([5, 3])->map(fn (int $rating) => Review::create([
            'product_id' => $product->id, 'author_name' => 'Автор '.$rating, 'rating' => $rating, 'text' => 'Текст',
        ]));

        $this->assertSame('2', ReviewResource::getNavigationBadge());

        Livewire::test(ListReviews::class)
            ->filterTable('is_published', false)
            ->assertCanSeeTableRecords($reviews)
            ->callTableBulkAction('publish', $reviews);

        $this->assertNull(ReviewResource::getNavigationBadge());
        $product->refresh();
        $this->assertSame(2, $product->reviews_count);
        $this->assertEquals(4.0, (float) $product->rating);
    }

    public function test_stock_notifications_pending_by_default_and_mark_notified(): void
    {
        $product = Product::query()->firstOrFail();
        $pending = StockNotification::create(['product_id' => $product->id, 'phone' => '+380631112233']);
        $done = StockNotification::create(['product_id' => $product->id, 'phone' => '+380631112244', 'notified_at' => now()]);

        Livewire::test(ListStockNotifications::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$done])
            ->callTableAction('markNotified', $pending);

        $this->assertNotNull($pending->refresh()->notified_at);
    }
}
