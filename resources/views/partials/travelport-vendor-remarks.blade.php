@php
    $vendorRemarks = $booking->travelportVendorRemarks();
@endphp

@if ($booking->isTravelport() && $vendorRemarks !== [])
    <div class="bkpd-vendor-remarks mb-3">
        <div class="bkpd-vendor-remarks__head">
            <i class="bx bx-info-circle"></i>
            <span>Airline notices (Ticket Time LImit)</span>
        </div>
        <ul class="bkpd-vendor-remarks__list">
            @foreach ($vendorRemarks as $remark)
                <li>{{ $remark }}</li>
            @endforeach
        </ul>
    </div>

    <style>
        .bkpd-vendor-remarks {
            border: 1px solid rgba(205, 27, 79, .18);
            background: rgba(205, 27, 79, .04);
            border-radius: 12px;
            padding: 14px 16px;
        }
        .bkpd-vendor-remarks__head {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-weight: 700;
            color: #8b1538;
            margin-bottom: .55rem;
        }
        .bkpd-vendor-remarks__head i {
            font-size: 1.1rem;
        }
        .bkpd-vendor-remarks__list {
            margin: 0;
            padding-left: 1.15rem;
            color: #4a3340;
        }
        .bkpd-vendor-remarks__list li + li {
            margin-top: .35rem;
        }
    </style>
@endif
