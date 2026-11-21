<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_categories_and_popular_products(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('Усе для дому')
            ->assertSee('Світло й заряд')
            ->assertSee('Що беруть цього тижня')
            ->assertSee('Павербанк 20 000 мАг, 22.5 Вт, три виходи')
            ->assertSee('Обрати розмір')          // худі — вибір розміру з картки
            ->assertSee('Безкоштовна доставка від '.money(1500), false);
    }

    public function test_home_works_on_empty_catalog(): void
    {
        $this->get('/')->assertOk();
    }
}
