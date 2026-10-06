<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/cart', [CartController::class, 'index'])
    ->middleware('auth')
    ->name('cart.index');

Route::post('/cart/items', [CartController::class, 'store'])
    ->middleware('auth')
    ->name('cart.items.store');

Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])
    ->middleware('auth')
    ->name('cart.items.update');

Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])
    ->middleware('auth')
    ->name('cart.items.destroy');

Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('auth')
    ->name('checkout.store');

Route::get('/orders', [OrderController::class, 'index'])
    ->middleware('auth')
    ->name('orders.index');

Route::get('/orders/{order}', [OrderController::class, 'show'])
    ->middleware('auth')
    ->name('orders.show');
