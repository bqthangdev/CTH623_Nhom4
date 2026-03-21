@props([
    'name'        => 'image',
    'multiple'    => false,
    'maxFiles'    => 1,
    'showPreview' => true,
    'currentSrc'  => null,
    'hint'        => 'JPG, PNG, WebP — tối đa 5MB, tự động nén nếu lớn hơn',
])

<div x-data="{
    previews: [],
    dragging: false,
    async processFiles(fileList) {
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        const max = {{ $multiple ? (int) $maxFiles : 1 }};
        const files = Array.from(fileList)
            .filter(f => allowed.includes(f.type))
            .slice(0, max);
        if (!files.length) return;
        const compressed = await Promise.all(files.map(f => window._compressImage(f)));
        const dt = new DataTransfer();
        compressed.forEach(f => dt.items.add(f));
        this.$refs.fileInput.files = dt.files;
        this.previews = compressed.map(f => URL.createObjectURL(f));
    },
    async handleChange(e) { await this.processFiles(e.target.files); },
    async handleDrop(e)   { this.dragging = false; await this.processFiles(e.dataTransfer.files); }
}">

    @if($showPreview)
    {{-- Preview of newly-selected files --}}
    <template x-if="previews.length > 0">
        <div class="flex flex-wrap gap-2 mb-3">
            <template x-for="src in previews" :key="src">
                <img :src="src" class="w-24 h-20 object-cover rounded-lg border border-gray-200">
            </template>
        </div>
    </template>

    @if($currentSrc)
    {{-- Show existing image only when no new file is selected yet --}}
    <template x-if="previews.length === 0">
        <div class="mb-3">
            <p class="text-xs text-gray-500 mb-1">Ảnh hiện tại — tải lên ảnh mới bên dưới để thay thế</p>
            <img src="{{ $currentSrc }}" class="w-48 h-28 object-cover rounded-lg bg-gray-100">
        </div>
    </template>
    @endif
    @endif

    {{-- Drop zone --}}
    <div
        class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition select-none"
        :class="dragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400'"
        @click="$refs.fileInput.click()"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="handleDrop($event)">
        <div class="pointer-events-none space-y-1">
            <svg class="w-10 h-10 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
            </svg>
            <p class="text-sm text-gray-600">Kéo ảnh vào đây hoặc <span class="text-indigo-600 font-medium">nhấn để chọn</span></p>
            <p class="text-xs text-gray-400">{{ $hint }}</p>
        </div>
    </div>

    <input
        x-ref="fileInput"
        type="file"
        name="{{ $name }}"
        @if($multiple) multiple @endif
        accept="image/jpeg,image/png,image/webp"
        class="hidden"
        @change="handleChange($event)">
</div>
