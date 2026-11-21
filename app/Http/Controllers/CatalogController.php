<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function show(Category $category): View
    {
        abort(404); // TODO: етап 1
    }

    public function sale(): View
    {
        abort(404); // TODO: етап 1
    }

    public function search(): View
    {
        abort(404); // TODO: етап 1
    }
}
