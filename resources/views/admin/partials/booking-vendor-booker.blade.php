@php
    $booker = $vendor ?? null;
    $isSubAgent = bookingVendorIsSubAgent($booker);
@endphp
@if ($booker)
    @if ($isSubAgent)
        <span class="badge rounded-pill bg-light text-dark border" style="font-size:10px; font-weight:700;">Sub agent</span>
        <div class="mt-1">
            <a href="{{ route('admin.vendors.show', $booker) }}" class="link" style="font-size:13px;">
                {{ $booker->name }}
            </a>
        </div>
        <div class="small text-muted" style="font-size:11px; line-height:1.35;">
            @if ($booker->username)
                <span>{{ $booker->username }}</span>
            @endif
            @if ($booker->email)
                <span>@if ($booker->username) &middot; @endif{{ $booker->email }}</span>
            @endif
        </div>
    @else
        <span class="badge rounded-pill bg-primary" style="font-size:10px; font-weight:700;">Agency owner</span>
        <div class="mt-1 small text-muted" style="font-size:11px; line-height:1.35;">
            @if ($booker->contact_name)
                <div>{{ $booker->contact_name }}</div>
            @endif
            @if ($booker->username)
                <div>{{ $booker->username }}</div>
            @endif
            @if ($booker->email)
                <div>{{ $booker->email }}</div>
            @endif
        </div>
    @endif
@else
    <span class="text-muted">-</span>
@endif
