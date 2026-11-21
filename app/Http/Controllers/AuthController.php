<?php

namespace App\Http\Controllers;

use App\Actions\Auth\CompleteLogin;
use App\Livewire\AuthPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Вхід = реєстрація: телефон → код з SMS → ім'я (лише нові). Логіка — у Livewire AuthPage / AuthModal
 * та app/Actions/Auth; тут лише сторінки й вихід.
 */
class AuthController extends Controller
{
    public function phone(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('account');
        }

        return view('pages.auth.login', ['step' => 'phone']);
    }

    public function code(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('account');
        }
        if (! session(AuthPage::PHONE_KEY)) {
            return redirect()->route('login');
        }

        return view('pages.auth.login', ['step' => 'code']);
    }

    public function name(): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        if (! $customer) {
            return redirect()->route('login');
        }
        if (! CompleteLogin::needsName($customer)) {
            return redirect()->intended(route('account'));
        }

        return view('pages.auth.login', ['step' => 'name']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
