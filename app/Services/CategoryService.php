<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryService
{
    public function create(array $data, ?string $imagePath = null): Category
    {
        $data['slug'] = Str::slug($data['name']);

        if ($imagePath) {
            $data['image'] = $imagePath;
        }

        return Category::create($data);
    }

    public function update(Category $category, array $data, ?string $imagePath = null): Category
    {
        $data['slug'] = Str::slug($data['name']);

        if ($imagePath) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $imagePath;
        }

        $category->update($data);

        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();
    }
}
