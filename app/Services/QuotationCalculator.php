<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tax;
use InvalidArgumentException;

class QuotationCalculator
{
    public function __construct(
        private readonly TaxCalculator $taxCalculator = new TaxCalculator,
        private readonly DiscountEngine $discountEngine = new DiscountEngine,
    ) {}

    /**
     * Calculate line items and totals for a quotation.
     *
     * @param  array<int, mixed>  $lines
     * @param  Tax|float|null  $tax
     * @return array{
     *     lines: array<int, array{
     *         product_id: int|null,
     *         variant_id: int|null,
     *         quantity: int,
     *         unit_price: float,
     *         amount: float,
     *         tax: float,
     *         tax_id: int|null
     *     }>,
     *     subtotal: float,
     *     tax: float,
     *     tax_total: float,
     *     discount: float,
     *     discount_total: float,
     *     grand_total: float,
     *     total: float
     * }
     */
    public function calculate(array $lines, $tax = null, ?Discount $discount = null): array
    {
        $calculatedLines = [];
        $subtotal = 0.0;
        $totalTax = 0.0;
        $totalQuantity = 0;

        foreach ($lines as $line) {
            $rawQty = is_array($line) ? ($line['quantity'] ?? 0) : ($line->quantity ?? 0);
            $qty = (int) $rawQty;

            if ($qty <= 0) {
                throw new InvalidArgumentException('Line quantity must be greater than zero.');
            }

            // Determine product & variant
            $product = null;
            $productId = null;
            $variant = null;
            $variantId = null;

            if (is_array($line)) {
                $productId = $line['product_id'] ?? null;
                $variantId = $line['variant_id'] ?? null;
                $product = $line['product'] ?? null;
                $variant = $line['variant'] ?? null;
            } elseif (is_object($line)) {
                $productId = $line->product_id ?? null;
                $variantId = $line->variant_id ?? null;
                $product = $line->product ?? null;
                $variant = $line->variant ?? null;
            }

            if (! $product && $productId) {
                $product = Product::find($productId);
            }
            if (! $variant && $variantId) {
                $variant = ProductVariant::find($variantId);
            }

            // Unit price calculation: product sales_price + variant extra_price
            $basePrice = 0.0;
            if ($product) {
                $basePrice = (float) $product->sales_price;
            } elseif (is_array($line) && isset($line['sales_price'])) {
                $basePrice = (float) $line['sales_price'];
            } elseif (is_array($line) && isset($line['unit_price'])) {
                $basePrice = (float) $line['unit_price'];
            } elseif (is_object($line) && isset($line->unit_price)) {
                $basePrice = (float) $line->unit_price;
            }

            $extraPrice = 0.0;
            if ($variant) {
                $extraPrice = (float) $variant->extra_price;
            } elseif (is_array($line) && isset($line['extra_price'])) {
                $extraPrice = (float) $line['extra_price'];
            }

            $unitPrice = round($basePrice + $extraPrice, 2);
            $lineAmount = round($qty * $unitPrice, 2);

            // Determine line tax
            $lineTax = null;
            if (is_array($line) && ! empty($line['tax_id'])) {
                $lineTax = Tax::find($line['tax_id']);
            } elseif (is_array($line) && isset($line['tax']) && $line['tax'] instanceof Tax) {
                $lineTax = $line['tax'];
            } elseif (is_object($line) && ! empty($line->tax_id)) {
                $lineTax = $line->tax ?? Tax::find($line->tax_id);
            } elseif ($tax instanceof Tax) {
                $lineTax = $tax;
            }

            $lineTaxAmount = 0.0;
            if ($lineTax) {
                $lineTaxAmount = $this->taxCalculator->calculate($lineAmount, $lineTax);
            } elseif (is_numeric($tax)) {
                $lineTaxAmount = round($lineAmount * ((float) $tax / 100), 2);
            }

            $subtotal += $lineAmount;
            $totalTax += $lineTaxAmount;
            $totalQuantity += $qty;

            $calculatedLines[] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'amount' => $lineAmount,
                'tax' => $lineTaxAmount,
                'tax_id' => $lineTax?->id,
            ];
        }

        $discountAmount = 0.0;
        if ($discount) {
            $discountAmount = $this->discountEngine->amountFor($discount, $subtotal, $totalQuantity);
        }

        $subtotal = round($subtotal, 2);
        $totalTax = round($totalTax, 2);
        $discountAmount = round($discountAmount, 2);
        $grandTotal = round(max(0.0, $subtotal - $discountAmount + $totalTax), 2);

        return [
            'lines' => $calculatedLines,
            'subtotal' => $subtotal,
            'tax' => $totalTax,
            'tax_total' => $totalTax,
            'discount' => $discountAmount,
            'discount_total' => $discountAmount,
            'grand_total' => $grandTotal,
            'total' => $grandTotal,
        ];
    }
}
