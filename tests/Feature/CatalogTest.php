<?php

namespace Tests\Feature;

use App\Livewire\Catalog;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    private function clothes(): int
    {
        return Category::query()->where('slug', 'clothes')->value('id');
    }

    public function test_root_category_shows_products_of_its_subcategories_with_filters(): void
    {
        $this->get(route('catalog', 'clothes'))
            ->assertOk()
            ->assertSee('Одяг')
            ->assertSee('6 товарів')
            ->assertSee('Худі oversize, футер 320 г')
            ->assertSee('Штани-джогери з начосом')
            ->assertDontSee('Павербанк 10 000 мАг')
            ->assertSee('Худі й світшоти')   // Тип
            ->assertSee('Розмір')
            ->assertSee('Графітовий')
            ->assertSee('Тільки зі знижкою');
    }

    public function test_size_from_url_filters_and_card_offers_that_size(): void
    {
        $this->get(route('catalog', ['category' => 'hoodies-sweatshirts', 'size' => 'M']))
            ->assertOk()
            ->assertSee('Розмір M')
            ->assertSee('Скинути все')
            ->assertSee('В кошик · M');
    }

    public function test_non_apparel_category_has_no_size_or_colour_filters(): void
    {
        $this->get(route('catalog', 'light-power'))
            ->assertOk()
            ->assertSee('Павербанк 10 000 мАг, компактний')
            ->assertDontSee('Графітовий')
            ->assertSee('Ціна, ₴');
    }

    public function test_filters_counts_and_reset(): void
    {
        Livewire::test(Catalog::class, ['mode' => 'category', 'categoryId' => $this->clothes()])
            ->assertViewHas('total', 6)
            ->call('toggle', 'type', 'tees')
            ->assertViewHas('total', 2)
            ->assertSet('type', 'tees')
            ->call('toggle', 'color', 'Оливковий')
            ->assertViewHas('total', 1)
            ->assertSee('Футболка oversize, щільна')
            ->call('remove', 'color', 'Оливковий')
            ->assertViewHas('total', 2)
            ->set('max', '420')
            ->assertViewHas('total', 1)
            ->assertSee('Ціна до')
            ->call('resetFilters')
            ->assertViewHas('total', 6);
    }

    public function test_size_and_colour_must_meet_in_one_variant(): void
    {
        // Худі на блискавці: розміри M–XXL, немає XS — світшот має XS
        Livewire::test(Catalog::class, ['mode' => 'category', 'categoryId' => $this->clothes()])
            ->call('toggle', 'size', 'XS')
            ->assertViewHas('products', fn ($products) => $products->pluck('slug')->doesntContain('hoodie-zip'));
    }

    public function test_empty_result_shows_reset_prompt(): void
    {
        Livewire::test(Catalog::class, ['mode' => 'category', 'categoryId' => $this->clothes()])
            ->set('min', '100000')
            ->assertViewHas('total', 0)
            ->assertSee('За цими фільтрами нічого немає')
            ->assertSee('Скинути фільтри')
            ->call('resetFilters')
            ->assertViewHas('total', 6);
    }

    public function test_sorting(): void
    {
        $component = Livewire::test(Catalog::class, ['mode' => 'category', 'categoryId' => $this->clothes()]);

        $component->set('sort', 'cheap')
            ->assertViewHas('products', fn ($p) => $p->first()->slug === 'tee-oversize');
        $component->set('sort', 'expensive')
            ->assertViewHas('products', fn ($p) => $p->first()->slug === 'hoodie-zip');
        $component->set('sort', 'rating')
            ->assertViewHas('products', fn ($p) => $p->first()->slug === 'tees-2pack');
        $component->set('sort', 'bogus')->assertSet('sort', 'popular');
    }

    public function test_load_more_pages_by_24(): void
    {
        $category = Category::query()->where('slug', 'tees')->firstOrFail();
        $template = Product::query()->where('slug', 'powerbank-10000')->with('variants')->firstOrFail();
        foreach (range(1, 30) as $i) {
            $p = $template->replicate(['rating', 'reviews_count'])->fill(['slug' => 'extra-'.$i, 'name' => 'Товар '.$i, 'category_id' => $category->id]);
            $p->save();
            $p->variants()->create(['sku' => 'EXTRA-'.$i, 'stock' => 5, 'is_active' => true]);
        }

        Livewire::test(Catalog::class, ['mode' => 'category', 'categoryId' => $category->id])
            ->assertViewHas('products', fn ($p) => $p->count() === 24)
            ->assertSee('Показати ще 8')
            ->assertSee('Показано 24 з 32')
            ->call('more')
            ->assertViewHas('products', fn ($p) => $p->count() === 32)
            ->assertDontSee('Показати ще');
    }

    public function test_sale_lists_discounted_products(): void
    {
        $this->get(route('catalog.sale'))
            ->assertOk()
            ->assertSee('Знижки')
            ->assertSee('Термос 1 л, нержавіюча сталь')
            ->assertDontSee('Павербанк 10 000 мАг, компактний');
    }

    public function test_inactive_category_is_404(): void
    {
        Category::query()->where('slug', 'tees')->update(['is_active' => false]);

        $this->get(route('catalog', 'tees'))->assertNotFound();
    }
}
