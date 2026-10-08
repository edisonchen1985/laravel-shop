<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CartService
{
    public function getCartForUser(User $user): ?Cart
    {
        return $user->cart()
            ->with([
                'items.product' => fn ($query) => $query->withTrashed()->with('category'),
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

            $cart = $lockedUser->cart()->lockForUpdate()->first();

            if (! $cart) {
                $cart = $lockedUser->cart()->create([]);
            }

            $cartItem = $cart->items()
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

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

            $category = $product->category()->lockForUpdate()->first();

            if (! $category || ! $category->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'This product is unavailable for purchase.',
                ]);
            }

            if ($quantity > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'The requested quantity exceeds available stock.',
                ]);
            }

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

    public function updateItem(User $user, int $cartItemId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($user, $cartItemId, $quantity): CartItem {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $cart = $lockedUser->cart()->lockForUpdate()->first();

            if (! $cart) {
                throw new NotFoundHttpException;
            }

            $cartItem = $cart->items()
                ->whereKey($cartItemId)
                ->lockForUpdate()
                ->firstOrFail();

            $product = Product::withTrashed()
                ->whereKey($cartItem->product_id)
                ->lockForUpdate()
                ->first();

            if (! $product || $product->trashed() || $product->status !== 'active') {
                throw ValidationException::withMessages([
                    'product_id' => 'This product is unavailable for purchase.',
                ]);
            }

            $category = $product->category()->lockForUpdate()->first();

            if (! $category || ! $category->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'This product is unavailable for purchase.',
                ]);
            }

            if ($quantity > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'The requested quantity exceeds available stock.',
                ]);
            }

            $cartItem->quantity = $quantity;
            $cartItem->save();

            return $cartItem;
        });
    }

    public function removeItem(User $user, int $cartItemId): void
    {
        DB::transaction(function () use ($user, $cartItemId): void {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $cart = $lockedUser->cart()->lockForUpdate()->first();

            if (! $cart) {
                throw new NotFoundHttpException;
            }

            $cart->items()
                ->whereKey($cartItemId)
                ->lockForUpdate()
                ->firstOrFail()
                ->delete();
        });
    }
}
