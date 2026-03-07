{{-- Shared form fields for create/edit product --}}

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Tên sản phẩm <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}"
            class="w-full border @error('name') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục <span class="text-red-500">*</span></label>
        <select name="category_id" class="w-full border @error('category_id') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <option value="">-- Chọn danh mục --</option>
            @foreach(\App\Models\Category::active()->orderBy('name')->get() as $cat)
            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                {{ $cat->name }}
            </option>
            @endforeach
        </select>
        @error('category_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Giá gốc (đ) <span class="text-red-500">*</span></label>
        <input type="number" name="price" value="{{ old('price', $product->price ?? '') }}" min="0"
            class="w-full border @error('price') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        @error('price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Giá khuyến mãi (đ)</label>
        <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price ?? '') }}" min="0"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        @error('sale_price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tồn kho <span class="text-red-500">*</span></label>
        <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" min="0"
            class="w-full border @error('stock') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        @error('stock')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="hidden" name="status" value="0">
                <input type="checkbox" name="status" value="1" class="rounded"
                    {{ old('status', $product->status ?? true) ? 'checked' : '' }}>
                <span class="text-sm">Hiển thị trên cửa hàng</span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" name="is_featured" value="1" class="rounded"
                    {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}>
                <span class="text-sm">Nổi bật</span>
            </label>
        </div>
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
    <textarea name="description" rows="4"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">{{ old('description', $product->description ?? '') }}</textarea>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">
        Ảnh sản phẩm {{ isset($product) ? '(thêm ảnh mới)' : '*' }}
    </label>
    <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
        class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
    @error('images')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    @error('images.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>
