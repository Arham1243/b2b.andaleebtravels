@php
    $booker = $vendor ?? null;
    $agency = bookingVendorAgency($booker);
@endphp
@if ($agency)
    <a href="{{ route('admin.vendors.show', $agency) }}" class="link fw-semibold" style="font-size:13px;">
        {{ $agency->display_agency_name ?: $agency->name }}
    </a>
    @if ($agency->agent_code)
        <div class="small text-muted" style="font-size:11px;">{{ $agency->agent_code }}</div>
    @endif
@else
    <span class="text-muted">-</span>
@endif
