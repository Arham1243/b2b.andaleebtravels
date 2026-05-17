@php
    $psUser = Auth::user();
    $psBalance = $psUser->main_balance ?? 0;
@endphp

<aside class="ps-nav">

    {{-- Header --}}
    <div class="ps-nav__head">
        <div class="ps-nav__logo"><i class="bx bxs-user-circle"></i></div>
        <div>
            <div class="ps-nav__title">My Profile</div>
            <div class="ps-nav__sub">Manage account settings</div>
        </div>
    </div>

    {{-- Balance pill --}}
    <div class="ps-nav__balance">
        <i class="bx bxs-wallet"></i>
        <span>Balance:</span>
        <strong><span class="dirham">AED</span>&nbsp;{{ number_format((float) $psBalance, 2) }}</strong>
    </div>

    <nav class="ps-nav__menu">

        {{-- ACCOUNT --}}
        <div class="ps-nav__section-label">Account</div>

        <a href="{{ route('user.profile.personalInfo') }}"
           class="ps-nav__item {{ request()->routeIs('user.profile.personalInfo') ? 'ps-nav__item--active' : '' }}">
            <span class="ps-nav__item-icon"><i class="bx bxs-id-card"></i></span>
            <span class="ps-nav__item-text">Personal Info</span>
            @if(request()->routeIs('user.profile.personalInfo'))
                <i class="bx bx-chevron-right ps-nav__item-arrow"></i>
            @endif
        </a>

        <a href="{{ route('user.profile.changePassword') }}"
           class="ps-nav__item {{ request()->routeIs('user.profile.changePassword') ? 'ps-nav__item--active' : '' }}">
            <span class="ps-nav__item-icon"><i class="bx bxs-lock-alt"></i></span>
            <span class="ps-nav__item-text">Change Password</span>
            @if(request()->routeIs('user.profile.changePassword'))
                <i class="bx bx-chevron-right ps-nav__item-arrow"></i>
            @endif
        </a>

        <div class="ps-nav__divider"></div>

        {{-- BILLING --}}
        <div class="ps-nav__section-label">Billing</div>

        <a href="{{ route('user.wallet.recharge') }}"
           class="ps-nav__item {{ request()->routeIs('user.wallet.*') ? 'ps-nav__item--active' : '' }}">
            <span class="ps-nav__item-icon"><i class="bx bxs-wallet-alt"></i></span>
            <span class="ps-nav__item-text">Wallet &amp; Recharge</span>
            @if(request()->routeIs('user.wallet.*'))
                <i class="bx bx-chevron-right ps-nav__item-arrow"></i>
            @endif
        </a>

        <div class="ps-nav__divider"></div>

        {{-- TEAM --}}
        <div class="ps-nav__section-label">Team</div>

        <a href="{{ route('user.sub-agents.index') }}"
           class="ps-nav__item {{ request()->routeIs('user.sub-agents.*') ? 'ps-nav__item--active' : '' }}">
            <span class="ps-nav__item-icon"><i class="bx bx-user-plus"></i></span>
            <span class="ps-nav__item-text">Sub Agents</span>
            @if(request()->routeIs('user.sub-agents.*'))
                <i class="bx bx-chevron-right ps-nav__item-arrow"></i>
            @endif
        </a>

        <div class="ps-nav__divider"></div>

        {{-- BOOKINGS --}}
        <div class="ps-nav__section-label">Bookings</div>

        <a href="{{ route('user.bookings.flights') }}" class="ps-nav__item">
            <span class="ps-nav__item-icon"><i class="bx bxs-plane-take-off"></i></span>
            <span class="ps-nav__item-text">Flight Bookings</span>
        </a>

        <a href="{{ route('user.bookings.hotels') }}" class="ps-nav__item" style="margin-bottom:4px;">
            <span class="ps-nav__item-icon"><i class="bx bxs-hotel"></i></span>
            <span class="ps-nav__item-text">Hotel Bookings</span>
        </a>

    </nav>
</aside>
