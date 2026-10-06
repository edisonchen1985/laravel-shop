<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(CartService $cartService): View
    {
        /** @var User $user */
        $user = request()->user();
        $cart = $cartService->getCartForUser($user);

        return view('cart.index', compact('cart'));
    }

    public function store(AddCartItemRequest $request, CartService $cartService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $cartService->addItem($user, (int) $data['product_id'], (int) $data['quantity']);

        return redirect()->back()->with('success', 'Product added to your cart.');
    }

    public function update(UpdateCartItemRequest $request, int $cartItem, CartService $cartService): RedirectResponse
    {
        /** @var User $user */
        $user = request()->user();

        $cartService->updateItem($user, $cartItem, (int) $request->validated('quantity'));

        return redirect()->back()->with('success', 'Cart quantity updated.');
    }

    public function destroy(int $cartItem, CartService $cartService): RedirectResponse
    {
        /** @var User $user */
        $user = request()->user();

        $cartService->removeItem($user, $cartItem);

        return redirect()->back()->with('success', 'Product removed from your cart.');
    }
}
