<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartItemRequest;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    public function store(AddCartItemRequest $request, CartService $cartService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $cartService->addItem($user, (int) $data['product_id'], (int) $data['quantity']);

        return redirect()->back()->with('success', 'Product added to your cart.');
    }
}
