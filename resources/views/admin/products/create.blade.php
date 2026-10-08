@extends('layouts.app')

@section('title', 'Create product')

@section('content')
    <h1>Create product</h1>
    <form method="POST" action="{{ route('admin.products.store') }}">
        @csrf
        @include('admin.products.fields')
        <button type="submit">Create</button>
    </form>
@endsection
