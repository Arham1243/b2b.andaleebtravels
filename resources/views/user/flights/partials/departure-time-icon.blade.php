@php
    $slot = $slot ?? flightDepartureTimeSlot($clock ?? null);
    $kind = ($kind ?? 'departure') === 'arrival' ? 'arrival' : 'departure';
    $icons = [
        'night' => ['bx bxs-moon', 'rc__time-icon--moon', 'Night ' . $kind],
        'morning' => ['bx bx-sun', 'rc__time-icon--sun', 'Morning ' . $kind],
        'afternoon' => ['bx bxs-sun', 'rc__time-icon--sun', 'Afternoon ' . $kind],
        'evening' => ['bx bx-moon', 'rc__time-icon--evening', 'Evening ' . $kind],
    ];
    $icon = $icons[$slot] ?? null;
@endphp
@if ($icon)
    <i class="{{ $icon[0] }} rc__time-icon {{ $icon[1] }}" title="{{ $icon[2] }}"></i>
@endif
