<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Сторінка товару: MobileProduct / MobileApparel / DeskProduct, а коли залишку немає —
 * StateOutOfStock / DeskOutOfStock.
 */
class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category.parent',
            'images',
            'variants' => fn ($q) => $q->where('is_active', true),
            'bundleProduct' => fn ($q) => $q->active()->with(['mainImage', 'variants' => fn ($v) => $v->where('is_active', true)]),
        ]);

        $inStock = $product->total_stock > 0;

        $bundle = $product->bundleProduct;
        if ($bundle && ($bundle->total_stock === 0 || ! $product->bundle_discount || $product->is_apparel || $bundle->is_apparel)) {
            $bundle = null;
        }

        return view('pages.product', [
            'product' => $product,
            'inStock' => $inStock,
            'reviews' => $product->reviews()->published()->latest()->limit(4)->get(),
            'bundle' => $bundle,
            // «Часто беруть разом» або, коли товару немає, «Схожі в наявності»
            'related' => $this->related($product),
        ]);
    }

    /** Товари в наявності з тієї ж гілки каталогу: спершу з тієї ж категорії */
    private function related(Product $product, int $limit = 4): Collection
    {
        $category = $product->category;
        if (! $category) {
            return collect();
        }

        $root = $category->parent ?? $category;
        $branch = Category::query()->active()->where('parent_id', $root->id)->pluck('id')->push($root->id);

        return Product::query()->active()->inStock()
            ->with(['mainImage', 'variants'])
            ->whereIn('category_id', $branch)
            ->whereKeyNot($product->id)
            ->orderByRaw('category_id = ? desc', [$category->id])
            ->orderBy('sort')
            ->limit($limit)
            ->get();
    }
}
