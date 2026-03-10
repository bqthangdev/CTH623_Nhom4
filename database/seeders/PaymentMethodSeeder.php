<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name'        => 'Thanh toán khi nhận hàng (COD)',
                'code'        => 'cod',
                'description' => 'Thanh toán bằng tiền mặt khi nhận hàng.',
                'is_external' => false,
                'config'      => null,
                'is_active'   => true,
                'sort_order'  => 1,
            ],
            [
                'name'        => 'VNPay',
                'code'        => 'vnpay',
                'description' => 'Thanh toán qua cổng VNPay (thẻ nội địa, thẻ quốc tế, ví VNPay).',
                'is_external' => true,
                'config'      => [
                    'tmn_code'   => '',
                    'hash_secret' => '',
                    'url'        => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
                    'return_url' => '',
                ],
                'is_active'   => true,
                'sort_order'  => 2,
            ],
        ];

        foreach ($methods as $data) {
            PaymentMethod::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
