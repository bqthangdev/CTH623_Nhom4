<?php

namespace App\Repositories;

use App\Models\ShippingCarrier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ShippingCarrierRepository
{
    public function getAllActive(): Collection
    {
        return ShippingCarrier::active()->orderBy('name')->get();
    }

    public function getPaginated(int $perPage = 20): LengthAwarePaginator
    {
        return ShippingCarrier::orderBy('name')->paginate($perPage);
    }
}
