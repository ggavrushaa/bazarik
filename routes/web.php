<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
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
