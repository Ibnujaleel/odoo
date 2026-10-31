<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->number }}</title>
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
        .party-name {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 3px;
        }
        .party-meta {
            color: #334155;
            line-height: 1.5;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
        }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-confirmed { background: #e0e7ff; color: #4338ca; }
        .badge-draft { background: #f1f5f9; color: #475569; }
        .badge-cancelled { background: #fee2e2; color: #b91c1c; }
        .badge-overdue { background: #fef2f2; color: #dc2626; }
        .lines-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .lines-table th {
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            padding: 8px 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            text-align: left;
        }
        .lines-table th.text-right {
            text-align: right;
        }
        .lines-table th.text-center {
            text-align: center;
        }
        .lines-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .lines-table td.text-right {
            text-align: right;
        }
        .lines-table td.text-center {
            text-align: center;
        }
        .item-name {
            font-weight: 600;
            color: #0f172a;
        }
        .item-desc {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .totals-table-container {
            width: 100%;
            margin-bottom: 25px;
        }
        .totals-table {
            width: 45%;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px 8px;
            font-size: 11px;
        }
        .totals-table td.label {
            color: #64748b;
            text-align: right;
        }
        .totals-table td.val {
            text-align: right;
            font-weight: 600;
            color: #1e293b;
        }
        .totals-table tr.total-row td {
            border-top: 1px solid #cbd5e1;
            border-bottom: 2px solid #4f46e5;
            font-size: 13px;
            font-weight: bold;
            padding-top: 8px;
            padding-bottom: 8px;
            color: #0f172a;
        }
        .totals-table tr.balance-row td {
            font-size: 12px;
            font-weight: bold;
            color: #e11d48;
            padding-top: 6px;
        }
        .footer {
            margin-top: 35px;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="brand">Brightloop</div>
                    <div class="brand-sub">Subscription Billing &amp; Invoicing Platform</div>
                </td>
                <td class="doc-title">
                    <h1>Tax Invoice</h1>
                    <div class="doc-meta">
                        <strong>Invoice #:</strong> {{ $invoice->number }}<br>
                        <strong>Date:</strong> {{ $invoice->created_at?->format('M j, Y') ?? now()->format('M j, Y') }}<br>
                        <strong>Due Date:</strong> {{ $invoice->due_date?->format('M j, Y') ?? 'Due upon receipt' }}<br>
                        <strong>Status:</strong>
                        @php
                            $statusVal = $invoice->displayStatus();
                        @endphp
                        <span class="badge badge-{{ $statusVal }}">{{ ucfirst($statusVal) }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="details-table">
        <tr>
            <td>
                <div class="section-title">Billed To</div>
                <div class="party-name">{{ $invoice->customer?->name }}</div>
                <div class="party-meta">
                    @if($invoice->customer?->company)
                        <strong>{{ $invoice->customer->company }}</strong><br>
                    @endif
                    @if($invoice->customer?->email)
                        {{ $invoice->customer->email }}<br>
                    @endif
                    @if($invoice->customer?->phone)
                        {{ $invoice->customer->phone }}<br>
                    @endif
                    @if($invoice->customer?->address)
                        {{ $invoice->customer->address }}
                    @endif
                </div>
            </td>
            <td>
                <div class="section-title">Subscription &amp; Period</div>
                <div class="party-meta">
                    <strong>Subscription:</strong> {{ $invoice->subscription?->number ?? 'N/A' }}<br>
                    @if($invoice->subscription?->plan)
                        <strong>Plan:</strong> {{ $invoice->subscription->plan->name }} ({{ ucfirst($invoice->subscription->plan->billing_period->value ?? $invoice->subscription->plan->billing_period) }})<br>
                    @endif
                    <strong>Billing Period:</strong><br>
                    {{ $invoice->billing_period_start?->format('M j, Y') }} &ndash; {{ $invoice->billing_period_end?->format('M j, Y') }}<br>
                    @if($invoice->sent_at)
                        <strong>Sent on:</strong> {{ $invoice->sent_at->format('M j, Y H:i') }}<br>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="lines-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Description</th>
                <th class="text-center" style="width: 10%;">Qty</th>
                <th class="text-right" style="width: 12%;">Unit Price</th>
                <th class="text-right" style="width: 12%;">Discount</th>
                <th class="text-right" style="width: 8%;">Tax</th>
                <th class="text-right" style="width: 13%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->lines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $line->description ?: ($line->product?->name ?? 'Line item') }}</div>
                        @if($line->product && $line->description && $line->description !== $line->product->name)
                            <div class="item-desc">{{ $line->product->name }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $line->quantity }}</td>
                    <td class="text-right">${{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $line->discount_amount, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $line->tax_amount, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $line->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #64748b;">No line items on this invoice.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals-table-container">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="val">${{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
            @if((float) $invoice->discount_total > 0)
                <tr>
                    <td class="label">Discount:</td>
                    <td class="val" style="color: #16a34a;">-${{ number_format((float) $invoice->discount_total, 2) }}</td>
                </tr>
            @endif
            @if((float) $invoice->tax_total > 0)
                <tr>
                    <td class="label">Tax:</td>
                    <td class="val">${{ number_format((float) $invoice->tax_total, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td class="label">Total Amount:</td>
                <td class="val">${{ number_format((float) $invoice->total, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Amount Paid:</td>
                <td class="val">${{ number_format((float) $invoice->amount_paid, 2) }}</td>
            </tr>
            <tr class="balance-row">
                <td class="label">Balance Due:</td>
                <td class="val">${{ number_format($invoice->balance(), 2) }}</td>
            </tr>
        </table>
    </div>

    @if($invoice->payments && $invoice->payments->isNotEmpty())
        <div style="margin-top: 15px;">
            <div class="section-title">Payment History</div>
            <table class="lines-table" style="font-size: 11px;">
                <thead>
                    <tr>
                        <th style="width: 25%;">Date</th>
                        <th style="width: 30%;">Method</th>
                        <th style="width: 25%; text-align: right;">Amount</th>
                        <th style="width: 20%;">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_on?->format('M j, Y') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->method->value ?? $payment->method)) }}</td>
                            <td class="text-right">${{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ $payment->note ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">
        Brightloop Inc. &bull; Questions regarding this invoice? Email billing@brightloop.test &bull; Thank you for your business.
    </div>

</body>
</html>
