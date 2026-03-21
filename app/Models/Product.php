<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'sale_price',
        'stock',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'sale_price'  => 'decimal:2',
        'stock'       => 'integer',
        'status'      => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function embedding(): HasOne
    {
        return $this->hasOne(ProductEmbedding::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->sale_price > 0 ? (float) $this->sale_price : (float) $this->price;
    }

    public function getAverageRatingAttribute(): ?float
    {
        // Use pre-computed aggregate from withAvg('reviews', 'rating') — no N+1
        if (array_key_exists('reviews_avg_rating', $this->attributes)) {
            $avg = $this->attributes['reviews_avg_rating'];
            return $avg !== null ? (float) round((float) $avg, 1) : null;
        }

        // Fall back to loaded relationship
        if (! $this->relationLoaded('reviews')) {
            return null;
        }

        $count = $this->reviews->count();
        return $count > 0 ? (float) round($this->reviews->avg('rating'), 1) : null;
    }

    public function getImageUrlAttribute(): string
    {
        $primary = $this->primaryImage ?? $this->images->first();

        if ($primary && Storage::disk('public')->exists($primary->image_path)) {
            return asset('storage/' . $primary->image_path);
        }

        return asset('images/no-image.svg');
    }
}
