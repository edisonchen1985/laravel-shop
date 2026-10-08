<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function checkout(User $user): Order
    {
        return DB::transaction(function () use ($user): Order {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $cart = $lockedUser->cart()->lockForUpdate()->first();

            if (! $cart) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty.',
                ]);
            }

            $cartItems = $cart->items()
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty.',
                ]);
            }

            $order = Order::create([
                'user_id' => $lockedUser->getKey(),
                'order_number' => 'ORD-'.Str::ulid(),
                'status' => 'pending',
                'customer_name' => $lockedUser->name,
                'customer_email' => $lockedUser->email,
                'subtotal' => '0.00',
                'total' => '0.00',
                'placed_at' => now(),
            ]);

            $subtotalCents = 0;

            foreach ($cartItems as $cartItem) {
                if ($cartItem->quantity < 1) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Cart item quantities must be at least one.',
                    ]);
                }

                $product = Product::withTrashed()
                    ->whereKey($cartItem->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product || $product->trashed() || $product->status !== 'active') {
                    throw ValidationException::withMessages([
                        'product_id' => 'A product in your cart is unavailable for purchase.',
                    ]);
                }

                $category = $product->category()->lockForUpdate()->first();

                if (! $category || ! $category->is_active) {
                    throw ValidationException::withMessages([
                        'product_id' => 'A product in your cart is unavailable for purchase.',
                    ]);
                }

                if ($cartItem->quantity > $product->stock_quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'A product in your cart does not have enough stock.',
                    ]);
                }

                $unitPriceCents = $this->toCents((string) $product->price);
                $lineTotalCents = $unitPriceCents * $cartItem->quantity;
                $subtotalCents += $lineTotalCents;

                $order->items()->create([
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $this->fromCents($unitPriceCents),
                    'quantity' => $cartItem->quantity,
                    'line_total' => $this->fromCents($lineTotalCents),
                ]);

                $product->stock_quantity -= $cartItem->quantity;
                $product->save();
            }

            $order->subtotal = $this->fromCents($subtotalCents);
            $order->total = $this->fromCents($subtotalCents);
            $order->save();

            $cart->items()->delete();

            return $order;
        });
    }

    private function toCents(string $amount): int
    {
        [$units, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $units * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function fromCents(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
