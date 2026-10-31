<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'amount' => fake()->randomFloat(2, 50, 2000),
            'paid_on' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'note' => fake()->optional()->sentence(),
            'recorded_by' => null,
        ];
    }
}
