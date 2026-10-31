<?php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DiscountUsage> */
class DiscountUsageFactory extends Factory
{
    protected $model = DiscountUsage::class;

    public function definition(): array
    {
        return [
            'discount_id' => Discount::factory(),
            'invoice_id' => Invoice::factory(),
        ];
    }
}
