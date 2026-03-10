@php $method = $method ?? null; @endphp
<div class="space-y-5" x-data="{ isExternal: {{ ($method?->is_external ?? false) ? 'true' : 'false' }}, configPairs: {{ json_encode(collect($method?->config ?? [])->map(fn($v, $k) => ['key' => $k, 'value' => $v])->values()->all()) }} }">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tên hiển thị <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $method?->name) }}"
                class="w-full border @error('name') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        @if(!isset($method))
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mã định danh <span class="text-red-500">*</span></label>
            <input type="text" name="code" value="{{ old('code') }}" placeholder="vd: cod, momo, zalopay"
                class="w-full border @error('code') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none font-mono">
            <p class="text-xs text-gray-400 mt-1">Chỉ chữ thường, số, dấu gạch dưới. Không thể thay đổi sau khi tạo.</p>
            @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        @else
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mã định danh</label>
            <input type="text" value="{{ $method->code }}" disabled
                class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-gray-500 font-mono cursor-not-allowed">
            <p class="text-xs text-gray-400 mt-1">Mã định danh không thể thay đổi.</p>
        </div>
        @endif
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
        <textarea name="description" rows="2"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">{{ old('description', $method?->description) }}</textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Thứ tự hiển thị</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $method?->sort_order ?? 0) }}" min="0"
                class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>

        <div class="flex flex-col gap-3 pt-1">
            <label class="flex items-center gap-2 cursor-pointer mt-4">
                <input type="checkbox" name="is_active" value="1" class="rounded"
                    {{ old('is_active', $method?->is_active ?? true) ? 'checked' : '' }}>
                <span class="text-sm font-medium text-gray-700">Kích hoạt</span>
            </label>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_external" value="1" class="rounded"
                    x-model="isExternal"
                    {{ old('is_external', $method?->is_external ?? false) ? 'checked' : '' }}>
                <span class="text-sm font-medium text-gray-700">Cổng thanh toán ngoài (cần API key)</span>
            </label>
        </div>
    </div>

    {{-- Config section for external payment gateways --}}
    <div x-show="isExternal" x-cloak>
        <div class="border border-blue-100 bg-blue-50 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-blue-800">Cấu hình API</h3>
                <button type="button"
                    @click="configPairs.push({ key: '', value: '' })"
                    class="text-xs px-2.5 py-1 rounded border border-blue-400 text-blue-700 hover:bg-blue-100 transition">
                    + Thêm tham số
                </button>
            </div>

            <template x-if="configPairs.length === 0">
                <p class="text-xs text-blue-600 italic">Chưa có tham số nào. Nhấn "+ Thêm tham số" để bắt đầu.</p>
            </template>

            <div class="space-y-2">
                <template x-for="(pair, index) in configPairs" :key="index">
                    <div class="flex gap-2 items-center">
                        <input type="text"
                            :name="`config_keys[${index}]`"
                            x-model="pair.key"
                            placeholder="Tên tham số (vd: tmn_code)"
                            class="flex-1 border border-gray-300 rounded px-2.5 py-1.5 text-sm font-mono focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        <input type="text"
                            :name="`config_values[${index}]`"
                            x-model="pair.value"
                            placeholder="Giá trị"
                            class="flex-1 border border-gray-300 rounded px-2.5 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        <button type="button"
                            @click="configPairs.splice(index, 1)"
                            class="text-red-400 hover:text-red-600 text-lg leading-none px-1">×</button>
                    </div>
                </template>
            </div>

            <p class="text-xs text-blue-500 mt-2">Lưu ý: Các giá trị này được mã hóa và lưu an toàn trong cơ sở dữ liệu.</p>
        </div>
    </div>

</div>
