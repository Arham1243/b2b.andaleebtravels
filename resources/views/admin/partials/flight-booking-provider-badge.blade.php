@once
    @push('css')
        <style>
            .fb-provider-badge {
                display: inline-flex;
                align-items: center;
                padding: .2rem .55rem;
                border-radius: 999px;
                font-size: .68rem;
                font-weight: 700;
                letter-spacing: .04em;
                text-transform: uppercase;
                line-height: 1.2;
                white-space: nowrap;
            }

            .fb-provider-badge--sabre {
                background: #eef4ff;
                color: #1d4ed8;
                border: 1px solid #bfdbfe;
            }

            .fb-provider-badge--travelport {
                background: #ecfdf5;
                color: #047857;
                border: 1px solid #a7f3d0;
            }
        </style>
    @endpush
@endonce

@php
    $providerKey = normalizeFlightBookingProvider($booking->provider ?? null);
    $providerLabel = formatFlightBookingProviderLabel($booking->provider ?? null);
@endphp
<span class="fb-provider-badge fb-provider-badge--{{ $providerKey }}" title="GDS provider">{{ $providerLabel }}</span>
