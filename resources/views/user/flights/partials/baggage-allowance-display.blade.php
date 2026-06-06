@php
    $friendly = $friendly ?? null;
    $fallback = trim((string) ($fallback ?? 'Not included'));
    $amount = trim((string) ($friendly['amount'] ?? ''));
@endphp

@if ($amount !== '' && $amount !== 'Not included')
    <span class="fd-bag__amt">{{ $amount }}</span>
@else
    <span class="fd-bag__amt fd-bag__amt--na">{{ $fallback }}</span>
@endif
