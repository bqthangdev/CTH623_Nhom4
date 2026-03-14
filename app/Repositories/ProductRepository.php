<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ProductRepository
{
    public function getForShop(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        return Product::with(['primaryImage', 'category'])
            ->active()
            ->inStock()
            ->when($request->category, function ($q, $slug) {
                $q->whereHas('category', fn ($q) => $q->where('slug', $slug));
            })
            ->when($request->min_price, fn ($q, $v) => $q->where('price', '>=', $v))
            ->when($request->max_price, fn ($q, $v) => $q->where('price', '<=', $v))
            ->when($request->q, fn ($q, $keyword) => $q->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            }))
            ->when($request->sort === 'price_asc', fn ($q) => $q->orderBy('price'))
            ->when($request->sort === 'price_desc', fn ($q) => $q->orderByDesc('price'))
            ->when($request->sort === 'newest' || ! $request->sort, fn ($q) => $q->latest())
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::with(['images', 'category', 'attributes', 'reviews.user'])
            ->where('slug', $slug)
            ->active()
            ->first();
    }

    public function getFeatured(int $limit = 8): Collection
    {
        return Product::with('primaryImage')
            ->active()
            ->featured()
            ->inStock()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getByIds(array $ids): Collection
    {
        return Product::with('primaryImage')
            ->whereIn('id', $ids)
            ->whereHas('images')
            ->active()
            ->get();
    }

    public function getSameCategoryExcept(int $categoryId, int $excludeId, int $limit = 8): Collection
    {
        return Product::with('primaryImage')
            ->where('category_id', $categoryId)
            ->where('id', '!=', $excludeId)
            ->active()
            ->inStock()
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
