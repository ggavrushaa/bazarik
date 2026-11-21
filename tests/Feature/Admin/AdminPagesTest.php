<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Collections\CollectionResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\StockNotifications\StockNotificationResource;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\LowStockVariants;
use App\Filament\Widgets\ShopStatsOverview;
use App\Models\Category;
use App\Models\Collection;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Review;
use App\Models\StockNotification;
use Livewire\Livewire;

/** Кожна сторінка адмінки відкривається для адміністратора */
class AdminPagesTest extends AdminTestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        auth()->logout();

        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_dashboard_renders_with_widgets(): void
    {
        $this->placeOrder(Product::query()->where('slug', 'powerbank-20000')->firstOrFail()->variants()->firstOrFail());

        $this->get('/admin')->assertOk();

        Livewire::test(ShopStatsOverview::class)->assertOk()->assertSee('Нових замовлень сьогодні');
        Livewire::test(LatestOrders::class)->assertOk()->assertSee('B-');
        Livewire::test(LowStockVariants::class)->assertOk();
    }

    public function test_index_and_create_pages_render(): void
    {
        $urls = [
            OrderResource::getUrl('index'),
            ProductResource::getUrl('index'),
            ProductResource::getUrl('create'),
            CategoryResource::getUrl('index'),
            CategoryResource::getUrl('create'),
            CollectionResource::getUrl('index'),
            CollectionResource::getUrl('create'),
            CustomerResource::getUrl('index'),
            ReviewResource::getUrl('index'),
            StockNotificationResource::getUrl('index'),
            ContactMessageResource::getUrl('index'),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_edit_and_view_pages_render(): void
    {
        $product = Product::query()->where('is_apparel', true)->firstOrFail();
        $variant = $product->variants()->firstOrFail();
        $customer = Customer::create(['phone' => '+380501112233', 'first_name' => 'Ірина']);
        $customer->addresses()->create([
            'recipient_name' => 'Ірина', 'recipient_phone' => '+380501112233',
            'delivery_method' => 'np_warehouse', 'city' => 'Львів', 'warehouse' => 'Відділення №3',
        ]);
        $order = $this->placeOrder($variant, 1, ['customer_id' => $customer->id]);
        $review = Review::create(['product_id' => $product->id, 'author_name' => 'Оксана', 'rating' => 5, 'text' => 'Тепле', 'order_id' => $order->id]);
        StockNotification::create(['product_id' => $product->id, 'product_variant_id' => $variant->id, 'phone' => '+380631112233']);
        ContactMessage::create(['name' => 'Петро', 'phone' => '+380671112233', 'message' => 'Коли буде худі?']);

        $this->get(OrderResource::getUrl('edit', ['record' => $order]))->assertOk()->assertSee($order->number)->assertSee('Олена Коваль');
        $this->get(ProductResource::getUrl('edit', ['record' => $product]))->assertOk();
        $this->get(CategoryResource::getUrl('edit', ['record' => Category::query()->firstOrFail()]))->assertOk();
        $this->get(CategoryResource::getUrl('edit', ['record' => Category::query()->whereNotNull('parent_id')->firstOrFail()]))->assertOk();
        $this->get(CustomerResource::getUrl('view', ['record' => $customer]))->assertOk()->assertSee('Ірина');
        $this->get(ReviewResource::getUrl('edit', ['record' => $review]))->assertOk();
        $this->get(StockNotificationResource::getUrl('index'))->assertOk()->assertSee('+380631112233');
        $this->get(ContactMessageResource::getUrl('index'))->assertOk()->assertSee('Коли буде худі?');

        $collection = Collection::query()->first() ?? Collection::create(['name' => 'Подарунки', 'slug' => 'gifts-collection']);
        $this->get(CollectionResource::getUrl('edit', ['record' => $collection]))->assertOk();
    }

    public function test_every_product_edit_page_renders(): void
    {
        foreach (Product::query()->get() as $product) {
            $this->get(ProductResource::getUrl('edit', ['record' => $product]))->assertOk();
        }
    }
}
