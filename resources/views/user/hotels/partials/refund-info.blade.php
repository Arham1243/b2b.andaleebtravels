{{--
    Refundable status + short policy line (listing cards & hotel detail sidebar).
    Expects keys on $hotel: is_refundable (bool|null), refund_policy_summary (string|null)
--}}
@php
    $hrIsRef = $hotel['is_refundable'] ?? null;
    $hrSummary = trim((string) ($hotel['refund_policy_summary'] ?? ''));
@endphp
@if ($hrIsRef !== null || $hrSummary !== '')
    <div class="{{ $wrapperClass ?? 'hotel-refund-block' }}">
        @if ($hrIsRef === true)
            <span class="hotel-refund-pill hotel-refund-pill--yes"><i class="bx bx-check-shield"></i> Refundable</span>
        @elseif ($hrIsRef === false)
            <span class="hotel-refund-pill hotel-refund-pill--no"><i class="bx bx-x-circle"></i> Non-refundable</span>
        @endif
        @if ($hrSummary !== '')
            <span class="hotel-refund-note">{{ $hrSummary }}</span>
        @endif
    </div>
@endif
