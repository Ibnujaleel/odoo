<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
            line-height: 1.6;
            margin: 0;
            padding: 24px;
            background-color: #f8fafc;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 32px;
            border: 1px solid #e2e8f0;
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
        }
        .title {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin: 16px 0 8px 0;
        }
        .summary-box {
            background-color: #f1f5f9;
            border-radius: 6px;
            padding: 16px;
            margin: 20px 0;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .summary-row:last-child {
            margin-bottom: 0;
            font-weight: bold;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
        }
        .button {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 16px;
        }
        .footer {
            margin-top: 32px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Brightloop</div>
        </div>

        <h1 class="title">Invoice {{ $invoice->number }}</h1>

        <p>Hello {{ $invoice->customer?->name ?? 'Valued Customer' }},</p>

        <p>Please find attached your invoice for subscription <strong>{{ $invoice->subscription?->number ?? 'N/A' }}</strong>.</p>

        <div class="summary-box">
            <div class="summary-row">
                <span>Invoice Number:</span>
                <strong>{{ $invoice->number }}</strong>
            </div>
            <div class="summary-row">
                <span>Billing Period:</span>
                <span>{{ $invoice->billing_period_start?->format('M j, Y') }} &ndash; {{ $invoice->billing_period_end?->format('M j, Y') }}</span>
            </div>
            <div class="summary-row">
                <span>Due Date:</span>
                <span>{{ $invoice->due_date?->format('M j, Y') }}</span>
            </div>
            <div class="summary-row">
                <span>Total Amount:</span>
                <span>${{ number_format((float) $invoice->total, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>Amount Paid:</span>
                <span>${{ number_format((float) $invoice->amount_paid, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>Balance Due:</span>
                <span style="color: #e11d48;">${{ number_format($invoice->balance(), 2) }}</span>
            </div>
        </div>

        <p>A copy of this invoice in PDF format has been attached to this email for your records.</p>

        <div style="text-align: center; margin-top: 24px;">
            <a href="{{ url('/portal/invoices') }}" class="button">View in Customer Portal</a>
        </div>

        <div class="footer">
            <p>Thank you for your business!<br>The Brightloop Team</p>
        </div>
    </div>
</body>
</html>
