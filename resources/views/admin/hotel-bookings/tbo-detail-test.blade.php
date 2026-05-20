@extends('admin.layouts.main')

@push('css')
<style>
    .tbo-test-meta {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 0.75rem 1.25rem;
        margin-bottom: 1.25rem;
    }
    .tbo-test-meta__item {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .tbo-test-meta__label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b6573;
    }
    .tbo-test-meta__value {
        font-size: 0.9rem;
        color: #18181b;
        word-break: break-word;
    }
    .tbo-test-result {
        border: 1px solid #ebecf0;
        border-radius: 12px;
        background: #fff;
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(20, 20, 30, 0.04);
    }
    .tbo-test-result__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #f0f1f4;
        background: #fafbfc;
    }
    .tbo-test-result__title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #18181b;
        margin: 0;
    }
    .tbo-test-result__sub {
        font-size: 0.78rem;
        color: #6b6573;
        margin: 0.15rem 0 0;
    }
    .tbo-test-result__body {
        padding: 0.85rem 1rem 1rem;
    }
    .tbo-test-payload {
        font-size: 0.78rem;
        color: #4b4753;
        margin-bottom: 0.65rem;
        word-break: break-all;
    }
    .tbo-test-response {
        margin: 0;
        padding: 0.85rem;
        border-radius: 8px;
        background: #1e1e2e;
        color: #cdd6f4;
        font-size: 0.78rem;
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 420px;
        overflow: auto;
    }
    .tbo-test-status {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
    }
    .tbo-test-status--ok { background: rgba(46, 125, 50, 0.12); color: #2e7d32; }
    .tbo-test-status--warn { background: rgba(179, 89, 0, 0.12); color: #b35900; }
    .tbo-test-status--err { background: rgba(179, 38, 30, 0.12); color: #b3261e; }
</style>
@endpush

@section('content')
<div class="col-md-12">
    <div class="dashboard-content py-3">
        {{ Breadcrumbs::render('admin.hotel-bookings.tbo-detail-test', $booking) }}

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <h1 style="font-size:1.35rem; font-weight:700; color:#18181b; margin:0 0 .25rem;">
                    TBO Booking Detail Test
                </h1>
                <p style="margin:0; font-size:.88rem; color:#6b6573;">
                    Probes TBO detail endpoints for booking <strong>{{ $booking->booking_number }}</strong>.
                </p>
            </div>
            <a href="{{ route('admin.hotel-bookings.show', $booking->id) }}" class="themeBtn">
                <i class="bx bx-arrow-back"></i> Back to booking
            </a>
        </div>

        <div class="custom-sec mb-4">
            <div class="custom-sec__header">
                <div class="section-content">
                    <h3 class="heading">Reference values used</h3>
                </div>
            </div>
            <div class="tbo-test-meta">
                <div class="tbo-test-meta__item">
                    <span class="tbo-test-meta__label">Booking ID</span>
                    <span class="tbo-test-meta__value">{{ $report['meta']['booking_id'] }}</span>
                </div>
                <div class="tbo-test-meta__item">
                    <span class="tbo-test-meta__label">Booking Number</span>
                    <span class="tbo-test-meta__value">{{ $report['meta']['booking_number'] }}</span>
                </div>
                <div class="tbo-test-meta__item">
                    <span class="tbo-test-meta__label">Supplier</span>
                    <span class="tbo-test-meta__value">{{ $report['meta']['supplier'] ?? '—' }}</span>
                </div>
                <div class="tbo-test-meta__item">
                    <span class="tbo-test-meta__label">Client Reference</span>
                    <span class="tbo-test-meta__value">{{ $report['meta']['client_ref'] ?? '—' }}</span>
                </div>
                <div class="tbo-test-meta__item">
                    <span class="tbo-test-meta__label">Confirmation</span>
                    <span class="tbo-test-meta__value">{{ $report['meta']['confirmation'] ?? '—' }}</span>
                </div>
                <div class="tbo-test-meta__item">
                    <span class="tbo-test-meta__label">TBO Booking ID</span>
                    <span class="tbo-test-meta__value">{{ $report['meta']['tbo_booking_id'] ?? '—' }}</span>
                </div>
            </div>
        </div>

        <div class="custom-sec">
            <div class="custom-sec__header">
                <div class="section-content">
                    <h3 class="heading">API responses</h3>
                    <p class="small text-muted mb-0">{{ count($report['results']) }} requests sent</p>
                </div>
            </div>

            @foreach ($report['results'] as $result)
                @php
                    $statusClass = ($result['success'] ?? false)
                        ? 'tbo-test-status--ok'
                        : (($result['error'] ?? false) ? 'tbo-test-status--err' : 'tbo-test-status--warn');
                    $prettyBody = $result['body'];
                    $decoded = json_decode($result['body'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $prettyBody = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    }
                @endphp
                <div class="tbo-test-result">
                    <div class="tbo-test-result__head">
                        <div>
                            <p class="tbo-test-result__title">{{ $result['endpoint'] }} · {{ $result['label'] }}</p>
                            <p class="tbo-test-result__sub">{{ $result['url'] }}</p>
                        </div>
                        <span class="tbo-test-status {{ $statusClass }}">
                            HTTP {{ $result['status'] ?? 'ERR' }}
                        </span>
                    </div>
                    <div class="tbo-test-result__body">
                        <div class="tbo-test-payload">
                            <strong>Payload:</strong>
                            {{ json_encode($result['payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}
                        </div>
                        <pre class="tbo-test-response">{{ $prettyBody }}</pre>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
