@php
    $brand = config('app.name', 'Travel');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Trade License Expired - {{ $brand }}</title>
    @include('user.emails.partials.booking-email-styles')
</head>
<body>
    @include('user.emails.partials.booking-email-shell-open')
    @include('user.emails.partials.email-banner-logo')

    <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
        <tr>
            <td align="center">
                <h1>Trade license expired</h1>
                <p class="muted" style="margin:12px 0 0;">Dear {{ $vendor->contact_name ?: $agencyName }}, your agency trade license expired on <strong>{{ $expiryDate }}</strong>. Portal access is disabled until your license is renewed and updated.</p>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:12px 0 24px;">
                <p class="muted" style="font-size:13px;">Please contact the administrator to update your trade license details.</p>
            </td>
        </tr>
    </table>

    @include('user.emails.partials.booking-email-footer')
    @include('user.emails.partials.booking-email-shell-close')
</body>
</html>
