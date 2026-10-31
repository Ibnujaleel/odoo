<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@brightloop.test')->first() ?? User::first();
        $customers = Customer::all();
        $subscriptions = Subscription::with('plan')->get();
        $products = Product::all();

        if ($customers->isEmpty() || $subscriptions->isEmpty()) {
            return;
        }

        $cust1 = $customers->first();
        $cust2 = $customers->count() > 1 ? $customers->skip(1)->first() : $cust1;
        $cust3 = $customers->count() > 2 ? $customers->skip(2)->first() : $cust2;
        $cust4 = $customers->count() > 3 ? $customers->skip(3)->first() : $cust1;

        $sub1 = $subscriptions->first();
        $sub2 = $subscriptions->count() > 1 ? $subscriptions->skip(1)->first() : $sub1;
        $sub3 = $subscriptions->count() > 2 ? $subscriptions->skip(2)->first() : $sub2;
        $sub4 = $subscriptions->count() > 3 ? $subscriptions->skip(3)->first() : $sub1;
        $prod = $products->first();

        // 1. Draft Invoice (no payments)
        $invDraft = Invoice::create([
            'number' => 'INV-2026-00101',
            'subscription_id' => $sub1->id,
            'customer_id' => $cust1->id,
            'billing_period_start' => Carbon::today()->startOfMonth(),
            'billing_period_end' => Carbon::today()->endOfMonth(),
            'status' => InvoiceStatus::Draft->value,
            'subtotal' => 500.00,
            'discount_total' => 0.00,
            'tax_total' => 50.00,
            'total' => 550.00,
            'amount_paid' => 0.00,
            'due_date' => Carbon::today()->addDays(15),
            'sent_at' => null,
        ]);
        InvoiceLine::create([
            'invoice_id' => $invDraft->id,
            'product_id' => $prod?->id,
            'description' => ($prod?->name ?? 'Software License').' - Monthly',
            'quantity' => 1,
            'unit_price' => 500.00,
            'discount_amount' => 0.00,
            'tax_amount' => 50.00,
            'amount' => 550.00,
        ]);

        // 2. Confirmed Invoice with Partial Payment (Outstanding, due in future)
        $invPartial = Invoice::create([
            'number' => 'INV-2026-00102',
            'subscription_id' => $sub2->id,
            'customer_id' => $cust2->id,
            'billing_period_start' => Carbon::today()->startOfMonth(),
            'billing_period_end' => Carbon::today()->endOfMonth(),
            'status' => InvoiceStatus::Confirmed->value,
            'subtotal' => 1000.00,
            'discount_total' => 50.00,
            'tax_total' => 95.00,
            'total' => 1045.00,
            'amount_paid' => 500.00,
            'due_date' => Carbon::today()->addDays(7),
            'sent_at' => Carbon::today()->subDays(10),
        ]);
        InvoiceLine::create([
            'invoice_id' => $invPartial->id,
            'product_id' => $prod?->id,
            'description' => 'Enterprise Suite Subscription',
            'quantity' => 1,
            'unit_price' => 1000.00,
            'discount_amount' => 50.00,
            'tax_amount' => 95.00,
            'amount' => 1045.00,
        ]);
        Payment::create([
            'invoice_id' => $invPartial->id,
            'method' => PaymentMethod::Card->value,
            'amount' => 500.00,
            'paid_on' => Carbon::today()->subDays(5),
            'note' => 'Partial upfront card payment',
            'recorded_by' => $admin?->id,
        ]);

        // 3. Overdue Invoice (Confirmed, due_date in past, total > amount_paid)
        $invOverdue = Invoice::create([
            'number' => 'INV-2026-00103',
            'subscription_id' => $sub3->id,
            'customer_id' => $cust3->id,
            'billing_period_start' => Carbon::today()->subMonths(1)->startOfMonth(),
            'billing_period_end' => Carbon::today()->subMonths(1)->endOfMonth(),
            'status' => InvoiceStatus::Confirmed->value,
            'subtotal' => 750.00,
            'discount_total' => 0.00,
            'tax_total' => 75.00,
            'total' => 825.00,
            'amount_paid' => 200.00,
            'due_date' => Carbon::today()->subDays(14),
            'sent_at' => Carbon::today()->subDays(25),
        ]);
        InvoiceLine::create([
            'invoice_id' => $invOverdue->id,
            'product_id' => $prod?->id,
            'description' => 'Dedicated Cloud Hosting & Support',
            'quantity' => 1,
            'unit_price' => 750.00,
            'discount_amount' => 0.00,
            'tax_amount' => 75.00,
            'amount' => 825.00,
        ]);
        Payment::create([
            'invoice_id' => $invOverdue->id,
            'method' => PaymentMethod::BankTransfer->value,
            'amount' => 200.00,
            'paid_on' => Carbon::today()->subDays(20),
            'note' => 'Initial wire deposit',
            'recorded_by' => $admin?->id,
        ]);

        // 4. Fully Paid Invoice
        $invPaid = Invoice::create([
            'number' => 'INV-2026-00104',
            'subscription_id' => $sub4->id,
            'customer_id' => $cust4->id,
            'billing_period_start' => Carbon::today()->subMonths(2)->startOfMonth(),
            'billing_period_end' => Carbon::today()->subMonths(2)->endOfMonth(),
            'status' => InvoiceStatus::Paid->value,
            'subtotal' => 1200.00,
            'discount_total' => 100.00,
            'tax_total' => 110.00,
            'total' => 1210.00,
            'amount_paid' => 1210.00,
            'due_date' => Carbon::today()->subMonths(1),
            'sent_at' => Carbon::today()->subMonths(2),
        ]);
        InvoiceLine::create([
            'invoice_id' => $invPaid->id,
            'product_id' => $prod?->id,
            'description' => 'Platform Service Fee & Consultation',
            'quantity' => 1,
            'unit_price' => 1200.00,
            'discount_amount' => 100.00,
            'tax_amount' => 110.00,
            'amount' => 1210.00,
        ]);
        Payment::create([
            'invoice_id' => $invPaid->id,
            'method' => PaymentMethod::Upi->value,
            'amount' => 1210.00,
            'paid_on' => Carbon::today()->subMonths(1)->subDays(5),
            'note' => 'Full payment settled via UPI',
            'recorded_by' => $admin?->id,
        ]);

        // 5. Cancelled Invoice
        $invCancelled = Invoice::create([
            'number' => 'INV-2026-00105',
            'subscription_id' => $sub1->id,
            'customer_id' => $cust1->id,
            'billing_period_start' => Carbon::today()->subMonths(3)->startOfMonth(),
            'billing_period_end' => Carbon::today()->subMonths(3)->endOfMonth(),
            'status' => InvoiceStatus::Cancelled->value,
            'subtotal' => 300.00,
            'discount_total' => 0.00,
            'tax_total' => 30.00,
            'total' => 330.00,
            'amount_paid' => 0.00,
            'due_date' => Carbon::today()->subMonths(2),
            'sent_at' => null,
        ]);
        InvoiceLine::create([
            'invoice_id' => $invCancelled->id,
            'product_id' => $prod?->id,
            'description' => 'Cancelled Pilot Trial',
            'quantity' => 1,
            'unit_price' => 300.00,
            'discount_amount' => 0.00,
            'tax_amount' => 30.00,
            'amount' => 330.00,
        ]);
    }
}
