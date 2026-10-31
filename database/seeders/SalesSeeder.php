<?php

namespace Database\Seeders;

use App\Enums\BillingPeriod;
use App\Enums\PlanStatus;
use App\Enums\ProductType;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subscription;
use App\Models\SubscriptionLine;
use App\Models\User;
use Illuminate\Database\Seeder;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        $staff = User::where('email', 'staff@brightloop.test')->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'staff'))->first()
            ?? User::first();

        $customerUser = User::where('email', 'customer@brightloop.test')->first();

        // 1. Primary demo customer linked to customer@brightloop.test
        $primaryCustomer = Customer::firstOrCreate(
            ['email' => 'customer@brightloop.test'],
            [
                'user_id' => $customerUser?->id,
                'name' => 'Brightloop Customer',
                'company' => 'Brightloop Demo Co.',
                'phone' => '+1 555 0100',
                'address' => '1 Loop Street, Suite 100, San Francisco CA',
                'assigned_to' => $staff?->id,
            ],
        );

        // 2-5. Four additional customer accounts
        $customersData = [
            [
                'name' => 'Sarah Connor',
                'company' => 'Cyberdyne Systems',
                'email' => 's.connor@cyberdyne.test',
                'phone' => '+1 555 0191',
                'address' => '2140 Tech Ridge Blvd, Austin TX',
                'assigned_to' => $staff?->id,
            ],
            [
                'name' => 'Miles Dyson',
                'company' => 'Skynet Innovations',
                'email' => 'm.dyson@skynet.test',
                'phone' => '+1 555 0192',
                'address' => '400 Microchip Way, Mountain View CA',
                'assigned_to' => $staff?->id,
            ],
            [
                'name' => 'Arthur Dent',
                'company' => 'Hitchhiker Media',
                'email' => 'arthur@hitchhiker.test',
                'phone' => '+44 20 7946 0919',
                'address' => '42 Cottington Crescent, London UK',
                'assigned_to' => $staff?->id,
            ],
            [
                'name' => 'Trillian McMillan',
                'company' => 'Galaxy Subscriptions Ltd',
                'email' => 'trillian@galaxy.test',
                'phone' => '+1 555 0142',
                'address' => '88 Celestial Highway, New York NY',
                'assigned_to' => $staff?->id,
            ],
        ];

        $extraCustomers = [];
        foreach ($customersData as $data) {
            $extraCustomers[] = Customer::firstOrCreate(['email' => $data['email']], $data);
        }

        // Ensure at least one plan exists
        $plan = Plan::firstOrCreate(
            ['name' => 'Professional Monthly'],
            [
                'price' => 299.00,
                'billing_period' => BillingPeriod::Monthly,
                'min_quantity' => 1,
                'status' => PlanStatus::Active,
                'auto_close' => false,
                'closable' => true,
                'pausable' => true,
                'renewable' => true,
            ],
        );

        // Ensure products exist
        $product = Product::firstOrCreate(
            ['name' => 'Cloud Subscription Seat'],
            [
                'type' => ProductType::Service,
                'sales_price' => 50.00,
                'cost_price' => 15.00,
                'is_active' => true,
            ],
        );

        $variant = ProductVariant::firstOrCreate(
            [
                'product_id' => $product->id,
                'attribute' => 'tier',
                'value' => 'Enterprise SLA',
            ],
            [
                'extra_price' => 25.00,
            ],
        );

        // 4 Subscriptions in different statuses
        $subscriptions = [
            [
                'number' => 'SUB-2026-00001',
                'customer_id' => $primaryCustomer->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Quotation,
                'quotation_valid_until' => today()->addDays(14),
                'payment_terms' => 'net_30',
                'created_by' => $staff?->id,
                'lines' => [
                    ['qty' => 5, 'unit' => 75.00, 'amount' => 375.00],
                ],
            ],
            [
                'number' => 'SUB-2026-00002',
                'customer_id' => $primaryCustomer->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
                'start_date' => today()->subMonth(),
                'next_billing_date' => today()->addDays(10),
                'payment_terms' => 'net_30',
                'created_by' => $staff?->id,
                'lines' => [
                    ['qty' => 10, 'unit' => 50.00, 'amount' => 500.00],
                ],
            ],
            [
                'number' => 'SUB-2026-00003',
                'customer_id' => $extraCustomers[0]->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Draft,
                'payment_terms' => 'net_15',
                'created_by' => $staff?->id,
                'lines' => [
                    ['qty' => 2, 'unit' => 75.00, 'amount' => 150.00],
                ],
            ],
            [
                'number' => 'SUB-2026-00004',
                'customer_id' => $extraCustomers[1]->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Confirmed,
                'start_date' => today(),
                'payment_terms' => 'net_30',
                'created_by' => $staff?->id,
                'lines' => [
                    ['qty' => 3, 'unit' => 50.00, 'amount' => 150.00],
                ],
            ],
        ];

        foreach ($subscriptions as $subData) {
            $lines = $subData['lines'];
            unset($subData['lines']);

            $sub = Subscription::firstOrCreate(
                ['number' => $subData['number']],
                $subData,
            );

            if ($sub->wasRecentlyCreated || $sub->lines()->count() === 0) {
                foreach ($lines as $line) {
                    SubscriptionLine::create([
                        'subscription_id' => $sub->id,
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'quantity' => $line['qty'],
                        'unit_price' => $line['unit'],
                        'amount' => $line['amount'],
                    ]);
                }
            }
        }
    }
}
