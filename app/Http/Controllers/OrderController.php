<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class OrderController extends Controller
{
    public function success(): View
    {
        abort(404); // TODO: етап 1
    }
}
