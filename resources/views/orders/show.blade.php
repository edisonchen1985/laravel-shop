<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order {{ $order->order_number }}</title>
</head>
<body>
    @include('partials.navigation')

    <main>
        <p><a href="{{ route('orders.index') }}">Back to my orders</a></p>
        <h1>Order {{ $order->order_number }}</h1>

        <p>Status: {{ $order->status }}</p>
        <p>Placed: {{ $order->placed_at?->format('Y-m-d H:i') ?? $order->created_at->format('Y-m-d H:i') }}</p>
        <p>Customer: {{ $order->customer_name }}</p>
        <p>Email: {{ $order->customer_email }}</p>
        <p>Subtotal: {{ $order->subtotal }}</p>
        <p>Total: {{ $order->total }}</p>

        @if ($order->notes)
            <p>Notes: {{ $order->notes }}</p>
        @endif

        <h2>Items</h2>
        @if ($order->items->isEmpty())
            <p>This order has no items.</p>
        @else
            <ul>
                @foreach ($order->items as $item)
                    <li>
                        {{ $item->product_name }}
                        @if ($item->sku)
                            (SKU: {{ $item->sku }})
                        @endif
                        — Unit price: {{ $item->unit_price }}
                        — Quantity: {{ $item->quantity }}
                        — Line total: {{ $item->line_total }}
                    </li>
                @endforeach
            </ul>
        @endif
    </main>
</body>
</html>
