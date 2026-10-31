<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuotationTemplate;
use App\Models\QuotationTemplateLine;
use App\Models\Tax;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds the catalogue with demo data:
 *  - 5 products (with variants)
 *  - 3 plans (monthly, yearly, daily)
 *  - 2 taxes (GST 18%, fixed service fee)
 *  - 2 discounts (percentage + fixed)
 *  - 2 quotation templates
 */
class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |----------------------------------------------------------------------
        | Products & Variants
        |----------------------------------------------------------------------
        */

        $odooErp = Product::create([
            'name'        => 'Odoo ERP',
            'type'        => 'subscription',
            'sales_price' => 2500,
            'cost_price'  => 1800,
            'notes'       => 'Full-featured ERP platform with modules for accounting, inventory, HR and more.',
            'is_active'   => true,
        ]);

        ProductVariant::create([
            'product_id'  => $odooErp->id,
            'attribute'   => 'brand',
            'value'       => 'Odoo',
            'extra_price' => 560,
        ]);
        ProductVariant::create([
            'product_id'  => $odooErp->id,
            'attribute'   => 'edition',
            'value'       => 'Enterprise',
            'extra_price' => 1200,
        ]);

        $crm = Product::create([
            'name'        => 'CRM Pro',
            'type'        => 'subscription',
            'sales_price' => 800,
            'cost_price'  => 450,
            'notes'       => 'Customer relationship management with pipeline, forecasting and email integration.',
            'is_active'   => true,
        ]);

        ProductVariant::create([
            'product_id'  => $crm->id,
            'attribute'   => 'tier',
            'value'       => 'Premium',
            'extra_price' => 300,
        ]);

        $hosting = Product::create([
            'name'        => 'Cloud Hosting',
            'type'        => 'service',
            'sales_price' => 150,
            'cost_price'  => 60,
            'notes'       => 'Managed cloud hosting with auto-scaling and daily backups.',
            'is_active'   => true,
        ]);

        ProductVariant::create([
            'product_id'  => $hosting->id,
            'attribute'   => 'region',
            'value'       => 'US-East',
            'extra_price' => 0,
        ]);
        ProductVariant::create([
            'product_id'  => $hosting->id,
            'attribute'   => 'region',
            'value'       => 'EU-West',
            'extra_price' => 20,
        ]);

        $support = Product::create([
            'name'        => 'Priority Support',
            'type'        => 'service',
            'sales_price' => 200,
            'cost_price'  => 80,
            'notes'       => '24/7 priority support with 1-hour SLA for critical issues.',
            'is_active'   => true,
        ]);

        $analytics = Product::create([
            'name'        => 'Analytics Dashboard',
            'type'        => 'addon',
            'sales_price' => 350,
            'cost_price'  => 120,
            'notes'       => 'Advanced analytics and reporting dashboard with custom KPIs.',
            'is_active'   => true,
        ]);

        ProductVariant::create([
            'product_id'  => $analytics->id,
            'attribute'   => 'data_retention',
            'value'       => '12 months',
            'extra_price' => 50,
        ]);

        /*
        |----------------------------------------------------------------------
        | Plans
        |----------------------------------------------------------------------
        */

        $monthlyPlan = Plan::create([
            'name'           => 'Standard Monthly',
            'price'          => 999,
            'billing_period' => 'monthly',
            'min_quantity'   => 1,
            'start_date'     => Carbon::today()->toDateString(),
            'end_date'       => Carbon::today()->addYear()->toDateString(),
            'auto_close'     => false,
            'closable'       => true,
            'pausable'       => true,
            'renewable'      => true,
            'status'         => 'active',
        ]);

        $yearlyPlan = Plan::create([
            'name'           => 'Enterprise Annual',
            'price'          => 9990,
            'billing_period' => 'yearly',
            'min_quantity'   => 5,
            'start_date'     => Carbon::today()->toDateString(),
            'end_date'       => Carbon::today()->addYears(3)->toDateString(),
            'auto_close'     => true,
            'closable'       => true,
            'pausable'       => false,
            'renewable'      => true,
            'status'         => 'active',
        ]);

        $trialPlan = Plan::create([
            'name'           => 'Trial Daily',
            'price'          => 0,
            'billing_period' => 'daily',
            'min_quantity'   => 1,
            'start_date'     => Carbon::today()->toDateString(),
            'end_date'       => Carbon::today()->addDays(14)->toDateString(),
            'auto_close'     => true,
            'closable'       => true,
            'pausable'       => false,
            'renewable'      => false,
            'status'         => 'active',
        ]);

        /*
        |----------------------------------------------------------------------
        | Taxes
        |----------------------------------------------------------------------
        */

        $gst = Tax::create([
            'name'      => 'GST 18%',
            'type'      => 'percentage',
            'rate'      => 18,
            'is_active' => true,
        ]);

        $serviceFee = Tax::create([
            'name'      => 'Platform Service Fee',
            'type'      => 'fixed',
            'rate'      => 49.99,
            'is_active' => true,
        ]);

        /*
        |----------------------------------------------------------------------
        | Discounts
        |----------------------------------------------------------------------
        */

        Discount::create([
            'name'            => 'Launch Offer 15%',
            'type'            => 'percentage',
            'value'           => 15,
            'min_purchase'    => 500,
            'min_quantity'    => null,
            'start_date'      => Carbon::today()->toDateString(),
            'end_date'        => Carbon::today()->addMonths(3)->toDateString(),
            'usage_limit'     => 100,
            'times_used'      => 0,
            'applies_to'      => 'product',
            'product_id'      => $odooErp->id,
            'subscription_id' => null,
            'is_active'       => true,
        ]);

        Discount::create([
            'name'            => 'Flat ₹200 Off',
            'type'            => 'fixed',
            'value'           => 200,
            'min_purchase'    => 1000,
            'min_quantity'    => 2,
            'start_date'      => Carbon::today()->toDateString(),
            'end_date'        => Carbon::today()->addMonth()->toDateString(),
            'usage_limit'     => 50,
            'times_used'      => 0,
            'applies_to'      => 'product',
            'product_id'      => $crm->id,
            'subscription_id' => null,
            'is_active'       => true,
        ]);

        /*
        |----------------------------------------------------------------------
        | Quotation Templates
        |----------------------------------------------------------------------
        */

        $starterTemplate = QuotationTemplate::create([
            'name'          => 'Starter Bundle',
            'plan_id'       => $monthlyPlan->id,
            'validity_days' => 30,
        ]);

        QuotationTemplateLine::create([
            'quotation_template_id' => $starterTemplate->id,
            'product_id'            => $odooErp->id,
            'variant_id'            => null,
            'quantity'              => 1,
        ]);
        QuotationTemplateLine::create([
            'quotation_template_id' => $starterTemplate->id,
            'product_id'            => $hosting->id,
            'variant_id'            => null,
            'quantity'              => 1,
        ]);

        $enterpriseTemplate = QuotationTemplate::create([
            'name'          => 'Enterprise Suite',
            'plan_id'       => $yearlyPlan->id,
            'validity_days' => 15,
        ]);

        QuotationTemplateLine::create([
            'quotation_template_id' => $enterpriseTemplate->id,
            'product_id'            => $odooErp->id,
            'variant_id'            => null,
            'quantity'              => 5,
        ]);
        QuotationTemplateLine::create([
            'quotation_template_id' => $enterpriseTemplate->id,
            'product_id'            => $crm->id,
            'variant_id'            => null,
            'quantity'              => 5,
        ]);
        QuotationTemplateLine::create([
            'quotation_template_id' => $enterpriseTemplate->id,
            'product_id'            => $support->id,
            'variant_id'            => null,
            'quantity'              => 1,
        ]);
        QuotationTemplateLine::create([
            'quotation_template_id' => $enterpriseTemplate->id,
            'product_id'            => $analytics->id,
            'variant_id'            => null,
            'quantity'              => 1,
        ]);
    }
}
