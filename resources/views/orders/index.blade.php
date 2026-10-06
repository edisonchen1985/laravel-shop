<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders</title>
</head>
<body>
    <main>
        <h1>My Orders</h1>

        @if ($orders->isEmpty())
            <p>You have no orders yet.</p>
        @else
            <ul>
                @foreach ($orders as $order)
                    <li>
                        <h2>
                            <a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a>
                        </h2>
                        <p>Status: {{ $order->status }}</p>
                        <p>Placed: {{ $order->placed_at?->format('Y-m-d H:i') ?? $order->created_at->format('Y-m-d H:i') }}</p>
                        <p>Total: {{ $order->total }}</p>

                        <ul>
                            @foreach ($order->items as $item)
                                <li>
                                    {{ $item->product_name }}
                                    @if ($item->sku)
                                        (SKU: {{ $item->sku }})
                                    @endif
                                    — Quantity: {{ $item->quantity }}
                                    — Line total: {{ $item->line_total }}
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>

            {{ $orders->links() }}
        @endif
    </main>
</body>
</html>
