<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal    = fake()->randomFloat(2, 100000, 10000000);
        $discount    = 0;
        $shippingFee = fake()->randomElement([0, 20000, 30000, 50000]);

        return [
            'user_id'          => User::factory(),
            'voucher_id'       => null,
            'subtotal'         => $subtotal,
            'discount'         => $discount,
            'shipping_fee'     => $shippingFee,
            'total'            => $subtotal - $discount + $shippingFee,
            'status'           => 'pending',
            'payment_method'   => fake()->randomElement(['cod', 'vnpay']),
            'payment_status'   => 'unpaid',
            'shipping_address' => fake()->address(),
            'phone'            => fake()->phoneNumber(),
            'recipient_name'   => fake()->name(),
            'note'             => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'confirmed']);
    }

    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'shipping']);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'         => 'delivered',
            'payment_status' => 'paid',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'cancelled']);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => ['payment_status' => 'paid']);
    }
}
