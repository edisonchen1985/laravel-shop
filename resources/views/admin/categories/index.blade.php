@extends('layouts.app')

@section('title', 'Admin: Categories')

@section('content')
    <h1>Admin: Categories</h1>
    <p><a href="{{ route('admin.products.index') }}">Manage products</a></p>

    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

    <h2>Create category</h2>
    <form method="POST" action="{{ route('admin.categories.store') }}">
        @csrf
        @include('admin.categories.fields', ['category' => null])
        <button type="submit">Create category</button>
    </form>

    <h2>Existing categories</h2>
    @foreach ($categories as $category)
        <article>
            <h3>{{ $category->name }}</h3>
            <p>{{ $category->slug }} · {{ $category->products_count }} products · {{ $category->children_count }} subcategories</p>
            <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                @csrf
                @method('PATCH')
                @include('admin.categories.fields', ['category' => $category])
                <button type="submit">Save category</button>
            </form>
        </article>
    @endforeach

    {{ $categories->links() }}
@endsection
