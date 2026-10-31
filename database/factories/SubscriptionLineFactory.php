<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SubscriptionLine> */
class SubscriptionLineFactory extends Factory
{
    protected $model = SubscriptionLine::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 10, 300);

        return [
            'subscription_id' => Subscription::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_id' => null,
            'amount' => round($quantity * $unitPrice, 2),
        ];
    }
}
