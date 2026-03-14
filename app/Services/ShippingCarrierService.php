<?php

namespace App\Services;

use App\Models\ShippingCarrier;
use App\Repositories\ShippingCarrierRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ShippingCarrierService
{
    public function __construct(
        private readonly ShippingCarrierRepository $carrierRepository,
    ) {}

    public function getAll(): LengthAwarePaginator
    {
        return $this->carrierRepository->getPaginated();
    }

    public function getAllActive(): Collection
    {
        return $this->carrierRepository->getAllActive();
    }

    public function create(array $data): ShippingCarrier
    {
        return ShippingCarrier::create([
            'name'      => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(ShippingCarrier $carrier, array $data): ShippingCarrier
    {
        $carrier->update([
            'name'      => $data['name'],
            'is_active' => $data['is_active'] ?? false,
        ]);

        return $carrier->fresh();
    }

    public function delete(ShippingCarrier $carrier): void
    {
        $carrier->delete();
    }
}
