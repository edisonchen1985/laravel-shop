<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function getCartForUser(User $user): ?Cart
    {
        return $user->cart()
            ->with([
                'items.product' => fn ($query) => $query->withTrashed(),
            ])
            ->first();
    }

    public function addItem(User $user, int $productId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($user, $productId, $quantity): CartItem {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $cart = $lockedUser->cart()->firstOrCreate([]);

            // Product's SoftDeletes global scope excludes soft-deleted products.
            $product = Product::query()
                ->whereKey($productId)
                ->lockForUpdate()
                ->first();

            if (! $product || $product->status !== 'active') {
                throw ValidationException::withMessages([
                    'product_id' => 'This product is unavailable for purchase.',
                ]);
            }

            if ($quantity > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'The requested quantity exceeds available stock.',
                ]);
            }

            $cartItem = $cart->items()
                ->where('product_id', $product->getKey())
                ->first();

            if ($cartItem) {
                $newQuantity = $cartItem->quantity + $quantity;

                if ($newQuantity > $product->stock_quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'The requested quantity exceeds available stock.',
                    ]);
                }

                $cartItem->quantity = $newQuantity;
                $cartItem->save();

                return $cartItem;
            }

            return $cart->items()->create([
                'product_id' => $product->getKey(),
                'quantity' => $quantity,
            ]);
        });
    }
}
