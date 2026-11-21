<?php

namespace Database\Seeders;

use App\Enums\ProductBadge;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Товари з затвердженого прототипу (bazarik-handoff/screens), щоб вітрина виглядала як макети.
 * Фото — тимчасові з Unsplash (public/images/demo), перед запуском замінити на справжні.
 */
class CatalogSeeder extends Seeder
{
    private const SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

    private const COLORS = [
        'Графітовий' => '#2b2b30',
        'Молочний' => '#f2ede4',
        'Оливковий' => '#6b6f4a',
        'Пудровий' => '#e3c4bd',
    ];

    public function run(): void
    {
        $roots = collect([
            'kitchen' => ['Кухня', 'cat-kitchen.jpg'],
            'home-textile' => ['Дім і текстиль', 'cat-home-textile.jpg'],
            'clothes' => ['Одяг', 'cat-clothes.jpg'],
            'light-power' => ['Світло й заряд', 'cat-light-power.jpg'],
            'kids' => ['Для дітей', 'cat-kids.jpg'],
            'gifts' => ['Подарунки', 'cat-gifts.jpg'],
        ])->map(fn (array $c, string $slug) => Category::query()->updateOrCreate(
            ['slug' => $slug],
            ['name' => $c[0], 'image' => '/images/demo/'.$c[1], 'parent_id' => null, 'sort' => array_search($slug, ['kitchen', 'home-textile', 'clothes', 'light-power', 'kids', 'gifts'])],
        ));

        $sub = fn (string $parent, string $slug, string $name, int $sort) => Category::query()->updateOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'parent_id' => $roots[$parent]->id, 'sort' => $sort],
        );

        $c = [
            'hoodies' => $sub('clothes', 'hoodies-sweatshirts', 'Худі й світшоти', 0),
            'tees' => $sub('clothes', 'tees', 'Футболки', 1),
            'pants' => $sub('clothes', 'pants', 'Штани', 2),
            'homewear' => $sub('clothes', 'homewear', 'Домашній одяг', 3),
            'powerbanks' => $sub('light-power', 'powerbanks', 'Павербанки й зарядки', 0),
            'lamps' => $sub('light-power', 'lamps', 'Ліхтарі й лампи', 1),
            'thermos' => $sub('kitchen', 'thermoses', 'Термоси й термочашки', 0),
            'blankets' => $sub('home-textile', 'blankets', 'Пледи й простирадла', 0),
        ];

        $hoodieChart = [
            'title' => 'Заміри виробу, см',
            'columns' => ['Розмір', 'Груди', 'Довжина', 'Рукав', 'Зріст'],
            'rows' => [
                ['S', '116', '68', '58', '158–166'],
                ['M', '122', '70', '60', '166–174'],
                ['L', '128', '72', '62', '174–182'],
                ['XL', '134', '74', '64', '182–190'],
                ['XXL', '140', '76', '65', '190+'],
            ],
        ];

        $products = [
            'powerbank-20000' => [
                'cat' => $c['powerbanks'], 'name' => 'Павербанк 20 000 мАг, 22.5 Вт, три виходи', 'price' => 899, 'old' => 1299,
                'badge' => ProductBadge::Hit, 'rating' => 4.8, 'reviews' => 126, 'photo' => 'p-powerbank-20000.jpg', 'stock' => 40, 'sku' => 'BZ-10217',
                'short' => 'Вистачить на 4 зарядки телефона. Заряджає три пристрої одночасно.',
                'highlights' => [
                    ['value' => '~4 зарядки', 'text' => 'смартфона на одному заряді'],
                    ['value' => '3 пристрої', 'text' => 'заряджає одночасно'],
                    ['value' => '~5 годин', 'text' => 'роботи Wi-Fi роутера'],
                    ['value' => '380 г', 'text' => 'вміщується в сумку'],
                ],
                'specs' => [
                    ['label' => 'Ємність', 'value' => '20 000 мАг'],
                    ['label' => 'Вихідна потужність', 'value' => 'до 22.5 Вт (PD, QC 3.0)'],
                    ['label' => 'Виходи', 'value' => '2 × USB-A, 1 × USB-C'],
                    ['label' => 'Вхід', 'value' => 'USB-C 18 Вт'],
                    ['label' => 'Вага', 'value' => '380 г'],
                    ['label' => 'Розмір', 'value' => '14 × 7 × 2.7 см'],
                ],
                'video_seconds' => 24, 'bundle' => 'led-lamp', 'bundle_discount' => 15,
            ],
            'powerbank-10000' => ['cat' => $c['powerbanks'], 'name' => 'Павербанк 10 000 мАг, компактний', 'price' => 549, 'rating' => 4.7, 'reviews' => 52, 'photo' => 'p-powerbank-10000.jpg', 'stock' => 35],
            'powerbank-20000-display' => ['cat' => $c['powerbanks'], 'name' => 'Павербанк 20 000 мАг з дисплеєм', 'price' => 1049, 'rating' => 4.6, 'reviews' => 21, 'photo' => 'p-powerbank-10000.jpg', 'stock' => 12],
            'powerbank-30000' => ['cat' => $c['powerbanks'], 'name' => 'Павербанк 30 000 мАг, 65 Вт для ноутбука', 'price' => 1599, 'old' => 1899, 'rating' => 4.8, 'reviews' => 17, 'photo' => 'p-powerbank-20000.jpg', 'stock' => 8],
            'led-lamp' => ['cat' => $c['lamps'], 'name' => 'Акумуляторна LED-лампа з гачком', 'price' => 379, 'rating' => 4.7, 'reviews' => 33, 'photo' => 'p-led-lamp.jpg', 'stock' => 60],
            'thermos-1l' => ['cat' => $c['thermos'], 'name' => 'Термос 1 л, нержавіюча сталь', 'price' => 449, 'old' => 599, 'rating' => 4.9, 'reviews' => 74, 'photo' => 'p-thermos.jpg', 'stock' => 30],
            'thermos-075' => ['cat' => $c['thermos'], 'name' => 'Термос 0,75 л, з чашкою', 'price' => 399, 'rating' => 4.7, 'reviews' => 22, 'photo' => 'p-thermos.jpg', 'stock' => 18],
            'mug-400' => ['cat' => $c['thermos'], 'name' => 'Термочашка 400 мл з кришкою', 'price' => 299, 'badge' => ProductBadge::New, 'rating' => 4.6, 'reviews' => 18, 'photo' => 'p-mug.jpg', 'stock' => 25],
            'mug-500' => ['cat' => $c['thermos'], 'name' => 'Термочашка 500 мл', 'price' => 329, 'old' => 429, 'rating' => 4.6, 'reviews' => 15, 'photo' => 'p-mug.jpg', 'stock' => 0],
            'heated-sheet' => ['cat' => $c['blankets'], 'name' => 'Простирадло з підігрівом 160×140 см', 'price' => 1499, 'old' => 1890, 'rating' => 4.7, 'reviews' => 58, 'photo' => 'p-heated-sheet.jpg', 'stock' => 15],
            'plaid' => ['cat' => $c['blankets'], 'name' => 'Плед флісовий 150×200 см', 'price' => 499, 'old' => 649, 'rating' => 4.8, 'reviews' => 29, 'photo' => 'p-plaid.jpg', 'stock' => 22],
            'hoodie-oversize' => [
                'cat' => $c['hoodies'], 'name' => 'Худі oversize, футер 320 г', 'price' => 799, 'old' => 999, 'badge' => ProductBadge::Hit,
                'rating' => 4.6, 'reviews' => 41, 'photo' => 'p-hoodie.jpg', 'sku' => 'BZ-10482',
                'fit' => 'Сідає розмір у розмір · вільний крій', 'model' => 'Модель на фото: зріст 172 см, розмір M',
                'description' => 'Об’ємне худі з щільного футера з начосом. Тримає форму після прання, не скочується. Капюшон на шнурку, кишеня-кенгуру, манжети з рібани.',
                'care' => '80% бавовна, 20% поліестер · тринитка з начосом, 320 г/м² · прання при 30 °C навиворіт · не відбілювати · сушити горизонтально',
                'chart' => $hoodieChart,
                'colors' => self::COLORS,
                // Залишки з макета MobileApparel: XS — немає, S — 3 шт, L — 2 шт, XXL — 3 шт; пудровий закінчився
                'stock' => ['XS' => 0, 'S' => 3, 'M' => 12, 'L' => 2, 'XL' => 10, 'XXL' => 3],
                'out_colors' => ['Пудровий'],
            ],
            'hoodie-zip' => ['cat' => $c['hoodies'], 'name' => 'Худі на блискавці з капюшоном', 'price' => 999, 'badge' => ProductBadge::New, 'rating' => 4.7, 'reviews' => 12, 'photo' => 'p-zip-hoodie.jpg', 'sizes' => ['M', 'L', 'XL', 'XXL'], 'colors' => ['Графітовий' => '#2b2b30', 'Молочний' => '#f2ede4']],
            'sweatshirt' => ['cat' => $c['hoodies'], 'name' => 'Світшот базовий, тринитка', 'price' => 649, 'rating' => 4.5, 'reviews' => 23, 'photo' => 'p-sweatshirt.jpg', 'sizes' => ['XS', 'S', 'M', 'L'], 'colors' => ['Молочний' => '#f2ede4', 'Графітовий' => '#2b2b30']],
            'tees-2pack' => ['cat' => $c['tees'], 'name' => 'Футболки бавовняні, 2 шт', 'price' => 449, 'old' => 590, 'rating' => 4.8, 'reviews' => 64, 'photo' => 'p-tees-2pack.jpg', 'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['Молочний' => '#f2ede4', 'Графітовий' => '#2b2b30']],
            'tee-oversize' => ['cat' => $c['tees'], 'name' => 'Футболка oversize, щільна', 'price' => 399, 'badge' => ProductBadge::New, 'rating' => 4.6, 'reviews' => 9, 'photo' => 'p-tee-oversize.jpg', 'sizes' => ['XS', 'S', 'M', 'L'], 'colors' => ['Молочний' => '#f2ede4', 'Оливковий' => '#6b6f4a']],
            'joggers' => ['cat' => $c['pants'], 'name' => 'Штани-джогери з начосом', 'price' => 899, 'old' => 1090, 'rating' => 4.6, 'reviews' => 31, 'photo' => 'p-joggers.jpg', 'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['Графітовий' => '#2b2b30', 'Оливковий' => '#6b6f4a']],
        ];

        $models = [];
        $sort = 0;
        foreach ($products as $slug => $p) {
            $apparel = isset($p['colors']);

            $product = Product::query()->updateOrCreate(['slug' => $slug], [
                'category_id' => $p['cat']->id,
                'name' => $p['name'],
                'price' => $p['price'],
                'old_price' => $p['old'] ?? null,
                'badge' => $p['badge'] ?? null,
                'is_apparel' => $apparel,
                'fit_hint' => $p['fit'] ?? ($apparel ? 'Сідає розмір у розмір' : null),
                'model_note' => $p['model'] ?? null,
                'short_description' => $p['short'] ?? null,
                'description' => $p['description'] ?? null,
                'care' => $p['care'] ?? null,
                'highlights' => $p['highlights'] ?? null,
                'specs' => $p['specs'] ?? null,
                'size_chart' => $p['chart'] ?? ($apparel ? $hoodieChart : null),
                'video_seconds' => $p['video_seconds'] ?? null,
                'bundle_discount' => $p['bundle_discount'] ?? null,
                'sort' => $sort++,
            ]);
            $product->forceFill(['rating' => $p['rating'], 'reviews_count' => $p['reviews']])->saveQuietly();

            $product->images()->delete();
            $product->images()->create(['path' => '/images/demo/'.$p['photo'], 'alt' => $p['name'], 'sort' => 0]);

            $product->variants()->delete();
            $baseSku = $p['sku'] ?? sprintf('BZ-%05d', 10000 + $product->id);

            if (! $apparel) {
                $product->variants()->create(['sku' => $baseSku, 'stock' => $p['stock']]);
            } else {
                $n = 0;
                $sizes = $p['sizes'] ?? self::SIZES;
                foreach ($p['colors'] as $color => $hex) {
                    foreach ($sizes as $i => $size) {
                        $stock = in_array($color, $p['out_colors'] ?? [], true) ? 0 : (is_array($p['stock'] ?? null) ? $p['stock'][$size] : 4 + ($i % 3) * 3);
                        $product->variants()->create([
                            'sku' => sprintf('%s-%s-%s', $baseSku, Str::upper(Str::substr(Str::slug($color), 0, 3)), $size),
                            'color' => $color,
                            'color_hex' => $hex,
                            'size' => $size,
                            'stock' => $stock,
                            'sort' => $n++,
                        ]);
                    }
                }
            }

            $models[$slug] = $product;
        }

        foreach ($products as $slug => $p) {
            if (isset($p['bundle'])) {
                $models[$slug]->update(['bundle_product_id' => $models[$p['bundle']]->id]);
            }
        }

        // Старі демо-товари з першого каркаса — прибираємо, щоб не плутали вітрину
        Product::query()->whereNotIn('slug', array_keys($products))->delete();
        Category::query()->whereNotIn('slug', array_merge($roots->keys()->all(), collect($c)->pluck('slug')->all()))->delete();

        $collections = [
            'Що беруть цього тижня' => ['powerbank-20000', 'heated-sheet', 'thermos-1l', 'hoodie-oversize'],
            'На блекаут' => ['powerbank-20000', 'powerbank-10000', 'powerbank-30000', 'led-lamp', 'thermos-1l'],
            'Подарунок до 500 ₴' => ['led-lamp', 'thermos-1l', 'mug-400', 'tees-2pack', 'plaid'],
        ];

        $i = 0;
        foreach ($collections as $name => $slugs) {
            $collection = Collection::query()->updateOrCreate(
                ['slug' => Str::slug(str_replace('₴', 'grn', $name))],
                ['name' => $name, 'show_on_home' => $i === 0, 'sort' => $i++],
            );
            $collection->products()->sync(collect($slugs)->mapWithKeys(fn ($s, $sort) => [$models[$s]->id => ['sort' => $sort]]));
        }
    }
}
