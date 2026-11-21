<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CartController extends Controller
{
    public function __invoke(): View
    {
        abort(404); // TODO: етап 1
    }
}
