<?php

namespace Database\Factories;

use App\Enums\TaxType;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tax> */
class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' Tax',
            'type' => TaxType::Percentage,
            'rate' => fake()->randomElement([0, 5, 12, 18]),
            'is_active' => true,
        ];
    }
}
