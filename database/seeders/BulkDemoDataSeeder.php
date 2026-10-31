<?php

namespace Database\Seeders;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionStatus;
use App\Enums\TaxType;
use App\Models\Discount;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BulkDemoDataSeeder extends Seeder
{
    private const CUSTOMERS = 3000;

    private const INVOICES = 2500;

    private const PAYMENTS = 1800;

    public function run(): void
    {
        $products = $this->catalogue();
        $customerIds = $this->customers();
        $subscriptionRows = $this->subscriptions($customerIds);
        [$subscriptions, $plans] = $subscriptionRows;
        $this->subscriptionLines($subscriptions, $products);
        $invoiceIds = $this->invoices($subscriptions, $plans);
        $this->invoiceLines($invoiceIds, $products);
        $this->payments($invoiceIds);
    }

    /** @return array<int, int> */
    private function catalogue(): array
    {
        if (Product::query()->doesntExist()) {
            $rows = [
                ['name' => 'Odoo ERP Licence', 'type' => 'service', 'sales_price' => 1200, 'cost_price' => 720],
                ['name' => 'Brightloop CRM', 'type' => 'service', 'sales_price' => 850, 'cost_price' => 510],
                ['name' => 'Managed Support', 'type' => 'service', 'sales_price' => 350, 'cost_price' => 180],
                ['name' => 'Onboarding Workshop', 'type' => 'product', 'sales_price' => 2400, 'cost_price' => 1300],
                ['name' => 'Priority Add-on', 'type' => 'addon', 'sales_price' => 150, 'cost_price' => 40],
            ];

            foreach ($rows as $row) {
                Product::create($row + [
                    'notes' => null,
                    'is_active' => true,
                ]);
            }

            ProductVariant::create([
                'product_id' => Product::query()->value('id'),
                'attribute' => 'brand',
                'value' => 'Odoo',
                'extra_price' => 560,
            ]);
            ProductVariant::create([
                'product_id' => Product::query()->value('id'),
                'attribute' => 'edition',
                'value' => 'Enterprise',
                'extra_price' => 900,
            ]);
        }

        if (Plan::query()->doesntExist()) {
            Plan::create([
                'name' => 'Starter Monthly',
                'price' => 299,
                'billing_period' => 'monthly',
                'min_quantity' => 1,
                'start_date' => today()->subYear(),
                'end_date' => null,
                'auto_close' => false,
                'closable' => true,
                'pausable' => true,
                'renewable' => true,
                'status' => 'active',
            ]);
            Plan::create([
                'name' => 'Growth Yearly',
                'price' => 2990,
                'billing_period' => 'yearly',
                'min_quantity' => 5,
                'start_date' => today()->subYear(),
                'end_date' => null,
                'auto_close' => true,
                'closable' => true,
                'pausable' => false,
                'renewable' => true,
                'status' => 'active',
            ]);
            Plan::create([
                'name' => 'Enterprise Quarterly',
                'price' => 4500,
                'billing_period' => 'weekly',
                'min_quantity' => 10,
                'start_date' => today()->subYear(),
                'end_date' => null,
                'auto_close' => false,
                'closable' => true,
                'pausable' => true,
                'renewable' => true,
                'status' => 'active',
            ]);
        }

        if (Tax::query()->doesntExist()) {
            Tax::create(['name' => 'GST 18%', 'type' => TaxType::Percentage, 'rate' => 18, 'is_active' => true]);
            Tax::create(['name' => 'Service Fee', 'type' => TaxType::Fixed, 'rate' => 25, 'is_active' => true]);
        }

        if (Discount::query()->doesntExist()) {
            Discount::create([
                'name' => 'Launch Offer',
                'type' => DiscountType::Percentage,
                'value' => 10,
                'min_purchase' => 0,
                'min_quantity' => 0,
                'start_date' => today()->subMonths(3),
                'end_date' => today()->addMonths(3),
                'usage_limit' => 500,
                'times_used' => 0,
                'applies_to' => DiscountAppliesTo::Subscription,
                'product_id' => null,
                'subscription_id' => null,
                'is_active' => true,
            ]);
        }

        return Product::query()->pluck('id')->all();
    }

    /** @return array<int, int> */
    private function customers(): array
    {
        if (DB::table('customers')->where('email', 'like', 'demo-customer-%')->exists()) {
            return DB::table('customers')->where('email', 'like', 'demo-customer-%')->pluck('id')->all();
        }

        $now = now();
        $chunk = [];

        for ($i = 1; $i <= self::CUSTOMERS; $i++) {
            $chunk[] = [
                'user_id' => null,
                'name' => 'Demo Customer '.$i,
                'company' => 'Demo Company '.$i,
                'email' => 'demo-customer-'.$i.'@brightloop.test',
                'phone' => '+1 555 '.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'address' => $i.' Loop Avenue',
                'assigned_to' => null,
                'invite_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($chunk) === 500) {
                DB::table('customers')->insert($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('customers')->insert($chunk);
        }

        return DB::table('customers')->where('email', 'like', 'demo-customer-%')->pluck('id')->all();
    }

    /**
     * @param  array<int, int>  $customerIds
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function subscriptions(array $customerIds): array
    {
        $plans = Plan::query()->get(['id', 'price', 'billing_period'])->toArray();
        $now = now();
        $chunk = [];
        $rows = [];

        foreach ($customerIds as $index => $customerId) {
            $plan = $plans[$index % count($plans)];
            $status = match ($index % 10) {
                0, 1 => SubscriptionStatus::Draft,
                2 => SubscriptionStatus::Quotation,
                3 => SubscriptionStatus::Confirmed,
                4 => SubscriptionStatus::Closed,
                default => SubscriptionStatus::Active,
            };

            $row = [
                'number' => 'SUB-DEMO-'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                'customer_id' => $customerId,
                'plan_id' => $plan['id'],
                'quotation_template_id' => null,
                'status' => $status->value,
                'start_date' => $status === SubscriptionStatus::Active ? today()->subMonths(2)->toDateString() : null,
                'expiry_date' => $status === SubscriptionStatus::Closed ? today()->subWeek()->toDateString() : null,
                'payment_terms' => 'net_30',
                'next_billing_date' => $status === SubscriptionStatus::Active ? today()->toDateString() : null,
                'quotation_valid_until' => null,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $chunk[] = $row;
            $rows[] = $row;

            if (count($chunk) === 500) {
                DB::table('subscriptions')->insert($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('subscriptions')->insert($chunk);
        }

        $subscriptions = DB::table('subscriptions')
            ->where('number', 'like', 'SUB-DEMO-%')
            ->orderBy('id')
            ->get(['id', 'customer_id', 'plan_id'])
            ->map(fn ($row) => (array) $row)
            ->all();

        return [$subscriptions, $plans];
    }

    /**
     * @param  array<int, array<string, mixed>>  $subscriptions
     * @param  array<int, int>  $products
     */
    private function subscriptionLines(array $subscriptions, array $products): void
    {
        if (DB::table('subscription_lines')->exists()) {
            return;
        }

        $now = now();
        $chunk = [];

        foreach ($subscriptions as $index => $subscription) {
            $lineCount = ($index % 3) + 1;

            for ($line = 0; $line < $lineCount; $line++) {
                $productId = $products[($index + $line) % count($products)];
                $quantity = (($index + $line) % 4) + 1;
                $unitPrice = 100 + (($index * 7 + $line * 13) % 900);
                $chunk[] = [
                    'subscription_id' => $subscription['id'],
                    'product_id' => $productId,
                    'variant_id' => null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_id' => null,
                    'amount' => round($quantity * $unitPrice, 2),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($chunk) >= 500) {
                DB::table('subscription_lines')->insert($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('subscription_lines')->insert($chunk);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $subscriptions
     * @param  array<int, array<string, mixed>>  $plans
     * @return array<int, array<string, mixed>>
     */
    private function invoices(array $subscriptions, array $plans): array
    {
        $now = now();
        $limit = min(self::INVOICES, count($subscriptions));
        $chunk = [];
        $rows = [];

        for ($i = 0; $i < $limit; $i++) {
            $subscription = $subscriptions[$i];
            $plan = $plans[$i % count($plans)];
            $subtotal = (float) $plan['price'];
            $tax = round($subtotal * 0.18, 2);
            $total = $subtotal + $tax;
            $status = match ($i % 10) {
                0, 1, 2, 3, 4, 5 => InvoiceStatus::Paid,
                6 => InvoiceStatus::Draft,
                7 => InvoiceStatus::Cancelled,
                default => InvoiceStatus::Confirmed,
            };
            $periodStart = today()->subMonths(($i % 6) + 1);
            $periodEnd = $periodStart->copy()->addMonthNoOverflow();
            $dueDate = $periodEnd->copy()->addDays(30);

            $row = [
                'number' => 'INV-DEMO-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'subscription_id' => $subscription['id'],
                'customer_id' => $subscription['customer_id'],
                'billing_period_start' => $periodStart->toDateString(),
                'billing_period_end' => $periodEnd->toDateString(),
                'status' => $status->value,
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'tax_total' => $tax,
                'total' => $total,
                'amount_paid' => $status === InvoiceStatus::Paid ? $total : 0,
                'due_date' => $dueDate->toDateString(),
                'sent_at' => $status === InvoiceStatus::Draft ? null : $periodStart,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $chunk[] = $row;
            $rows[] = $row;

            if (count($chunk) === 500) {
                DB::table('invoices')->insert($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('invoices')->insert($chunk);
        }

        return DB::table('invoices')
            ->where('number', 'like', 'INV-DEMO-%')
            ->orderBy('id')
            ->get(['id', 'subscription_id', 'customer_id', 'total', 'status'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $invoices
     * @param  array<int, int>  $products
     */
    private function invoiceLines(array $invoices, array $products): void
    {
        if (DB::table('invoice_lines')->exists()) {
            return;
        }

        $now = now();
        $chunk = [];

        foreach ($invoices as $index => $invoice) {
            $productId = $products[$index % count($products)];
            $quantity = ($index % 4) + 1;
            $unitPrice = round((float) $invoice['total'] / $quantity, 2);

            $chunk[] = [
                'invoice_id' => $invoice['id'],
                'product_id' => $productId,
                'description' => 'Subscription billing '.$invoice['subscription_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => 0,
                'tax_amount' => round($unitPrice * $quantity * 0.18, 2),
                'amount' => round($quantity * $unitPrice, 2),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($chunk) === 500) {
                DB::table('invoice_lines')->insert($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('invoice_lines')->insert($chunk);
        }
    }

    /** @param array<int, array<string, mixed>> $invoices */
    private function payments(array $invoices): void
    {
        if (DB::table('payments')->exists()) {
            return;
        }

        $adminId = User::query()->where('email', 'admin@brightloop.test')->value('id');
        $methods = array_map(fn (PaymentMethod $method) => $method->value, PaymentMethod::cases());
        $now = now();
        $chunk = [];
        $created = 0;

        foreach ($invoices as $index => $invoice) {
            $isPaid = $invoice['status'] === InvoiceStatus::Paid->value;
            $isPartial = $invoice['status'] === InvoiceStatus::Confirmed->value;

            if (! $isPaid && ! $isPartial) {
                continue;
            }

            if ($created >= self::PAYMENTS) {
                break;
            }

            $amount = $isPaid
                ? (float) $invoice['total']
                : round((float) $invoice['total'] * 0.3, 2);

            if ($isPartial) {
                DB::table('invoices')->where('id', $invoice['id'])->update(['amount_paid' => $amount]);
            }

            $chunk[] = [
                'invoice_id' => $invoice['id'],
                'method' => $methods[$index % count($methods)],
                'amount' => $amount,
                'paid_on' => today()->subDays($index % 90)->toDateString(),
                'note' => 'Demo payment '.Str::upper(Str::random(6)),
                'recorded_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $created++;

            if (count($chunk) === 500) {
                DB::table('payments')->insert($chunk);
                $chunk = [];
            }

        }

        if ($chunk !== []) {
            DB::table('payments')->insert($chunk);
        }
    }
}
