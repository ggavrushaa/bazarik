<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Collections\Pages\EditCollection;
use App\Filament\Resources\Collections\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Livewire\Livewire;

class CatalogAdminTest extends AdminTestCase
{
    private function category(): Category
    {
        return Category::query()->where('slug', 'powerbanks')->firstOrFail();
    }

    public function test_creating_simple_product_creates_one_default_variant(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Ліхтарик кемпінговий',
                'category_id' => $this->category()->id,
                'price' => 349,
                'initial_stock' => 12,
            ])
            ->assertSchemaStateSet(['slug' => 'likhtaryk-kempinhovyy'])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->where('slug', 'likhtaryk-kempinhovyy')->firstOrFail();
        $this->assertCount(1, $product->variants);
        $this->assertSame('BZ-P'.$product->id, $product->variants->first()->sku);
        $this->assertSame(12, $product->variants->first()->stock);
        $this->assertNull($product->variants->first()->color);
        $this->assertNull($product->size_chart);
        $this->assertNull($product->highlights);
    }

    public function test_creating_apparel_product_saves_size_chart_and_has_no_default_variant(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Худі базове',
                'slug' => 'hoodie-basic',
                'category_id' => Category::query()->where('slug', 'hoodies-sweatshirts')->value('id'),
                'price' => 899,
                'old_price' => 1199,
                'is_apparel' => true,
                'fit_hint' => 'Сідає розмір у розмір',
                'size_chart' => [
                    'title' => 'Заміри, см',
                    'columns' => ['Розмір', 'Груди'],
                    'rows' => "S | 116\nM | 122\n",
                ],
                'highlights' => [['value' => '320 г/м²', 'text' => 'щільний футер']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->where('slug', 'hoodie-basic')->firstOrFail();
        $this->assertCount(0, $product->variants);
        $this->assertSame([['S', '116'], ['M', '122']], $product->size_chart['rows']);
        $this->assertSame(['Розмір', 'Груди'], $product->size_chart['columns']);
        $this->assertSame([['value' => '320 г/м²', 'text' => 'щільний футер']], $product->highlights);
    }

    public function test_old_price_must_be_greater_than_price(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Тест', 'category_id' => $this->category()->id, 'price' => 500, 'old_price' => 400])
            ->call('create')
            ->assertHasFormErrors(['old_price']);
    }

    public function test_editing_apparel_product_keeps_size_chart(): void
    {
        $product = Product::query()->where('is_apparel', true)->whereNotNull('size_chart')->firstOrFail();
        $chart = $product->size_chart;

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSchemaStateSet(['size_chart.rows' => collect($chart['rows'])->map(fn ($r) => implode(' | ', $r))->implode("\n")])
            ->fillForm(['name' => 'Нова назва'])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame('Нова назва', $product->name);
        $this->assertEquals($chart['rows'], $product->size_chart['rows']);
    }

    public function test_product_list_filters_and_toggle(): void
    {
        $apparel = Product::query()->where('is_apparel', true)->firstOrFail();
        $simple = Product::query()->where('is_apparel', false)->firstOrFail();

        Livewire::test(ListProducts::class)
            ->filterTable('is_apparel', true)
            ->assertCanSeeTableRecords([$apparel])
            ->assertCanNotSeeTableRecords([$simple]);

        $simple->variants()->update(['stock' => 0]);
        Livewire::test(ListProducts::class)
            ->filterTable('in_stock', false)
            ->assertCanSeeTableRecords([$simple])
            ->assertCanNotSeeTableRecords([$apparel]);
    }

    public function test_variants_bulk_stock_edit(): void
    {
        $product = Product::query()->where('is_apparel', true)->firstOrFail();
        $variants = $product->variants()->take(2)->get();

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->assertCanSeeTableRecords($variants)
            ->callTableBulkAction('stock', $variants, data: ['mode' => 'set', 'quantity' => 7]);

        $this->assertEquals([7, 7], $variants->fresh()->pluck('stock')->all());

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callTableBulkAction('stock', $variants, data: ['mode' => 'add', 'quantity' => 3]);

        $this->assertEquals([10, 10], $variants->fresh()->pluck('stock')->all());
    }

    public function test_variant_can_be_created_with_unique_sku(): void
    {
        $product = Product::query()->where('is_apparel', true)->firstOrFail();
        $existing = $product->variants()->firstOrFail();

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callTableAction('create', data: ['sku' => $existing->sku, 'stock' => 1])
            ->assertHasTableActionErrors(['sku' => 'unique']);

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callTableAction('create', data: ['sku' => 'BZ-NEW-1', 'color' => 'Синій', 'color_hex' => '#1e3a8a', 'size' => 'M', 'stock' => 4])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('product_variants', ['product_id' => $product->id, 'sku' => 'BZ-NEW-1', 'stock' => 4, 'color_hex' => '#1e3a8a']);
    }

    public function test_images_relation_manager_renders(): void
    {
        $product = Product::query()->has('images')->firstOrFail();

        Livewire::test(ImagesRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->assertOk()
            ->assertCanSeeTableRecords($product->images);
    }

    public function test_category_create_with_parent(): void
    {
        $parent = Category::query()->where('slug', 'kitchen')->firstOrFail();

        Livewire::test(CreateCategory::class)
            ->fillForm(['name' => 'Ланчбокси', 'parent_id' => $parent->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', ['slug' => 'lanchboksy', 'parent_id' => $parent->id]);

        Livewire::test(ListCategories::class)->assertOk()->assertSee('Ланчбокси');
    }

    public function test_collection_attach_products_with_sort(): void
    {
        $collection = Collection::create(['name' => 'На блекаут', 'slug' => 'blackout-test']);
        $products = Product::query()->take(2)->get();

        Livewire::test(ProductsRelationManager::class, ['ownerRecord' => $collection, 'pageClass' => EditCollection::class])
            ->callTableAction('attach', data: ['recordId' => $products->modelKeys(), 'sort' => 5])
            ->assertHasNoTableActionErrors();

        $this->assertSame(2, $collection->products()->count());
        $this->assertDatabaseHas('collection_product', ['collection_id' => $collection->id, 'product_id' => $products[0]->id, 'sort' => 5]);
    }
}
