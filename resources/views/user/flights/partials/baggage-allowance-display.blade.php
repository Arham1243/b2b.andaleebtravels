@php
    $friendly = $friendly ?? null;
    $fallback = trim((string) ($fallback ?? 'Not included'));
    $amount = trim((string) ($friendly['amount'] ?? ''));
    $label = trim((string) ($friendly['label'] ?? ''));
    $note = trim((string) ($friendly['note'] ?? ''));
@endphp

@if ($amount !== '' && $amount !== 'Not included')
    <span class="fd-bag__amt">{{ $amount }}</span>
    @if ($label !== '')
        <span class="fd-bag__amt-label">({{ $label }})</span>
    @endif
@else
    <span class="fd-bag__amt fd-bag__amt--na">{{ $fallback }}</span>
@endif
