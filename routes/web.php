<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
