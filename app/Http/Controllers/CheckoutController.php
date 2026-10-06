<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;

class CheckoutController extends Controller
{
    public function store(CheckoutService $checkoutService): RedirectResponse
    {
        /** @var User $user */
        $user = request()->user();
        $order = $checkoutService->checkout($user);

        return redirect()->route('cart.index')
            ->with('success', "Order {$order->order_number} placed successfully.");
    }
}
