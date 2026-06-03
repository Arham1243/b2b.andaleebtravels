@php
    $recipientName = $vendor->contact_name ?: ($vendor->display_agency_name ?: 'there');
    $rechargeUrl = route('user.wallet.recharge.card');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment reminder - {{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#222222;background:#ffffff;">
    @include('user.emails.partials.email-banner-logo')

    <p style="margin:0 0 12px;">Dear {{ $recipientName }},</p>

    <p style="margin:0 0 16px;">
        This is a reminder to top up your wallet so you can continue booking without interruption.
        Please recharge your account at your earliest convenience.
    </p>

    <table cellpadding="0" cellspacing="0" width="100%" style="max-width:520px;border-collapse:collapse;border:1px solid #e5e5e5;font-size:14px;">
        <tr>
            <td colspan="2" style="padding:10px 12px;background:#f8f9fa;border-bottom:1px solid #e5e5e5;font-weight:600;">
                Wallet summary
            </td>
        </tr>
        <tr>
            <td style="padding:10px 12px;width:38%;border-bottom:1px solid #eeeeee;color:#666666;">Available balance</td>
            <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;font-weight:600;">
                AED {{ number_format($vendor->availableBalanceAmount(), 2) }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;color:#666666;">Used balance</td>
            <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;">
                AED {{ number_format($vendor->usedBalanceAmount(), 2) }}
            </td>
        </tr>
    </table>

    <p style="margin:16px 0 0;font-size:12px;">
        <a href="{{ $rechargeUrl }}" style="color:#0d6efd;">Recharge wallet</a>
    </p>
</body>
</html>
