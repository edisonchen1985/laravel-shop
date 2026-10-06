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

        @if (! $cart || $cart->items->isEmpty())
            <p>Your cart is empty.</p>
        @else
            <ul>
                @foreach ($cart->items as $item)
                    @php
                        $product = $item->product;
                        $unavailable = ! $product || $product->trashed() || $product->status !== 'active';
                    @endphp

                    <li>
                        <span>{{ $product?->name ?? 'Unavailable product' }}</span>
                        <span>Quantity: {{ $item->quantity }}</span>

                        @if ($unavailable)
                            <span>Unavailable</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </main>
</body>
</html>
