<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            'categories' => Category::query()->active()->root()->orderBy('sort')->get(),
            'products' => Product::query()->active()->with('mainImage')->orderBy('sort')->limit(8)->get(),
        ]);
    }
}
