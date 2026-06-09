@php
    $slot = $slot ?? flightDepartureTimeSlot($clock ?? null);
    $icons = [
        'night' => ['bx bxs-moon', 'rc__time-icon--moon', 'Night departure'],
        'morning' => ['bx bx-sun', 'rc__time-icon--sun', 'Morning departure'],
        'afternoon' => ['bx bxs-sun', 'rc__time-icon--sun', 'Afternoon departure'],
        'evening' => ['bx bx-moon', 'rc__time-icon--evening', 'Evening departure'],
    ];
    $icon = $icons[$slot] ?? null;
@endphp
@if ($icon)
    <i class="{{ $icon[0] }} rc__time-icon {{ $icon[1] }}" title="{{ $icon[2] }}"></i>
@endif
