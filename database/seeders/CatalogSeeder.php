<?php

namespace Database\Seeders;

use App\Enums\ProductBadge;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Приклади з прототипу — щоб було з чим працювати локально. Асортимент не фінальний. */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            'Кухня', 'Дім і текстиль', 'Одяг', 'Світло й заряд', 'Для дітей', 'Подарунки',
        ])->mapWithKeys(fn (string $name, int $i) => [$name => Category::query()->updateOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'sort' => $i],
        )]);

        $apparel = $categories['Одяг'];
        $sub = collect(['Худі', 'Світшоти', 'Футболки'])->mapWithKeys(fn (string $name, int $i) => [$name => Category::query()->updateOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'parent_id' => $apparel->id, 'sort' => $i],
        )]);

        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

        $products = [
            ['cat' => $categories['Світло й заряд'], 'name' => 'Павербанк 20 000 мАг, швидка зарядка 22,5 Вт', 'price' => 749, 'old' => 899, 'badge' => ProductBadge::Hit, 'stock' => 40],
            ['cat' => $categories['Світло й заряд'], 'name' => 'Павербанк 30 000 мАг з ліхтариком', 'price' => 999, 'stock' => 25],
            ['cat' => $categories['Світло й заряд'], 'name' => 'Акумуляторна LED-лампа, 3 режими', 'price' => 289, 'badge' => ProductBadge::Hit, 'stock' => 60],
            ['cat' => $categories['Кухня'], 'name' => 'Термос сталевий 1 л', 'price' => 399, 'old' => 499, 'stock' => 30],
            ['cat' => $categories['Дім і текстиль'], 'name' => 'Електропростирадло 150×120 см', 'price' => 649, 'badge' => ProductBadge::New, 'stock' => 15],
            ['cat' => $categories['Подарунки'], 'name' => 'Подарунковий набір «Тепла зима»', 'price' => 489, 'stock' => 20],
            [
                'cat' => $sub['Худі'], 'name' => 'Худі oversize, футер 320 г', 'price' => 799, 'old' => 999, 'badge' => ProductBadge::New,
                'apparel' => true, 'fit' => 'Сідає розмір у розмір',
                'colors' => ['Оливковий' => '#6B7041', 'Графіт' => '#1F1F24'], 'sizes' => $sizes,
            ],
            [
                'cat' => $sub['Худі'], 'name' => 'Худі на блискавці з капюшоном', 'price' => 999,
                'apparel' => true, 'fit' => 'Сідає розмір у розмір',
                'colors' => ['Сірий меланж' => '#9A9A9E'], 'sizes' => ['M', 'L', 'XL', 'XXL'],
            ],
            [
                'cat' => $sub['Світшоти'], 'name' => 'Світшот базовий, тринитка', 'price' => 649,
                'apparel' => true, 'fit' => 'Вільний крій — якщо між розмірами, беріть менший',
                'colors' => ['Бежевий' => '#D8C8AE'], 'sizes' => $sizes,
            ],
            [
                'cat' => $sub['Футболки'], 'name' => 'Футболки бавовняні, 2 шт', 'price' => 379, 'old' => 499,
                'apparel' => true, 'fit' => 'Сідає розмір у розмір',
                'colors' => ['Білий' => '#FFFFFF', 'Чорний' => '#1F1F24'], 'sizes' => $sizes,
            ],
        ];

        foreach ($products as $i => $data) {
            $slug = Str::slug($data['name']);

            $product = Product::query()->updateOrCreate(['slug' => $slug], [
                'category_id' => $data['cat']->id,
                'name' => $data['name'],
                'price' => $data['price'],
                'old_price' => $data['old'] ?? null,
                'badge' => $data['badge'] ?? null,
                'is_apparel' => $data['apparel'] ?? false,
                'fit_hint' => $data['fit'] ?? null,
                'short_description' => 'Опис-заглушка. Справжній текст додасте в адмінці.',
                'sort' => $i,
            ]);

            if (! ($data['apparel'] ?? false)) {
                $product->variants()->updateOrCreate(
                    ['sku' => 'BZ-'.str_pad((string) $product->id, 4, '0', STR_PAD_LEFT)],
                    ['stock' => $data['stock']],
                );

                continue;
            }

            $n = 0;
            foreach ($data['colors'] as $color => $hex) {
                foreach ($data['sizes'] as $size) {
                    $product->variants()->updateOrCreate(
                        ['color' => $color, 'size' => $size],
                        [
                            'sku' => sprintf('BZ-%04d-%s-%s', $product->id, Str::upper(Str::substr(Str::slug($color), 0, 3)), $size),
                            'color_hex' => $hex,
                            'stock' => random_int(0, 5), // нулі теж потрібні — перевіряти «немає в наявності»
                            'sort' => $n++,
                        ],
                    );
                }
            }
        }

        $collections = [
            'На блекаут' => ['Павербанк 20 000 мАг, швидка зарядка 22,5 Вт', 'Павербанк 30 000 мАг з ліхтариком', 'Акумуляторна LED-лампа, 3 режими', 'Термос сталевий 1 л'],
            'Тепло вдома' => ['Електропростирадло 150×120 см', 'Худі oversize, футер 320 г', 'Термос сталевий 1 л'],
            'Подарунок до 500 ₴' => ['Акумуляторна LED-лампа, 3 режими', 'Термос сталевий 1 л', 'Подарунковий набір «Тепла зима»', 'Футболки бавовняні, 2 шт'],
        ];

        $i = 0;
        foreach ($collections as $name => $productNames) {
            $collection = Collection::query()->updateOrCreate(
                ['slug' => Str::slug(str_replace('₴', 'grn', $name))],
                ['name' => $name, 'show_on_home' => true, 'sort' => $i++],
            );

            $ids = Product::query()->whereIn('name', $productNames)->pluck('id');
            $collection->products()->sync($ids->mapWithKeys(fn ($id, $sort) => [$id => ['sort' => $sort]]));
        }
    }
}
