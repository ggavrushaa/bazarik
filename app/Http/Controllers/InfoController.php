<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Інфосторінки для покупців: MobileDelivery / DeskDelivery, MobileReturns / DeskReturns, MobileContacts / DeskContacts, оферта, конфіденційність. */
class InfoController extends Controller
{
    public function delivery(): View
    {
        return view('pages.info.delivery');
    }

    public function returns(): View
    {
        return view('pages.info.returns');
    }

    public function contacts(): View
    {
        return view('pages.info.contacts');
    }

    public function offer(): View
    {
        return view('pages.info.offer');
    }

    public function privacy(): View
    {
        return view('pages.info.privacy');
    }
}
