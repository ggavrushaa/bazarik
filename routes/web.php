<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuickBuyController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Каталог і пошук
Route::get('/catalog/{category:slug}', [CatalogController::class, 'show'])->name('catalog');
Route::get('/sale', [CatalogController::class, 'sale'])->name('catalog.sale');
Route::get('/search', [CatalogController::class, 'search'])->name('search');

Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product.show');

// Кошик → оформлення → «Замовлення прийнято»
Route::get('/cart', CartController::class)->name('cart');
Route::get('/checkout', CheckoutController::class)->name('checkout');
Route::get('/order/{order:public_token}', [OrderController::class, 'success'])->name('order.success');

// ── Етап 2 ──

// Вхід = реєстрація: телефон → код з SMS → ім'я (лише нові). На десктопі — модалка, ці сторінки — для телефона й без JS
Route::get('/login', [AuthController::class, 'phone'])->name('login');
Route::get('/login/code', [AuthController::class, 'code'])->name('login.code');
Route::get('/login/name', [AuthController::class, 'name'])->name('login.name');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Кабінет покупця
Route::get('/account/favorites', [AccountController::class, 'favorites'])->name('account.favorites'); // гостям теж
Route::middleware('auth:customer')->prefix('account')->name('account')->group(function () {
    Route::get('/', [AccountController::class, 'home']);
    Route::get('/orders', [AccountController::class, 'orders'])->name('.orders');
    Route::get('/orders/{order:number}', [AccountController::class, 'order'])->name('.order');
    Route::get('/addresses', [AccountController::class, 'addresses'])->name('.addresses');
});

// «Де моє замовлення»: за номером і телефоном, або за посиланням з SMS
Route::get('/tracking', [TrackingController::class, 'form'])->name('tracking');
Route::get('/track/{order:public_token}', [TrackingController::class, 'show'])->name('tracking.show');

// «Купити в 1 клік»: на телефоні — сторінка, на десктопі — модалка поверх товару
Route::get('/product/{product:slug}/quick-buy', [QuickBuyController::class, 'show'])->name('quick-buy');

// Інфосторінки
Route::get('/delivery', [InfoController::class, 'delivery'])->name('info.delivery');
Route::get('/returns', [InfoController::class, 'returns'])->name('info.returns');
Route::get('/contacts', [InfoController::class, 'contacts'])->name('info.contacts');
Route::get('/offer', [InfoController::class, 'offer'])->name('info.offer');
Route::get('/privacy', [InfoController::class, 'privacy'])->name('info.privacy');

// Оплата карткою не пройшла: замовлення тримаємо config('shop.payment_hold_hours') год
Route::get('/order/{order:public_token}/payment-failed', [OrderController::class, 'paymentFailed'])->name('order.payment-failed');
