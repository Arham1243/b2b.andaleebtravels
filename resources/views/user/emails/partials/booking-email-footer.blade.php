@php
    $brandName = trim((string) (config('app.name') ?: 'Travel'));
    $siteUrl = rtrim((string) (config('app.url') ?: url('/')), '/');
@endphp

<div class="footer">
    <p class="footer-text">
        &copy; {{ date('Y') }} {{ $brandName }}
        @if ($siteUrl !== '')
            <a href="{{ $siteUrl }}">{{ parse_url($siteUrl, PHP_URL_HOST) ?: $siteUrl }}</a>
        @endif
    </p>
    @isset($footerExtra)
        <p class="footer-text" style="margin-top:8px;">{!! $footerExtra !!}</p>
    @endisset
</div>
