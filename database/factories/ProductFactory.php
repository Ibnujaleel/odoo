<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 500);

        return [
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(ProductType::cases()),
            'sales_price' => $price,
            'cost_price' => round($price * 0.6, 2),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
