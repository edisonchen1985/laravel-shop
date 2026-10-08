<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Cart</title>
</head>
<body>
    @include('partials.navigation')

    <main>
        <h1>Your Cart</h1>
        @if (session('success'))
            <p role="status">{{ session('success') }}</p>
        @endif

        @if (! $cart || $cart->items->isEmpty())
            <p>Your cart is empty.</p>
        @else
            @php
                $hasUnavailableItems = $cart->items->contains(function ($item) {
                    $product = $item->product;

                    return ! $product
                        || $product->trashed()
                        || $product->status !== 'active'
                        || ! $product->category
                        || ! $product->category->is_active
                        || $product->stock_quantity < 1
                        || $item->quantity > $product->stock_quantity;
                });
            @endphp

            <ul>
                @foreach ($cart->items as $item)
                    @php
                        $product = $item->product;
                        $unavailable = ! $product
                            || $product->trashed()
                            || $product->status !== 'active'
                            || ! $product->category
                            || ! $product->category->is_active
                            || $product->stock_quantity < 1
                            || $item->quantity > $product->stock_quantity;
                    @endphp

                    <li>
                        <span>{{ $product?->name ?? 'Unavailable product' }}</span>
                        <span>Quantity: {{ $item->quantity }}</span>
                        @if ($product && ! $unavailable)
                            <span>Unit price: {{ $product->price }}</span>
                        @endif

                        @if ($unavailable)
                            <span>Unavailable</span>
                        @else
                            <form method="POST" action="{{ route('cart.items.update', $item) }}">
                                @csrf
                                @method('PATCH')
                                <label for="quantity-{{ $item->id }}">Quantity</label>
                                <input
                                    id="quantity-{{ $item->id }}"
                                    name="quantity"
                                    type="number"
                                    min="1"
                                    max="{{ $product->stock_quantity }}"
                                    value="{{ $item->quantity }}"
                                    required
                                >
                                <button type="submit">Update quantity</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Remove</button>
                        </form>
                    </li>
                @endforeach
            </ul>

            @if ($hasUnavailableItems)
                <p>Remove unavailable items before checkout. Totals use current product prices.</p>
            @endif

            <p>Subtotal: {{ $cart->subtotal }}</p>
            <p>Total: {{ $cart->total }}</p>

            @if (! $hasUnavailableItems)
                <form method="POST" action="{{ route('checkout.store') }}">
                    @csrf
                    <button type="submit">Checkout</button>
                </form>
            @endif
        @endif

        @if (! $cart || $cart->items->isEmpty())
            <p>Subtotal: {{ $cart?->subtotal ?? '0.00' }}</p>
            <p>Total: {{ $cart?->total ?? '0.00' }}</p>
        @endif
    </main>
</body>
</html>
