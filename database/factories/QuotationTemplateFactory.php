<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\QuotationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuotationTemplate> */
class QuotationTemplateFactory extends Factory
{
    protected $model = QuotationTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'plan_id' => Plan::factory(),
            'validity_days' => fake()->numberBetween(7, 30),
        ];
    }
}
