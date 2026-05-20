@if (!empty($tboBookingDetail))
    @php
        $tboDetailJson = $tboBookingDetail['response'] ?? null;
        if (is_array($tboDetailJson)) {
            $tboDetailPretty = json_encode($tboDetailJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif (is_string($tboDetailJson)) {
            $tboDetailPretty = $tboDetailJson;
        } else {
            $tboDetailPretty = '—';
        }
    @endphp
    <div class="bkpd-card mt-3">
        <div class="bkpd-card__section-head bkpd-card__section-head--purple">
            <i class="bx bx-test-tube"></i> TBO BookingDetail (test)
        </div>
        <div class="p-3">
            @if (!empty($tboBookingDetail['ok']))
                <p class="small text-success mb-2"><strong>Success.</strong> Live response from TBO <code>BookingDetail</code> API.</p>
            @else
                <p class="small text-danger mb-2">
                    <strong>Failed.</strong> {{ $tboBookingDetail['error'] ?? 'Could not fetch TBO booking detail.' }}
                </p>
            @endif
            @if (!empty($tboBookingDetail['payload']))
                <p class="small text-muted mb-2">
                    Request:
                    <code>{{ json_encode($tboBookingDetail['payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code>
                    @if (!empty($tboBookingDetail['http_status']))
                        · HTTP {{ $tboBookingDetail['http_status'] }}
                    @endif
                </p>
            @endif
            <pre style="margin:0;padding:12px;border-radius:8px;background:#1e1e2e;color:#cdd6f4;font-size:12px;line-height:1.5;white-space:pre-wrap;word-break:break-word;max-height:480px;overflow:auto;">{{ $tboDetailPretty }}</pre>
            <p class="small text-muted mt-2 mb-0">Also logged to <code>storage/logs/laravel.log</code> as <strong>TBO BookingDetail (admin hotel booking show)</strong>.</p>
        </div>
    </div>
@endif
