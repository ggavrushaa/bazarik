<?php

namespace Tests\Feature;

use App\Livewire\ProductNotify;
use App\Models\Product;
use App\Models\Review;
use App\Models\StockNotification;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_regular_product_page(): void
    {
        $this->get('/product/powerbank-20000')
            ->assertOk()
            ->assertSee('<title>Павербанк 20 000 мАг, 22.5 Вт, три виходи — Bazarik</title>', false)
            ->assertSee('Економія '.money(400), false)
            ->assertSee('В наявності')
            ->assertSee('Замовте до '.config('shop.same_day_cutoff').' — отримаєте '.delivery_label(lower: true), false)
            ->assertSee('Додати в кошик')
            ->assertSee('Купити в 1 клік')
            ->assertSee('Простими словами')
            ->assertSee('~4 зарядки')
            ->assertSee('Усі характеристики')
            ->assertSee('Ємність 20 000 мАг · вихідна потужність до 22.5 Вт (PD, QC 3.0)', false)
            ->assertSee('Разом дешевше')
            ->assertSee(money(1086), false)          // (899 + 379) × 0.85
            ->assertSee('Додати набір')
            ->assertSee('Відео 0:24')
            ->assertSee('Відгуки · 4,8 з 5')
            ->assertSee('[Реальний відгук покупця — додамо після перших замовлень]', false)
            ->assertSee('Часто беруть разом')
            ->assertSee('Артикул BZ-10217')
            ->assertSee('add-variant', false)
            ->assertDontSee('Повідомимо, коли з’явиться');
    }

    public function test_apparel_product_page(): void
    {
        $this->get('/product/hoodie-oversize')
            ->assertOk()
            ->assertSee('Колір:')
            ->assertSee('Пудровий, немає в наявності')
            ->assertSee('XS, немає в наявності')
            ->assertSee('S, залишилось 3')
            ->assertSee('Розмір: <b x-text="size ?? \'не обрано\'">не обрано</b>', false)
            ->assertSee('Таблиця розмірів')
            ->assertSee('Заміри виробу, см')
            ->assertSee("'is-match'", false)
            ->assertSee('Сідає розмір у розмір · вільний крій')
            ->assertSee('Модель на фото: зріст 172 см, розмір M')
            ->assertSee('Оберіть розмір')
            ->assertSee('bz-buybar', false)
            ->assertSee('Склад і догляд')
            ->assertSee('Не підійшов розмір — повернете у відділенні протягом 14 днів')
            ->assertSee('Артикул BZ-10482')
            ->assertDontSee('Разом дешевше');
    }

    public function test_published_reviews_replace_placeholder(): void
    {
        $product = Product::query()->where('slug', 'powerbank-20000')->firstOrFail();
        Review::query()->create(['product_id' => $product->id, 'author_name' => 'Олена, Львів', 'rating' => 5, 'text' => 'Тримає заряд добре', 'is_published' => true]);
        Review::query()->create(['product_id' => $product->id, 'author_name' => 'Прихований', 'rating' => 1, 'text' => 'Не показувати', 'is_published' => false]);

        $this->get('/product/powerbank-20000')
            ->assertOk()
            ->assertSee('Олена, Львів')
            ->assertSee('Тримає заряд добре')
            ->assertDontSee('Не показувати')
            ->assertDontSee('[Реальний відгук покупця', false);
    }

    public function test_out_of_stock_product_page(): void
    {
        $this->get('/product/mug-500')
            ->assertOk()
            ->assertSee('Немає в наявності')
            ->assertSee('Повідомимо, коли з’явиться')
            ->assertSee('Повідомити мене')
            ->assertSee('Схожі в наявності')
            ->assertSee('Термос 0,75 л, з чашкою')   // та сама категорія, в наявності
            ->assertDontSee('Додати в кошик');
    }

    public function test_inactive_and_unknown_products_are_404(): void
    {
        Product::query()->where('slug', 'powerbank-10000')->update(['is_active' => false]);

        $this->get('/product/powerbank-10000')->assertNotFound();
        $this->get('/product/no-such-product')->assertNotFound();
    }

    public function test_notify_me_stores_phone(): void
    {
        $mug = Product::query()->where('slug', 'mug-500')->firstOrFail();

        Livewire::test(ProductNotify::class, ['productId' => $mug->id])
            ->set('phone', '12345')
            ->call('save')
            ->assertHasErrors('phone')
            ->set('phone', '067 123-45-67')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('+380 67 123 45 67');

        $this->assertDatabaseHas('stock_notifications', ['product_id' => $mug->id, 'phone' => '+380671234567', 'notified_at' => null]);

        // Повторна підписка не дублює запис
        Livewire::test(ProductNotify::class, ['productId' => $mug->id])->set('phone', '+380 67 123 45 67')->call('save');
        $this->assertSame(1, StockNotification::query()->count());
    }

    public function test_phone_normalization(): void
    {
        $this->assertSame('671234567', ProductNotify::normalize('67 123 45 67'));
        $this->assertSame('671234567', ProductNotify::normalize('+38 (067) 123-45-67'));
        $this->assertNull(ProductNotify::normalize('067123456'));
        $this->assertNull(ProductNotify::normalize('012345678'));
    }
}
