@php
    $booker = $vendor ?? null;
@endphp
@if ($booker)
    <div class="bkpd-card mb-3 admin-booking-vendor">
        <div class="bkpd-card__section-head bkpd-card__section-head--purple"><i class="bx bx-briefcase"></i> Vendor</div>
        <div class="bkpd-info-rows">
            <div class="bkpd-info-row">
                <span class="bkpd-info-row__label">Agency</span>
                <span class="bkpd-info-row__val">
                    @include('admin.partials.booking-vendor-agency', ['vendor' => $booker])
                </span>
            </div>
            <div class="bkpd-info-row">
                <span class="bkpd-info-row__label">Booked by</span>
                <span class="bkpd-info-row__val">
                    @include('admin.partials.booking-vendor-booker', ['vendor' => $booker])
                </span>
            </div>
        </div>
    </div>
@endif
