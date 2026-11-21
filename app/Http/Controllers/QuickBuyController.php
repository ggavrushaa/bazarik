<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * «Купити в 1 клік» сторінкою — для телефона й без JS: QuickBuyGuest / QuickBuyUser.
 * На десктопі ту саму форму відкриває модалка QuickBuyModal поверх сторінки товару.
 */
class QuickBuyController extends Controller
{
    public function show(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $variants = $product->variants()->where('is_active', true)->get();
        abort_if($variants->isEmpty(), 404);

        $variant = $request->integer('variant')
            ? $variants->firstWhere('id', $request->integer('variant'))
            : null;

        // Без вибору: одяг — спершу обрати розмір на сторінці товару; інше — варіант з найбільшим залишком, як «Додати в кошик»
        if (! $variant) {
            if ($product->is_apparel && $variants->count() > 1 && $variants->whereNotNull('size')->isNotEmpty()) {
                return redirect()->to(route('product.show', $product).'#sizes');
            }
            $variant = $variants->sortByDesc('stock')->first();
        }

        return view('pages.quick-buy', ['product' => $product, 'variant' => $variant]);
    }
}
