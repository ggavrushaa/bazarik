<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Список товарів з фільтрами: категорія (MobileCatalog / DeskCatalog), «Знижки» і пошук
 * (StateSearch / DeskSearchResults). Увесь стан фільтрів — у адресі, тож посиланням можна поділитися.
 */
class Catalog extends Component
{
    public const PER_PAGE = 24;

    public const SORTS = [
        'popular' => 'Популярні',
        'cheap' => 'Спочатку дешевші',
        'expensive' => 'Спочатку дорожчі',
        'new' => 'Новинки',
        'rating' => 'За рейтингом',
    ];

    private const SIZE_ORDER = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

    /** category | sale | search */
    #[Locked]
    public string $mode = 'category';

    #[Locked]
    public ?int $categoryId = null;

    #[Locked, Url(except: '')]
    public string $q = '';

    /** Підкатегорії (у пошуку й знижках — категорії), slug через кому */
    #[Url(except: '')]
    public string $type = '';

    /** Розміри через кому: ?size=M,L */
    #[Url(except: '')]
    public string $size = '';

    /** Кольори через кому: ?color=Графітовий */
    #[Url(except: '')]
    public string $color = '';

    #[Url(except: '')]
    public string $min = '';

    #[Url(except: '')]
    public string $max = '';

    /** «Тільки зі знижкою» */
    #[Url(as: 'discount', except: false)]
    public bool $discount = false;

    #[Url(except: 'popular')]
    public string $sort = 'popular';

    /** Скільки сторінок по 24 вже показано («Показати ще 24») */
    #[Url(as: 'p', except: 1)]
    public int $pages = 1;

    private ?Category $categoryModel = null;

    /** @var array{display: string, also: list<string>, groups: list<list<string>>}|null */
    private ?array $parsed = null;

    public function mount(string $mode = 'category', ?int $categoryId = null, string $q = ''): void
    {
        $this->mode = in_array($mode, ['category', 'sale', 'search'], true) ? $mode : 'category';
        $this->categoryId = $categoryId;
        $this->q = mb_substr(trim($q), 0, 100);

        if (! array_key_exists($this->sort, self::SORTS)) {
            $this->sort = 'popular';
        }
        $this->pages = max(1, min($this->pages, 20));
    }

    public function updated(string $property): void
    {
        if ($property !== 'pages') {
            $this->pages = 1;
        }
        if ($property === 'sort' && ! array_key_exists($this->sort, self::SORTS)) {
            $this->sort = 'popular';
        }
        if (in_array($property, ['min', 'max'], true)) {
            $this->{$property} = preg_replace('/\D/', '', $this->{$property});
        }
    }

    /** Увімкнути/вимкнути значення фільтра (type, size, color) */
    public function toggle(string $facet, string $value): void
    {
        if (! in_array($facet, ['type', 'size', 'color'], true)) {
            return;
        }

        $values = $this->values($facet);
        $values = in_array($value, $values, true)
            ? array_values(array_diff($values, [$value]))
            : [...$values, $value];

        $this->{$facet} = implode(',', $values);
        $this->pages = 1;
    }

    /** Чипи підкатегорій на телефоні й чипи категорій у пошуку: одна за раз, «Усі» — скинути */
    public function setType(string $slug = ''): void
    {
        $this->type = $slug === '' || $this->type === $slug ? '' : $slug;
        $this->pages = 1;
    }

    public function remove(string $facet, string $value = ''): void
    {
        match ($facet) {
            'type', 'size', 'color' => $this->toggle($facet, $value),
            'price' => [$this->min, $this->max] = ['', ''],
            'discount' => $this->discount = false,
            default => null,
        };
        $this->pages = 1;
    }

    public function resetFilters(): void
    {
        $this->reset('type', 'size', 'color', 'min', 'max', 'discount', 'pages');
    }

    public function more(): void
    {
        $this->pages = min($this->pages + 1, 20);
    }

    public function render(): View
    {
        $category = $this->category();
        $types = $this->typeOptions();
        $hasApparel = (clone $this->base())->where('is_apparel', true)->exists();
        $sizes = $hasApparel ? $this->sizeOptions() : collect();
        $colors = $hasApparel ? $this->colorOptions() : collect();

        $query = $this->filtered();
        $total = (clone $query)->count();
        $products = $this->sorted($query)
            ->with(['mainImage', 'variants'])
            ->limit(self::PER_PAGE * $this->pages)
            ->get();

        $prices = (clone $this->base())->selectRaw('min(price) as lo, max(price) as hi')->first();
        $baseTotal = $this->mode === 'search' ? (clone $this->base())->count() : null;

        return view('livewire.catalog', [
            'category' => $category,
            'title' => match ($this->mode) {
                'sale' => 'Знижки',
                'search' => 'Пошук',
                default => $category?->name,
            },
            'search' => $this->mode === 'search' ? $this->parsed() : null,
            'products' => $products,
            'total' => $total,
            'baseTotal' => $baseTotal,
            'types' => $types,
            'sizes' => $sizes,
            'colors' => $colors,
            'active' => $this->activeFilters($types),
            'selected' => ['type' => $this->values('type'), 'size' => $this->values('size'), 'color' => $this->values('color')],
            'priceFrom' => $prices?->lo !== null ? (int) floor((float) $prices->lo) : null,
            'priceTo' => $prices?->hi !== null ? (int) ceil((float) $prices->hi) : null,
            'popular' => $total === 0 && $this->mode === 'search' ? $this->popularProducts() : collect(),
            'sorts' => self::SORTS,
        ]);
    }

    // ── Запити ──

    /** Усі товари сторінки без фільтрів користувача */
    private function base(): Builder
    {
        $query = Product::query()->active();

        return match ($this->mode) {
            'sale' => $query->whereNotNull('old_price')->whereColumn('old_price', '>', 'price'),
            'search' => Search::apply($query, $this->parsed()['groups']),
            default => $query->whereIn('category_id', $this->categoryIds()),
        };
    }

    /** Базовий набір + фільтри, крім $except (для чесних лічильників у фасетах) */
    private function filtered(?string $except = null): Builder
    {
        $query = $this->base();

        if ($except !== 'type' && ($ids = $this->selectedTypeIds()) !== []) {
            $query->whereIn('category_id', $ids);
        }
        if ($this->min !== '') {
            $query->where('price', '>=', (int) $this->min);
        }
        if ($this->max !== '') {
            $query->where('price', '<=', (int) $this->max);
        }
        if ($this->discount) {
            $query->whereNotNull('old_price')->whereColumn('old_price', '>', 'price');
        }

        $sizes = $except === 'size' ? [] : $this->values('size');
        $colors = $except === 'color' ? [] : $this->values('color');
        if ($sizes !== [] || $colors !== []) {
            // Розмір і колір — в одному варіанті з залишком: «M графітовий» має бути на складі
            $query->whereHas('variants', fn (Builder $v) => $this->inStock($v)
                ->when($sizes, fn (Builder $q) => $q->whereIn('size', $sizes))
                ->when($colors, fn (Builder $q) => $q->whereIn('color', $colors)));
        }

        return $query;
    }

    private function sorted(Builder $query): Builder
    {
        // Товари в наявності — вище за ті, яких немає
        $query->orderByDesc(
            ProductVariant::query()->selectRaw('count(*) > 0')->whereColumn('product_id', 'products.id')->where('is_active', true)->where('stock', '>', 0)
        );

        return match ($this->sort) {
            'cheap' => $query->orderBy('price')->orderBy('id'),
            'expensive' => $query->orderByDesc('price')->orderBy('id'),
            'new' => $query->orderByDesc('id'),
            'rating' => $query->orderByDesc('rating')->orderByDesc('reviews_count')->orderBy('id'),
            default => $query->orderBy('sort')->orderBy('id'),
        };
    }

    private function inStock(Builder $variants): Builder
    {
        return $variants->where('is_active', true)->where('stock', '>', 0);
    }

    // ── Фасети ──

    /**
     * «Тип»: підкатегорії кореневої категорії, а в знижках і пошуку — категорії знайдених товарів.
     *
     * @return Collection<int, array{slug: string, name: string, id: int, count: int}>
     */
    private function typeOptions(): Collection
    {
        $category = $this->category();

        if ($this->mode === 'category') {
            $candidates = $category?->children->where('is_active', true) ?? collect();
        } else {
            $ids = (clone $this->base())->whereNotNull('category_id')->distinct()->pluck('category_id');
            $candidates = Category::query()->active()->whereIn('id', $ids)->orderBy('sort')->orderBy('name')->get();
        }

        if ($candidates->isEmpty()) {
            return collect();
        }

        $inBase = (clone $this->base())->toBase()->selectRaw('category_id, count(*) as n')->groupBy('category_id')->pluck('n', 'category_id');
        $counts = $this->filtered('type')->toBase()->selectRaw('category_id, count(*) as n')->groupBy('category_id')->pluck('n', 'category_id');

        return $candidates
            ->filter(fn (Category $c) => ($inBase[$c->id] ?? 0) > 0)
            ->map(fn (Category $c) => ['id' => $c->id, 'slug' => $c->slug, 'name' => $c->name, 'count' => (int) ($counts[$c->id] ?? 0)])
            ->values();
    }

    /** @return Collection<int, array{value: string, count: int}> */
    private function sizeOptions(): Collection
    {
        $all = $this->inStock(ProductVariant::query())
            ->whereIn('product_id', $this->base()->select('products.id'))
            ->whereNotNull('size')->distinct()->pluck('size');

        $colors = $this->values('color');
        $counts = $this->inStock(ProductVariant::query())
            ->whereIn('product_id', $this->filtered('size')->select('products.id'))
            ->when($colors, fn (Builder $q) => $q->whereIn('color', $colors))
            ->whereNotNull('size')
            ->toBase()->selectRaw('size, count(distinct product_id) as n')->groupBy('size')->pluck('n', 'size');

        return $all
            ->sortBy(fn (string $s) => ($i = array_search($s, self::SIZE_ORDER, true)) === false ? 99 : $i)
            ->map(fn (string $s) => ['value' => $s, 'count' => (int) ($counts[$s] ?? 0)])
            ->values();
    }

    /** @return Collection<int, array{value: string, hex: ?string, count: int}> */
    private function colorOptions(): Collection
    {
        $all = $this->inStock(ProductVariant::query())
            ->whereIn('product_id', $this->base()->select('products.id'))
            ->whereNotNull('color')
            ->toBase()->selectRaw('color, max(color_hex) as hex, count(distinct product_id) as n')->groupBy('color')
            ->orderByDesc('n')->orderBy('color')->get();

        $sizes = $this->values('size');
        $counts = $this->inStock(ProductVariant::query())
            ->whereIn('product_id', $this->filtered('color')->select('products.id'))
            ->when($sizes, fn (Builder $q) => $q->whereIn('size', $sizes))
            ->whereNotNull('color')
            ->toBase()->selectRaw('color, count(distinct product_id) as n')->groupBy('color')->pluck('n', 'color');

        return $all->map(fn ($row) => ['value' => $row->color, 'hex' => $row->hex, 'count' => (int) ($counts[$row->color] ?? 0)])->values();
    }

    /**
     * Обрані фільтри для чипів «Розмір M ×»
     *
     * @return list<array{facet: string, value: string, label: string}>
     */
    private function activeFilters(Collection $types): array
    {
        $chips = [];
        foreach ($types->whereIn('slug', $this->values('type')) as $t) {
            $chips[] = ['facet' => 'type', 'value' => $t['slug'], 'label' => $t['name']];
        }
        foreach ($this->values('size') as $s) {
            $chips[] = ['facet' => 'size', 'value' => $s, 'label' => 'Розмір '.$s];
        }
        foreach ($this->values('color') as $c) {
            $chips[] = ['facet' => 'color', 'value' => $c, 'label' => $c];
        }
        if ($this->min !== '' || $this->max !== '') {
            $label = match (true) {
                $this->min !== '' && $this->max !== '' => number_format((int) $this->min, 0, ',', "\u{00A0}").'–'.money($this->max),
                $this->min !== '' => 'від '.money($this->min),
                default => 'до '.money($this->max),
            };
            $chips[] = ['facet' => 'price', 'value' => '', 'label' => 'Ціна '.$label];
        }
        if ($this->discount) {
            $chips[] = ['facet' => 'discount', 'value' => '', 'label' => 'Зі знижкою'];
        }

        return $chips;
    }

    // ── Допоміжне ──

    private function category(): ?Category
    {
        if ($this->mode !== 'category' || ! $this->categoryId) {
            return null;
        }

        return $this->categoryModel ??= Category::query()->with(['parent', 'children'])->find($this->categoryId);
    }

    /** Категорія разом з активними підкатегоріями */
    private function categoryIds(): array
    {
        $category = $this->category();
        if (! $category) {
            return [0];
        }

        return [$category->id, ...$category->children->where('is_active', true)->pluck('id')];
    }

    private function selectedTypeIds(): array
    {
        $slugs = $this->values('type');
        if ($slugs === []) {
            return [];
        }

        $query = Category::query()->whereIn('slug', $slugs);
        if ($this->mode === 'category') {
            $query->where('parent_id', $this->categoryId ?? 0);
        }
        $ids = $query->pluck('id')->all();

        return $ids === [] ? [0] : $ids;
    }

    /** @return list<string> */
    private function values(string $facet): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $this->{$facet})), fn ($v) => $v !== '')));
    }

    /** Розбір запиту; якщо нічого не знайшли — пробуємо іншу розкладку клавіатури («gfdth,fyr») */
    private function parsed(): array
    {
        if ($this->parsed !== null) {
            return $this->parsed;
        }

        $parsed = Search::parse($this->q);
        if ($parsed['groups'] !== [] && ! Search::apply(Product::query()->active(), $parsed['groups'])->exists()) {
            $swapped = Search::swapLayout($this->q);
            if ($swapped !== null) {
                $alt = Search::parse($swapped);
                if ($alt['groups'] !== [] && Search::apply(Product::query()->active(), $alt['groups'])->exists()) {
                    $parsed = $alt;
                }
            }
        }

        return $this->parsed = $parsed;
    }

    /** «Що беруть цього тижня» на порожньому пошуку — та сама підбірка, що й на головній */
    private function popularProducts(): Collection
    {
        $collection = ProductCollection::query()->active()->where('show_on_home', true)->orderBy('sort')->first();

        return $collection
            ? $collection->products()->active()->with(['mainImage', 'variants'])->limit(4)->get()
            : Product::query()->active()->with(['mainImage', 'variants'])->orderBy('sort')->limit(4)->get();
    }
}
