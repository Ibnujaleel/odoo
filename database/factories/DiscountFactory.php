<?php

namespace Database\Factories;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Discount> */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' Discount',
            'type' => DiscountType::Percentage,
            'value' => fake()->randomElement([5, 10, 15]),
            'min_purchase' => 0,
            'min_quantity' => 0,
            'start_date' => null,
            'end_date' => null,
            'usage_limit' => null,
            'times_used' => 0,
            'applies_to' => DiscountAppliesTo::Subscription,
            'product_id' => null,
            'subscription_id' => null,
            'is_active' => true,
        ];
    }
}
