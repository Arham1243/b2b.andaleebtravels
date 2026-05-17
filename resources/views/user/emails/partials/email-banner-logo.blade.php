@php
    $__emailLogoOuterTbl = trim((string) ($emailLogoOuterTableStyle ?? ''));
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
    @if ($__emailLogoOuterTbl !== '') style="{{ $__emailLogoOuterTbl }}" @endif>
    <tr>
        <td align="center" style="padding:0 0 16px 0;line-height:0;font-size:0;">
            @include('user.emails.partials.email-banner-logo-img')
        </td>
    </tr>
</table>
