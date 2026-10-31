<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Quotation {{ $subscription->number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 20mm 15mm 20mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .brand {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
        }
        .brand-sub {
            font-size: 11px;
            color: #64748b;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h1 {
            font-size: 20px;
            margin: 0 0 4px 0;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-meta {
            font-size: 11px;
            color: #475569;
        }
        .details-table {
            width: 100%;
            margin-bottom: 25px;
        }
        .details-table td {
            vertical-align: top;
            width: 50%;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 6px;
        }
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 12px;
        }
        .info-card p {
            margin: 2px 0;
        }
        .info-card .name {
            font-weight: bold;
            color: #0f172a;
            font-size: 13px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        .items-table th.right, .items-table td.right {
            text-align: right;
        }
        .items-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .items-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .item-name {
            font-weight: bold;
            color: #0f172a;
        }
        .item-variant {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .totals-table {
            width: 45%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .totals-table td {
            padding: 5px 8px;
        }
        .totals-table .label {
            color: #64748b;
            text-align: right;
        }
        .totals-table .amount {
            text-align: right;
            font-weight: 500;
        }
        .totals-table .grand-total td {
            border-top: 2px solid #4f46e5;
            padding-top: 8px;
            font-size: 14px;
            font-weight: bold;
            color: #4f46e5;
        }
        .terms-block {
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
            font-size: 10px;
            color: #64748b;
        }
        .terms-block h4 {
            margin: 0 0 4px 0;
            font-size: 11px;
            color: #334155;
            text-transform: uppercase;
        }
        .status-pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #e0e7ff;
            color: #4338ca;
        }
    </style>
</head>
<body>
    <div class="header">
        <table cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <div class="brand">Brightloop</div>
                    <div class="brand-sub">Subscription & Billing Management System</div>
                </td>
                <td class="doc-title">
                    <h1>Quotation</h1>
                    <div class="doc-meta">
                        <strong>{{ $subscription->number }}</strong><br>
                        Date: {{ optional($subscription->created_at)->format('d M Y') ?? now()->format('d M Y') }}<br>
                        Valid Until: {{ optional($subscription->quotation_valid_until)->format('d M Y') ?? 'N/A' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="details-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-right: 15px;">
                <div class="section-title">Quotation For</div>
                <div class="info-card">
                    <p class="name">{{ $subscription->customer->name ?? 'Valued Customer' }}</p>
                    @if(!empty($subscription->customer->company))
                        <p>{{ $subscription->customer->company }}</p>
                    @endif
                    @if(!empty($subscription->customer->email))
                        <p>{{ $subscription->customer->email }}</p>
                    @endif
                    @if(!empty($subscription->customer->phone))
                        <p>{{ $subscription->customer->phone }}</p>
                    @endif
                    @if(!empty($subscription->customer->address))
                        <p>{{ $subscription->customer->address }}</p>
                    @endif
                </div>
            </td>
            <td style="padding-left: 15px;">
                <div class="section-title">Subscription Plan Details</div>
                <div class="info-card">
                    <p><strong>Plan:</strong> {{ $subscription->plan->name ?? 'Standard Plan' }}</p>
                    <p><strong>Billing Period:</strong> {{ ucfirst($subscription->plan->billing_period?->value ?? $subscription->plan->billing_period ?? 'Monthly') }}</p>
                    <p><strong>Payment Terms:</strong> {{ strtoupper(str_replace('_', ' ', $subscription->payment_terms ?? 'Net 30')) }}</p>
                    <p><strong>Status:</strong> <span class="status-pill">{{ ucfirst($subscription->status?->value ?? (string) $subscription->status) }}</span></p>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 45%;">Item / Description</th>
                <th class="right" style="width: 15%;">Qty</th>
                <th class="right" style="width: 20%;">Unit Price</th>
                <th class="right" style="width: 20%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
                $calcLines = [];
                $subtotal = 0.0;
                $taxTotal = 0.0;
                $calculator = app(\App\Services\QuotationCalculator::class);
                try {
                    $calcResult = $calculator->calculate($subscription->lines->all());
                    $subtotal = $calcResult['subtotal'];
                    $taxTotal = $calcResult['tax'];
                    $grandTotal = $calcResult['grand_total'];
                } catch (\Throwable $e) {
                    $subtotal = (float) $subscription->lines->sum('amount');
                    $taxTotal = 0.0;
                    $grandTotal = $subtotal;
                }
            @endphp

            @forelse($subscription->lines as $line)
                <tr>
                    <td>
                        <div class="item-name">{{ $line->product->name ?? 'Product Item' }}</div>
                        @if($line->variant)
                            <div class="item-variant">{{ $line->variant->displayName() }} (+${{ number_format($line->variant->extra_price, 2) }})</div>
                        @endif
                    </td>
                    <td class="right">{{ $line->quantity }}</td>
                    <td class="right">${{ number_format($line->unit_price, 2) }}</td>
                    <td class="right">${{ number_format($line->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">
                        No line items configured for this quotation.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Subtotal:</td>
            <td class="amount">${{ number_format($subtotal, 2) }}</td>
        </tr>
        @if($taxTotal > 0)
            <tr>
                <td class="label">Estimated Tax:</td>
                <td class="amount">${{ number_format($taxTotal, 2) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td class="label">Total:</td>
            <td class="amount">${{ number_format($grandTotal ?? $subtotal, 2) }}</td>
        </tr>
    </table>

    <div class="terms-block">
        <h4>Terms & Conditions</h4>
        <p>1. This quotation is valid until the validity date specified above.</p>
        <p>2. Acceptance of this quotation constitutes agreement to Brightloop standard subscription terms.</p>
        <p>3. Invoices will be generated according to the plan billing cycle upon subscription activation.</p>
    </div>
</body>
</html>

