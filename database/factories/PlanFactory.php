<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Enums\PlanStatus;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' Plan',
            'price' => fake()->randomFloat(2, 20, 500),
            'billing_period' => fake()->randomElement(BillingPeriod::cases()),
            'min_quantity' => fake()->numberBetween(1, 3),
            'start_date' => null,
            'end_date' => null,
            'auto_close' => false,
            'closable' => true,
            'pausable' => fake()->boolean(),
            'renewable' => true,
            'status' => PlanStatus::Active,
        ];
    }
}
