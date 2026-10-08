@extends('layouts.app')

@section('title', 'Edit product')

@section('content')
    <h1>Edit {{ $product->name }}</h1>
    <form method="POST" action="{{ route('admin.products.update', $product) }}">
        @csrf
        @method('PATCH')
        @include('admin.products.fields')
        <button type="submit">Save changes</button>
    </form>
@endsection
