<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_external',
        'config',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_external' => 'boolean',
        'is_active'   => 'boolean',
        'config'      => 'array',
        'sort_order'  => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }
}
