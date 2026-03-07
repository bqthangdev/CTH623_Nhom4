{{-- Shared form for voucher create/edit --}}
@props(['voucher' => null])

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Mã voucher <span class="text-red-500">*</span></label>
        <input type="text" name="code" value="{{ old('code', $voucher->code ?? '') }}" placeholder="VD: SUMMER20"
            class="w-full border @error('code') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2 uppercase tracking-widest focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Loại giảm giá <span class="text-red-500">*</span></label>
            <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                <option value="fixed"   {{ old('type', $voucher->type ?? '') === 'fixed'   ? 'selected' : '' }}>Số tiền cố định</option>
                <option value="percent" {{ old('type', $voucher->type ?? '') === 'percent' ? 'selected' : '' }}>Phần trăm (%)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Giá trị <span class="text-red-500">*</span></label>
            <input type="number" name="value" value="{{ old('value', $voucher->value ?? '') }}" min="0" step="any"
                class="w-full border @error('value') border-red-400 @else border-gray-300 @enderror rounded-lg px-3 py-2">
            @error('value')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Giá trị đơn hàng tối thiểu</label>
            <input type="number" name="min_order" value="{{ old('min_order', $voucher->min_order ?? '') }}" min="0"
                placeholder="0"
                class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Số lần sử dụng tối đa</label>
            <input type="number" name="max_uses" value="{{ old('max_uses', $voucher->max_uses ?? '') }}" min="1"
                placeholder="Không giới hạn"
                class="w-full border border-gray-300 rounded-lg px-3 py-2">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ngày hết hạn</label>
        <input type="date" name="expires_at"
            value="{{ old('expires_at', isset($voucher) && $voucher->expires_at ? $voucher->expires_at->format('Y-m-d') : '') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2">
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" id="voucher_active" value="1" class="rounded"
            {{ old('is_active', $voucher->is_active ?? true) ? 'checked' : '' }}>
        <label for="voucher_active" class="text-sm text-gray-700 cursor-pointer">Kích hoạt voucher</label>
    </div>
</div>
