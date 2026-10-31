<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', 'now');
        $end = (clone $start)->modify('+1 month');

        return [
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'subscription_id' => Subscription::factory(),
            'customer_id' => Customer::factory(),
            'billing_period_start' => $start->format('Y-m-d'),
            'billing_period_end' => $end->format('Y-m-d'),
            'status' => InvoiceStatus::Draft,
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'due_date' => (clone $end)->modify('+30 days')->format('Y-m-d'),
            'sent_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'amount_paid' => $attributes['total'] ?? 0,
        ]);
    }
}
