@props(['carrier' => null])

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tên đơn vị vận chuyển <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $carrier->name ?? '') }}"
            class="w-full border @error('name') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none"
            placeholder="VD: SPX Express, Viettel Post...">
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                {{ old('is_active', $carrier->is_active ?? true) ? 'checked' : '' }}
                class="w-4 h-4 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500">
            <span class="text-sm font-medium text-gray-700">Đang hoạt động</span>
        </label>
    </div>
</div>
