<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __invoke(): View
    {
        abort(404); // TODO: етап 1
    }
}
