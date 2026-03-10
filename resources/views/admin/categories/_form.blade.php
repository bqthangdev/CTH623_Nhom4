@props(['category' => null, 'parents'])

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tên danh mục <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}"
            class="w-full border @error('name') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục cha</label>
        <select name="parent_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <option value="">— Không có —</option>
            @foreach($parents as $parent)
            <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id ?? '') == $parent->id ? 'selected' : '' }}>
                {{ $parent->name }}
            </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ảnh danh mục</label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
            class="block text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-indigo-50 file:text-indigo-600">
        @if(isset($category) && $category->image_url)
        <img src="{{ $category->image_url }}" alt="" class="w-20 h-20 object-cover rounded-lg mt-2 bg-gray-100">
        @endif
        @error('image')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded"
            {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
        <label for="is_active" class="text-sm text-gray-700 cursor-pointer">Hiển thị trên cửa hàng</label>
    </div>
</div>
