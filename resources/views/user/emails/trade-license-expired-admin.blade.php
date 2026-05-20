@php
    $brand = config('app.name', 'Travel');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Trade License Expired - Admin - {{ $brand }}</title>
    @include('user.emails.partials.booking-email-styles')
</head>
<body>
    @include('user.emails.partials.booking-email-shell-open')
    @include('user.emails.partials.email-banner-logo')

    <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
        <tr>
            <td>
                <h1>Agency trade license expired</h1>
                <p class="muted" style="margin:12px 0 16px;">An agency attempted to log in with an expired trade license.</p>
                <div class="credentials-wrap">
                    <table width="100%" cellpadding="8" cellspacing="0">
                        <tr>
                            <td style="padding:6px 0;border-bottom:1px solid #eeeeee;">
                                <div class="label">Agency</div>
                                <div class="data-text">{{ $agencyName }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;border-bottom:1px solid #eeeeee;">
                                <div class="label">Contact email</div>
                                <div class="data-text">{{ $vendor->email }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;border-bottom:1px solid #eeeeee;">
                                <div class="label">Agent code</div>
                                <div class="data-text">{{ $vendor->agent_code }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;">
                                <div class="label">License expiry</div>
                                <div class="data-text">{{ $expiryDate }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    @include('user.emails.partials.booking-email-footer')
    @include('user.emails.partials.booking-email-shell-close')
</body>
</html>
