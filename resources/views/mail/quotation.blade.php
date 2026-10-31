<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Brightloop Quotation</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="background-color: #4f46e5; padding: 20px 24px; color: #ffffff;">
            <h1 style="margin: 0; font-size: 20px; font-weight: 700;">Brightloop</h1>
            <p style="margin: 4px 0 0; font-size: 13px; color: #c7d2fe;">Subscription Management</p>
        </div>
        <div style="padding: 24px;">
            <p style="font-size: 15px; margin-top: 0;">Hello {{ $subscription->customer->name ?? 'there' }},</p>
            <p style="font-size: 14px; line-height: 1.5; color: #475569;">
                Your quotation <strong>{{ $subscription->number }}</strong> for the <strong>{{ $subscription->plan->name ?? 'subscription plan' }}</strong> is ready for your review.
            </p>
            <p style="font-size: 14px; line-height: 1.5; color: #475569;">
                The detailed PDF document has been attached to this email. You can also view, accept, or review the quotation directly online in the customer portal.
            </p>
            @if($subscription->quotation_valid_until)
                <p style="font-size: 13px; color: #b45309; background-color: #fef3c7; padding: 8px 12px; border-radius: 4px;">
                    This quotation is valid until <strong>{{ $subscription->quotation_valid_until->format('d M Y') }}</strong>.
                </p>
            @endif
            <div style="margin: 28px 0; text-align: center;">
                <a href="{{ $portalUrl }}" style="display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 24px; font-size: 14px; font-weight: 600; border-radius: 6px;">
                    View &amp; Accept Quotation Online
                </a>
            </div>
            <p style="font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-bottom: 0;">
                If you have any questions or require changes, please contact your account manager.
            </p>
        </div>
    </div>
</body>
</html>
