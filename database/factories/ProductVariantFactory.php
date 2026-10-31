<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'attribute' => fake()->randomElement(['brand', 'size', 'edition', 'colour']),
            'value' => fake()->word(),
            'extra_price' => fake()->randomFloat(2, 0, 200),
        ];
    }
}
