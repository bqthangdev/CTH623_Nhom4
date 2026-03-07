<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name'        => ucwords($name),
            'slug'        => Str::slug($name) . '-' . fake()->randomNumber(5),
            'description' => fake()->paragraphs(2, true),
            'price'       => fake()->randomFloat(2, 50000, 5000000),
            'sale_price'  => null,
            'stock'       => fake()->numberBetween(0, 200),
            'status'      => true,
            'is_featured' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'status'      => true,
        ]);
    }

    public function inStock(int $stock = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $stock,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }

    public function withSalePrice(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'] ?? fake()->randomFloat(2, 100000, 5000000);
            return [
                'sale_price' => round($price * fake()->randomFloat(2, 0.5, 0.9), 2),
            ];
        });
    }
}
