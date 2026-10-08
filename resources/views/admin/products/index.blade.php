@extends('layouts.app')

@section('title', 'Admin: Products')

@section('content')
    <h1>Admin: Products</h1>
    <p><a href="{{ route('admin.categories.index') }}">Manage categories</a></p>
    <p><a href="{{ route('admin.products.create') }}">Create product</a></p>

    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

    @forelse ($products as $product)
        <article>
            <h2>{{ $product->name }}</h2>
            <p>{{ $product->category->name }} · {{ $product->status }} · {{ $product->price }}</p>
            <a href="{{ route('admin.products.edit', $product) }}">Edit</a>
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}">
                @csrf
                @method('DELETE')
                <button type="submit">Unpublish</button>
            </form>
        </article>
    @empty
        <p>No products yet.</p>
    @endforelse

    {{ $products->links() }}
@endsection
