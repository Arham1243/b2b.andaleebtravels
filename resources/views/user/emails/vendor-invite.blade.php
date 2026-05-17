@php
    $brand = config('app.name', 'Travel');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Welcome - {{ $brand }}</title>
    @include('user.emails.partials.booking-email-styles')
</head>

<body>
    @include('user.emails.partials.booking-email-shell-open')
    @include('user.emails.partials.email-banner-logo')

    <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
        <tr>
            <td align="center">
                <p class="muted" style="font-size:12px;font-weight:700;color:#999999;text-transform:uppercase;margin:0 0 8px;">
                    {{ $brand }} - B2B
                </p>
                <h1>Welcome, {{ $vendor->name }}</h1>
                <p class="muted" style="margin:12px 0 0;">Your vendor account has been created. Below are your login credentials.</p>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" class="section-padding border-bottom">
        <tr>
            <td>
                <h2>Login credentials</h2>
                <div class="credentials-wrap">
                    <table width="100%" cellpadding="8" cellspacing="0">
                        <tr>
                            <td style="padding:6px 0;border-bottom:1px solid #eeeeee;">
                                <div class="label">Agent code</div>
                                <div class="data-text">{{ $vendor->agent_code }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;border-bottom:1px solid #eeeeee;">
                                <div class="label">Username</div>
                                <div class="data-text">{{ $vendor->username }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;">
                                <div class="label">Password</div>
                                <div class="data-text conf-ref" style="font-size:14px;">{{ $plainPassword }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:8px 0 12px;">
                <p class="muted" style="margin-bottom:12px;">Use the button below to sign in:</p>
                <a href="{{ route('auth.login') }}" class="btn-view">Login to your account</a>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:12px 0 24px;">
                <p class="muted" style="margin-bottom:12px;">If you want to reset your password, you can do it here:</p>
                <a href="{{ route('password.request') }}" class="btn-view" style="background-color:#424242;">Reset password</a>
            </td>
        </tr>
    </table>

    <div class="note-box">
        <strong>Important:</strong> Please change your password after your first login for security purposes.
    </div>

    @include('user.emails.partials.booking-email-footer')
    @include('user.emails.partials.booking-email-shell-close')
</body>

</html>
