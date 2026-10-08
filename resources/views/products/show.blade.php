<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product->name }}</title>
</head>
<body>
    @include('partials.navigation')

    <main>
        <p><a href="{{ route('products.index') }}">Back to products</a></p>
        <h1>{{ $product->name }}</h1>
        <p>Category: {{ $product->category->name }}</p>
        <p>Price: {{ $product->price }}</p>
        @if ($product->sku)
            <p>SKU: {{ $product->sku }}</p>
        @endif
        @if ($product->description)
            <p>{{ $product->description }}</p>
        @endif

        @if ($product->images->isNotEmpty())
            <ul>
                @foreach ($product->images as $image)
                    <li>
                        <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text ?? $product->name }}" width="240">
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($product->stock_quantity > 0)
            @auth
                <form method="POST" action="{{ route('cart.items.store') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <label for="quantity">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="1" max="{{ $product->stock_quantity }}" value="1" required>
                    <button type="submit">Add to cart</button>
                </form>
            @else
                <p><a href="{{ route('login') }}">Log in</a> to add this item to your cart.</p>
            @endauth
        @else
            <p>Out of stock</p>
        @endif
    </main>
</body>
</html>
