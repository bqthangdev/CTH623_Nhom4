<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voucher>
 */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code'       => strtoupper(Str::random(8)),
            'type'       => 'percent',
            'value'      => fake()->randomFloat(2, 5, 30),
            'min_order'  => fake()->randomElement([0, 200000, 500000]),
            'max_uses'   => fake()->randomElement([null, 50, 100, 200]),
            'used_count' => 0,
            'is_active'  => true,
            'expires_at' => fake()->dateTimeBetween('+1 month', '+1 year'),
        ];
    }

    public function percent(): static
    {
        return $this->state(fn (array $attributes) => [
            'type'  => 'percent',
            'value' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type'  => 'fixed',
            'value' => fake()->randomElement([20000, 50000, 100000, 200000]),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => null,
        ]);
    }
}
