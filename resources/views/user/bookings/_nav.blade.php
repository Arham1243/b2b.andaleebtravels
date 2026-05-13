{{--
    Shared bookings sidebar nav.
    Expects: $counts = ['flights' => int, 'hotels' => int]
    Expects: $activeSection = 'flights' | 'hotels'
--}}
<aside class="bk-nav">
    <div class="bk-nav__head">
        <div class="bk-nav__logo"><i class="bx bx-briefcase-alt-2"></i></div>
        <div>
            <div class="bk-nav__title">My Bookings</div>
            <div class="bk-nav__sub">Manage all your reservations</div>
        </div>
    </div>

    <nav class="bk-nav__menu">
        <div class="bk-nav__section-label">Bookings</div>

        <a href="{{ route('user.bookings.flights') }}"
           class="bk-nav__item {{ ($activeSection ?? '') === 'flights' ? 'bk-nav__item--active' : '' }}">
            <span class="bk-nav__item-icon"><i class="bx bxs-plane-take-off"></i></span>
            <span class="bk-nav__item-text">Flight Bookings</span>
            <span class="bk-nav__item-count">{{ $counts['flights'] ?? 0 }}</span>
        </a>

        <a href="{{ route('user.bookings.hotels') }}"
           class="bk-nav__item {{ ($activeSection ?? '') === 'hotels' ? 'bk-nav__item--active' : '' }}">
            <span class="bk-nav__item-icon"><i class="bx bxs-hotel"></i></span>
            <span class="bk-nav__item-text">Hotel Bookings</span>
            <span class="bk-nav__item-count">{{ $counts['hotels'] ?? 0 }}</span>
        </a>

        <div class="bk-nav__section-label" style="margin-top:16px;">Coming Soon</div>

        <div class="bk-nav__item bk-nav__item--disabled">
            <span class="bk-nav__item-icon"><i class="bx bx-map-alt"></i></span>
            <span class="bk-nav__item-text">Tour Bookings</span>
            <span class="bk-nav__item-pill">Soon</span>
        </div>

        <div class="bk-nav__item bk-nav__item--disabled">
            <span class="bk-nav__item-icon"><i class="bx bx-shield-alt-2"></i></span>
            <span class="bk-nav__item-text">Insurance</span>
            <span class="bk-nav__item-pill">Soon</span>
        </div>
    </nav>

    <div class="bk-nav__footer">
        <a href="{{ route('user.flights.index') }}" class="bk-nav__new-btn">
            <i class="bx bx-plus"></i> New Flight Search
        </a>
    </div>
</aside>
