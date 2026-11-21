<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function show(Category $category): View
    {
        abort_unless($category->is_active, 404);
        $category->loadMissing('parent');

        return view('pages.catalog', [
            'mode' => 'category',
            'category' => $category,
            'title' => $category->seo_title ?: $category->name,
        ]);
    }

    public function sale(): View
    {
        return view('pages.catalog', [
            'mode' => 'sale',
            'category' => null,
            'title' => 'Знижки',
        ]);
    }

    public function search(Request $request): View
    {
        $q = mb_substr(trim((string) $request->string('q')), 0, 100);

        return view('pages.catalog', [
            'mode' => 'search',
            'category' => null,
            'q' => $q,
            'title' => $q !== '' ? 'Пошук: '.$q : 'Пошук',
        ]);
    }
}
