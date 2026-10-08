<label for="name-{{ $category?->id ?? 'new' }}">Name</label>
<input id="name-{{ $category?->id ?? 'new' }}" name="name" value="{{ old('name', $category?->name ?? '') }}" required maxlength="150">
@error('name') <p>{{ $message }}</p> @enderror

<label for="slug-{{ $category?->id ?? 'new' }}">Slug</label>
<input id="slug-{{ $category?->id ?? 'new' }}" name="slug" value="{{ old('slug', $category?->slug ?? '') }}" required maxlength="180">
@error('slug') <p>{{ $message }}</p> @enderror

<label for="description-{{ $category?->id ?? 'new' }}">Description</label>
<textarea id="description-{{ $category?->id ?? 'new' }}" name="description">{{ old('description', $category?->description ?? '') }}</textarea>
@error('description') <p>{{ $message }}</p> @enderror

<label for="is_active-{{ $category?->id ?? 'new' }}">Active</label>
<select id="is_active-{{ $category?->id ?? 'new' }}" name="is_active" required>
    <option value="1" @selected((string) old('is_active', $category?->is_active ?? 1) === '1')>Yes</option>
    <option value="0" @selected((string) old('is_active', $category?->is_active ?? 1) === '0')>No</option>
</select>
@error('is_active') <p>{{ $message }}</p> @enderror
