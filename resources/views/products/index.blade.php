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

        <form method="GET" action="{{ route('products.index') }}">
            <label for="search">Search products</label>
            <input id="search" name="search" type="search" value="{{ $search }}" maxlength="200">

            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected($categorySlug === $category->slug)>{{ $category->name }}</option>
                @endforeach
            </select>

            <button type="submit">Search</button>
        </form>

        @if ($products->isEmpty())
            <p>No products found.</p>
        @else
            <ul>
                @foreach ($products as $product)
                    <li>
                        @if ($product->images->isNotEmpty())
                            <a href="{{ route('products.show', $product->slug) }}">
                                <img src="{{ asset('storage/'.$product->images->first()->path) }}" alt="{{ $product->images->first()->alt_text ?? $product->name }}" width="180">
                            </a>
                        @endif
                        <h2><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h2>
                        <p>Category: {{ $product->category->name }}</p>
                        <p>Price: {{ $product->price }}</p>
                        <p>Status: Available</p>
                        <a href="{{ route('products.show', $product->slug) }}">View product</a>
                    </li>
                @endforeach
            </ul>

            {{ $products->links() }}
        @endif
    </main>
</body>
</html>
