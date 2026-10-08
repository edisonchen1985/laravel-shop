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

    <section>
        <h2>Product images</h2>

        <form method="POST" action="{{ route('admin.products.images.store', $product) }}" enctype="multipart/form-data">
            @csrf
            <label for="image">Upload image</label>
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" required>
            @error('image') <p>{{ $message }}</p> @enderror

            <label for="image_alt_text">Alt text</label>
            <input id="image_alt_text" name="alt_text" maxlength="255" value="{{ old('alt_text') }}">
            @error('alt_text') <p>{{ $message }}</p> @enderror

            <label for="image_sort_order">Sort order</label>
            <input id="image_sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', 0) }}">
            @error('sort_order') <p>{{ $message }}</p> @enderror

            <label>
                <input name="is_primary" type="checkbox" value="1">
                Set as primary image
            </label>
            <button type="submit">Upload image</button>
        </form>

        @if ($product->images->isEmpty())
            <p>No images have been uploaded.</p>
        @else
            <ul>
                @foreach ($product->images as $image)
                    <li>
                        <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text ?? $product->name }}" width="160">
                        @if ($image->is_primary)
                            <strong>Primary</strong>
                        @endif

                        <form method="POST" action="{{ route('admin.products.images.update', [$product, $image]) }}">
                            @csrf
                            @method('PATCH')
                            <label for="alt_text_{{ $image->id }}">Alt text</label>
                            <input id="alt_text_{{ $image->id }}" name="alt_text" maxlength="255" value="{{ old('alt_text', $image->alt_text) }}">
                            <label for="sort_order_{{ $image->id }}">Sort order</label>
                            <input id="sort_order_{{ $image->id }}" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $image->sort_order) }}" required>
                            <label>
                                <input name="is_primary" type="hidden" value="0">
                                <input name="is_primary" type="checkbox" value="1" @checked(old('is_primary', $image->is_primary))>
                                Primary
                            </label>
                            <button type="submit">Save image details</button>
                        </form>

                        <form method="POST" action="{{ route('admin.products.images.destroy', [$product, $image]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Delete image</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
