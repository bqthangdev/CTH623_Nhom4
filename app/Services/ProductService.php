<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductEmbedding;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function create(array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($data, $images) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);

            $product = Product::create($data);

            $this->saveImages($product, $images);
            $this->saveAttributes($product, $data['attributes'] ?? []);

            return $product->load(['images', 'category']);
        });
    }

    public function update(Product $product, array $data, array $newImages = []): Product
    {
        return DB::transaction(function () use ($product, $data, $newImages) {
            if (isset($data['name']) && $data['name'] !== $product->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $product->id);
            }

            $product->update($data);

            if (! empty($newImages)) {
                $this->saveImages($product, $newImages);
            }

            if (array_key_exists('attributes', $data)) {
                $product->attributes()->delete();
                $this->saveAttributes($product, $data['attributes']);
            }

            return $product->fresh(['images', 'category', 'attributes']);
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function recordView(Product $product, ?int $userId = null): void
    {
        if ($userId) {
            \App\Models\UserActivity::create([
                'user_id'    => $userId,
                'action'     => 'view',
                'product_id' => $product->id,
            ]);
        }
    }

    private function generateUniqueSlug(string $name, int $excludeId = 0): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        while (Product::where('slug', $slug)->where('id', '!=', $excludeId)->exists()) {
            $slug = $original . '-' . $count++;
        }

        return $slug;
    }

    private function saveImages(Product $product, array $files): void
    {
        $hasPrimary = $product->primaryImage()->exists();

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => ! $hasPrimary && $index === 0,
                'sort_order' => $product->images()->count() + $index,
            ]);

            if (! $hasPrimary && $index === 0) {
                $hasPrimary = true;
            }
        }
    }

    private function saveAttributes(Product $product, array $attributes): void
    {
        foreach ($attributes as $attr) {
            if (! empty($attr['key']) && ! empty($attr['value'])) {
                ProductAttribute::create([
                    'product_id' => $product->id,
                    'key'        => $attr['key'],
                    'value'      => $attr['value'],
                ]);
            }
        }
    }
}
