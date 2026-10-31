<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'number' => 'SUB-'.fake()->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'plan_id' => Plan::factory(),
            'quotation_template_id' => null,
            'status' => SubscriptionStatus::Draft,
            'start_date' => null,
            'expiry_date' => null,
            'payment_terms' => 'net_30',
            'next_billing_date' => null,
            'quotation_valid_until' => null,
            'created_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Active,
            'start_date' => today()->subMonth(),
            'next_billing_date' => today(),
        ]);
    }
}
