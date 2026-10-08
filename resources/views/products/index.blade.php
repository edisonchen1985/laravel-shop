<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Products</title>
</head>
<body>
    @include('partials.navigation')

    <main>
        <h1>Products</h1>

        <nav aria-label="Product categories">
            <a href="{{ route('products.index') }}">All categories</a>
            @foreach ($categories as $category)
                <a href="{{ route('products.index', ['category' => $category->slug]) }}">{{ $category->name }}</a>
            @endforeach
        </nav>

        @if ($products->isEmpty())
            <p>No products found.</p>
        @else
            <ul>
                @foreach ($products as $product)
                    <li>
                            <h2><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h2>
                        <p>Category: {{ $product->category->name }}</p>
                        <p>Price: {{ $product->price }}</p>
                        @if ($product->stock_quantity > 0)
                            <p>In stock</p>
                        @else
                            <p>Out of stock</p>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{ $products->links() }}
        @endif
    </main>
</body>
</html>
