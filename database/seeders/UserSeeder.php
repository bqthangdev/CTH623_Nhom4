<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@smartshop.local'],
            [
                'name'      => 'Admin',
                'password'  => Hash::make('password'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@smartshop.local'],
            [
                'name'      => 'Khách hàng Demo',
                'password'  => Hash::make('password'),
                'role'      => 'customer',
                'phone'     => '0901234567',
                'address'   => '123 Đường Lê Lợi, Quận 1, TP.HCM',
                'is_active' => true,
            ]
        );
    }
}
