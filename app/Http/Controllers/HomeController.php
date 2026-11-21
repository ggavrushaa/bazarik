<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        // «Що беруть цього тижня» — підбірка з позначкою «на головній»; якщо її немає — перші товари
        $collection = Collection::query()->active()->where('show_on_home', true)->orderBy('sort')->first();
        $products = $collection
            ? $collection->products()->active()->with(['mainImage', 'variants'])->limit(4)->get()
            : Product::query()->active()->with(['mainImage', 'variants'])->orderBy('sort')->limit(4)->get();

        return view('pages.home', [
            'categories' => Category::query()->active()->root()->orderBy('sort')->get(),
            'products' => $products,
            'popularTitle' => $collection?->name ?? 'Що беруть цього тижня',
        ]);
    }
}
