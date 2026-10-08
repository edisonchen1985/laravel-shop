<label for="category_id">Category</label>
<select id="category_id" name="category_id" required>
    @foreach ($categories as $category)
        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>
            {{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}
        </option>
    @endforeach
</select>
@error('category_id') <p>{{ $message }}</p> @enderror

<label for="name">Name</label>
<input id="name" name="name" value="{{ old('name', $product->name ?? '') }}" required maxlength="200">
@error('name') <p>{{ $message }}</p> @enderror

<label for="slug">Slug</label>
<input id="slug" name="slug" value="{{ old('slug', $product->slug ?? '') }}" required maxlength="220">
@error('slug') <p>{{ $message }}</p> @enderror

<label for="description">Description</label>
<textarea id="description" name="description">{{ old('description', $product->description ?? '') }}</textarea>
@error('description') <p>{{ $message }}</p> @enderror

<label for="sku">SKU</label>
<input id="sku" name="sku" value="{{ old('sku', $product->sku ?? '') }}" maxlength="100">
@error('sku') <p>{{ $message }}</p> @enderror

<label for="price">Price</label>
<input id="price" name="price" type="number" min="0" step="0.01" value="{{ old('price', $product->price ?? '') }}" required>
@error('price') <p>{{ $message }}</p> @enderror

<label for="stock_quantity">Stock quantity</label>
<input id="stock_quantity" name="stock_quantity" type="number" min="0" step="1" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required>
@error('stock_quantity') <p>{{ $message }}</p> @enderror

<label for="status">Status</label>
<select id="status" name="status" required>
    @foreach (['draft', 'active', 'inactive'] as $status)
        <option value="{{ $status }}" @selected(old('status', $product->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
    @endforeach
</select>
@error('status') <p>{{ $message }}</p> @enderror
