<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Activate Your Account</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="background-color: #4f46e5; padding: 20px 24px; color: #ffffff;">
            <h1 style="margin: 0; font-size: 20px; font-weight: 700;">Brightloop</h1>
            <p style="margin: 4px 0 0; font-size: 13px; color: #c7d2fe;">Customer Portal Access</p>
        </div>
        <div style="padding: 24px;">
            <p style="font-size: 15px; margin-top: 0;">Hello {{ $customer->name }},</p>
            <p style="font-size: 14px; line-height: 1.5; color: #475569;">
                An account has been prepared for you on the Brightloop Customer Portal. You can track your quotations, manage subscriptions, view invoices, and make payments online.
            </p>
            <p style="font-size: 14px; line-height: 1.5; color: #475569;">
                Please click the button below to set your password and activate your portal access:
            </p>
            <div style="margin: 28px 0; text-align: center;">
                <a href="{{ $activationUrl }}" style="display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 24px; font-size: 14px; font-weight: 600; border-radius: 6px;">
                    Activate Account &amp; Set Password
                </a>
            </div>
            <p style="font-size: 13px; color: #64748b;">
                Or copy and paste this link into your browser:<br>
                <a href="{{ $activationUrl }}" style="color: #4f46e5; word-break: break-all;">{{ $activationUrl }}</a>
            </p>
            <p style="font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-bottom: 0;">
                If you did not request this invitation, please disregard this email.
            </p>
        </div>
    </div>
</body>
</html>
