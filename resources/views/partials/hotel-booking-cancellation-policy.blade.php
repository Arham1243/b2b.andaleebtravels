@php
    $cancellation = $cancellation ?? [];
    $isRefundable = $cancellation['is_refundable'] ?? null;
    $policySummary = trim((string) ($cancellation['policy_summary'] ?? ''));
    $deadlineFormatted = trim((string) ($cancellation['deadline_formatted'] ?? ''));
    $canCancel = (bool) ($cancellation['can_cancel'] ?? false);
@endphp

<div class="bkpd-cancel-policy">
    <div class="bkpd-cancel-policy__head">Cancellation policy</div>

    @if ($isRefundable === true)
        <span class="bkpd-refund-pill bkpd-refund-pill--yes"><i class="bx bx-check-shield"></i> Refundable booking</span>
    @elseif ($isRefundable === false)
        <span class="bkpd-refund-pill bkpd-refund-pill--no"><i class="bx bx-x-circle"></i> Non-refundable booking</span>
    @endif

    @if ($policySummary !== '')
        <p class="bkpd-cancel-policy__text">{{ $policySummary }}</p>
    @endif

    @if ($deadlineFormatted !== '' && $canCancel)
        <p class="bkpd-cancel-policy__deadline"><i class="bx bx-time-five"></i> Cancel before {{ $deadlineFormatted }}</p>
    @endif
</div>
