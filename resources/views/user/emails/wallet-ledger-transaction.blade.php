@php
    $vendor = $entry->vendor;
    $recipientName = $vendor?->contact_name ?: ($vendor?->display_agency_name ?: 'there');
    $reference = $entry->userReferenceLink();
    $referenceLabel = $reference['label'] !== '' ? $reference['label'] : null;
    $referenceUrl = $reference['url'] ?? null;
    $transactionDate = $entry->created_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A');
    $ledgerUrl = route('user.profile.walletLedger');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet transaction - {{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#222222;background:#ffffff;">
    @include('user.emails.partials.email-banner-logo')

    <p style="margin:0 0 16px;">Dear {{ $recipientName }},</p>

    <table cellpadding="0" cellspacing="0" width="100%" style="max-width:520px;border-collapse:collapse;border:1px solid #e5e5e5;font-size:14px;">
        <tr>
            <td colspan="2" style="padding:10px 12px;background:#f8f9fa;border-bottom:1px solid #e5e5e5;font-weight:600;">
                Wallet transaction
            </td>
        </tr>
        <tr>
            <td style="padding:10px 12px;width:38%;border-bottom:1px solid #eeeeee;color:#666666;">Type</td>
            <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;font-weight:600;">
                {{ $entry->isCredit() ? 'Credit added' : 'Debit deducted' }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;color:#666666;">Amount</td>
            <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;font-weight:600;">
                AED {{ number_format((float) $entry->amount, 2) }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;color:#666666;">Current balance</td>
            <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;font-weight:600;">
                AED {{ number_format((float) $entry->balance_after, 2) }}
            </td>
        </tr>
        @if ($referenceLabel)
            <tr>
                <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;color:#666666;">Reference</td>
                <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;">
                    @if ($referenceUrl)
                        <a href="{{ $referenceUrl }}" style="color:#0d6efd;text-decoration:none;">{{ $referenceLabel }}</a>
                    @else
                        {{ $referenceLabel }}
                    @endif
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;color:#666666;vertical-align:top;">Details</td>
            <td style="padding:10px 12px;border-bottom:1px solid #eeeeee;">{{ $entry->description }}</td>
        </tr>
        <tr>
            <td style="padding:10px 12px;color:#666666;">Date</td>
            <td style="padding:10px 12px;">{{ $transactionDate }}</td>
        </tr>
    </table>

    <p style="margin:16px 0 0;font-size:12px;">
        <a href="{{ $ledgerUrl }}" style="color:#0d6efd;">View wallet ledger</a>
    </p>
</body>
</html>
