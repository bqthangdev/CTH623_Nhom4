<?php

namespace Database\Seeders;

use App\Models\ShippingCarrier;
use Illuminate\Database\Seeder;

class ShippingCarrierSeeder extends Seeder
{
    public function run(): void
    {
        $carriers = [
            'SPX Express',
            'Viettel Post',
            'Vietnam Post',
            'J&T Express',
        ];

        foreach ($carriers as $name) {
            ShippingCarrier::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
