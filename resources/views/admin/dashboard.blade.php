@extends('admin.layouts.main')

@section('content')
    @php
        $adminName = \Illuminate\Support\Str::title($adminFirstName);
        $today = \Carbon\Carbon::now();
    @endphp

    <div class="col-md-12">
        <div class="dashboard-content admin-dash py-3">
            <header class="admin-dash__hero mb-4">
                <p class="admin-dash__date">{{ $today->format('l, F j, Y') }}</p>
                <h1 class="admin-dash__title">Hello, {{ $adminName }}</h1>
                <p class="admin-dash__lead">
                    @if ($needsAttention > 0)
                        You have <span class="admin-dash__lead-count">{{ $needsAttention }}</span>
                        {{ \Illuminate\Support\Str::plural('item', $needsAttention) }} that need attention today.
                    @else
                        Everything looks calm. Here's a quick overview of your B2B portal.
                    @endif
                </p>
            </header>

            <section class="admin-dash__section mb-3">
                <div class="admin-dash__section-head">
                    <h2 class="admin-dash__section-title">Bookings overview</h2>
                </div>
                <div class="row g-3 admin-dash__kpi-row">
                    <div class="col-sm-6 col-xl-4 d-flex">
                        <a href="{{ route('admin.hotel-bookings.index') }}" class="admin-stat w-100">
                            <span class="admin-stat__head">
                                <span class="admin-stat__label">Hotel bookings</span>
                                <span class="admin-stat__icon"><i class='bx bx-hotel'></i></span>
                            </span>
                            <span class="admin-stat__value">{{ number_format($hotelBookingsPaid) }}</span>
                            <span class="admin-stat__meta">
                                {{ number_format($hotelBookingsTotal) }} total
                                @if ($hotelBookingsPending > 0)
                                    &middot; <span class="admin-stat__meta-warn">{{ $hotelBookingsPending }} pending</span>
                                @endif
                                @if ($hotelBookingsFailed > 0)
                                    &middot; <span class="admin-stat__meta-danger">{{ $hotelBookingsFailed }} failed</span>
                                @endif
                            </span>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-4 d-flex">
                        <a href="{{ route('admin.flight-bookings.index') }}" class="admin-stat w-100">
                            <span class="admin-stat__head">
                                <span class="admin-stat__label">Flight bookings</span>
                                <span class="admin-stat__icon"><i class='bx bx-plane'></i></span>
                            </span>
                            <span class="admin-stat__value">{{ number_format($flightBookingsPaid) }}</span>
                            <span class="admin-stat__meta">
                                {{ number_format($flightBookingsTotal) }} total
                                @if ($flightBookingsPending > 0)
                                    &middot; <span class="admin-stat__meta-warn">{{ $flightBookingsPending }} pending</span>
                                @endif
                                @if ($flightBookingsFailed > 0)
                                    &middot; <span class="admin-stat__meta-danger">{{ $flightBookingsFailed }} failed</span>
                                @endif
                            </span>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-4 d-flex">
                        <a href="{{ route('admin.wallet.bank-transfers.index') }}" class="admin-stat w-100">
                            <span class="admin-stat__head">
                                <span class="admin-stat__label">Wallet top-ups</span>
                                <span class="admin-stat__icon"><i class='bx bx-wallet'></i></span>
                            </span>
                            <span class="admin-stat__value">{{ number_format($walletTransfersPending) }}</span>
                            <span class="admin-stat__meta">
                                Pending bank transfers
                                @if ($walletTransfersPending > 0)
                                    &middot; <span class="admin-stat__meta-warn">needs review</span>
                                @else
                                    &middot; all clear
                                @endif
                            </span>
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-dash__section mb-3">
                <div class="row g-3 admin-dash__quick-row">
                    <div class="col-lg-7 d-flex">
                        <div class="admin-card w-100">
                            <div class="admin-card__head">
                                <div>
                                    <h2 class="admin-card__title">Revenue overview</h2>
                                    <p class="admin-card__subtitle">From paid hotel and flight bookings.</p>
                                </div>
                            </div>
                            <div class="admin-revenue">
                                <div class="admin-revenue__row admin-revenue__row--hero">
                                    <span class="admin-revenue__label">All time</span>
                                    <span class="admin-revenue__value">{!! formatPrice($earningsTotal) !!}</span>
                                </div>
                                <div class="admin-revenue__row">
                                    <span class="admin-revenue__label">This month</span>
                                    <span class="admin-revenue__value">{!! formatPrice($earningsThisMonth) !!}</span>
                                </div>
                                <div class="admin-revenue__row">
                                    <span class="admin-revenue__label">This week</span>
                                    <span class="admin-revenue__value">{!! formatPrice($earningsThisWeek) !!}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 d-flex">
                        <div class="admin-card w-100">
                            <div class="admin-card__head">
                                <div>
                                    <h2 class="admin-card__title">Quick actions</h2>
                                    <p class="admin-card__subtitle mb-0">Jump straight to what needs you.</p>
                                </div>
                            </div>
                            <ul class="admin-actions">
                                @if ($walletTransfersPending > 0)
                                    <li>
                                        <a href="{{ route('admin.wallet.bank-transfers.index') }}">
                                            <span class="admin-actions__icon"><i class='bx bx-time-five'></i></span>
                                            <span class="admin-actions__label">Wallet bank transfers pending</span>
                                            <span class="admin-actions__hint">{{ $walletTransfersPending }}</span>
                                            <i class='bx bx-chevron-right admin-actions__arrow'></i>
                                        </a>
                                    </li>
                                @endif
                                @if ($hotelBookingsPending > 0)
                                    <li>
                                        <a href="{{ route('admin.hotel-bookings.index') }}">
                                            <span class="admin-actions__icon"><i class='bx bx-hotel'></i></span>
                                            <span class="admin-actions__label">Hotel bookings pending payment</span>
                                            <span class="admin-actions__hint">{{ $hotelBookingsPending }}</span>
                                            <i class='bx bx-chevron-right admin-actions__arrow'></i>
                                        </a>
                                    </li>
                                @endif
                                @if ($flightBookingsPending > 0)
                                    <li>
                                        <a href="{{ route('admin.flight-bookings.index') }}">
                                            <span class="admin-actions__icon"><i class='bx bx-plane'></i></span>
                                            <span class="admin-actions__label">Flight bookings pending payment</span>
                                            <span class="admin-actions__hint">{{ $flightBookingsPending }}</span>
                                            <i class='bx bx-chevron-right admin-actions__arrow'></i>
                                        </a>
                                    </li>
                                @endif
                                @if ($hotelBookingsFailed > 0)
                                    <li>
                                        <a href="{{ route('admin.hotel-bookings.index') }}">
                                            <span class="admin-actions__icon admin-actions__icon--danger"><i class='bx bx-error-circle'></i></span>
                                            <span class="admin-actions__label">Failed hotel payments</span>
                                            <span class="admin-actions__hint">{{ $hotelBookingsFailed }}</span>
                                            <i class='bx bx-chevron-right admin-actions__arrow'></i>
                                        </a>
                                    </li>
                                @endif
                                @if ($flightBookingsFailed > 0)
                                    <li>
                                        <a href="{{ route('admin.flight-bookings.index') }}">
                                            <span class="admin-actions__icon admin-actions__icon--danger"><i class='bx bx-error-circle'></i></span>
                                            <span class="admin-actions__label">Failed flight payments</span>
                                            <span class="admin-actions__hint">{{ $flightBookingsFailed }}</span>
                                            <i class='bx bx-chevron-right admin-actions__arrow'></i>
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <a href="{{ route('admin.hotel-bookings.index') }}">
                                        <span class="admin-actions__icon admin-actions__icon--neutral"><i class='bx bx-hotel'></i></span>
                                        <span class="admin-actions__label">All hotel bookings</span>
                                        <i class='bx bx-chevron-right admin-actions__arrow'></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.flight-bookings.index') }}">
                                        <span class="admin-actions__icon admin-actions__icon--neutral"><i class='bx bx-plane'></i></span>
                                        <span class="admin-actions__label">All flight bookings</span>
                                        <i class='bx bx-chevron-right admin-actions__arrow'></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.vendors.index') }}">
                                        <span class="admin-actions__icon admin-actions__icon--neutral"><i class='bx bx-briefcase'></i></span>
                                        <span class="admin-actions__label">Manage vendors</span>
                                        <i class='bx bx-chevron-right admin-actions__arrow'></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.inquiries.index') }}">
                                        <span class="admin-actions__icon admin-actions__icon--neutral"><i class='bx bx-message-dots'></i></span>
                                        <span class="admin-actions__label">View inquiries</span>
                                        @if ($inquiriesTotal > 0)
                                            <span class="admin-actions__hint">{{ $inquiriesTotal }}</span>
                                        @endif
                                        <i class='bx bx-chevron-right admin-actions__arrow'></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-dash__section">
                <div class="admin-dash__section-head">
                    <h2 class="admin-dash__section-title">Platform</h2>
                </div>
                <div class="row g-3 admin-dash__catalog-row">
                    <div class="col-sm-6 col-xl-3 d-flex">
                        <a href="{{ route('admin.vendors.index') }}" class="admin-tile w-100">
                            <span class="admin-tile__icon"><i class='bx bx-briefcase'></i></span>
                            <span class="admin-tile__body">
                                <span class="admin-tile__label">Active vendors</span>
                                <span class="admin-tile__value">{{ number_format($activeVendorsCount) }}</span>
                            </span>
                            <i class='bx bx-chevron-right admin-tile__arrow'></i>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3 d-flex">
                        <a href="{{ route('admin.vendors.index') }}" class="admin-tile w-100">
                            <span class="admin-tile__icon"><i class='bx bx-group'></i></span>
                            <span class="admin-tile__body">
                                <span class="admin-tile__label">Total vendors</span>
                                <span class="admin-tile__value">{{ number_format($totalVendorsCount) }}</span>
                            </span>
                            <i class='bx bx-chevron-right admin-tile__arrow'></i>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3 d-flex">
                        <a href="{{ route('admin.wallet.bank-transfers.index') }}" class="admin-tile w-100">
                            <span class="admin-tile__icon"><i class='bx bx-bank'></i></span>
                            <span class="admin-tile__body">
                                <span class="admin-tile__label">Pending transfers</span>
                                <span class="admin-tile__value">{{ number_format($walletTransfersPending) }}</span>
                            </span>
                            <i class='bx bx-chevron-right admin-tile__arrow'></i>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3 d-flex">
                        <a href="{{ route('admin.inquiries.index') }}" class="admin-tile w-100">
                            <span class="admin-tile__icon"><i class='bx bx-message-dots'></i></span>
                            <span class="admin-tile__body">
                                <span class="admin-tile__label">Inquiries</span>
                                <span class="admin-tile__value">{{ number_format($inquiriesTotal) }}</span>
                            </span>
                            <i class='bx bx-chevron-right admin-tile__arrow'></i>
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .admin-dash {
            color: #1d1b22;
        }

        .admin-dash__date {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: #6b6573;
            text-transform: none;
            margin: 0 0 0.35rem;
        }

        .admin-dash__title {
            font-size: clamp(1.6rem, 2.4vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #18181b;
            line-height: 1.15;
            margin: 0 0 0.4rem;
        }

        .admin-dash__lead {
            font-size: 0.95rem;
            line-height: 1.55;
            color: #5b5563;
            margin: 0;
            max-width: 48rem;
        }

        .admin-dash__lead-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.6rem;
            padding: 0.05rem 0.45rem;
            font-weight: 700;
            font-size: 0.85rem;
            line-height: 1.4;
            color: var(--color-primary, #cd1b4f);
            background: rgba(205, 27, 79, 0.1);
            border-radius: 999px;
            margin: 0 0.15rem;
        }

        .admin-dash__section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 0.25rem 0 0.85rem;
        }

        .admin-dash__section-title {
            position: relative;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #4b4753;
            margin: 0;
            padding-left: 0.85rem;
        }

        .admin-dash__section-title::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 0.85rem;
            border-radius: 2px;
            background: var(--color-primary, #cd1b4f);
        }

        .admin-stat {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 1.1rem 1.2rem 1.15rem;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #ebecf0;
            box-shadow: 0 1px 2px rgba(20, 20, 30, 0.04);
            text-decoration: none;
            color: inherit;
            overflow: hidden;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        }

        .admin-stat::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 3px;
            background: linear-gradient(180deg, var(--color-primary, #cd1b4f), rgba(205, 27, 79, 0.25));
            opacity: 0;
            transition: opacity 0.18s ease;
        }

        .admin-stat:hover {
            border-color: rgba(205, 27, 79, 0.35);
            box-shadow: 0 8px 24px rgba(205, 27, 79, 0.1);
            color: inherit;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .admin-stat:hover::before {
            opacity: 1;
        }

        .admin-stat__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .admin-stat__icon {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(205, 27, 79, 0.1);
            color: var(--color-primary, #cd1b4f);
            font-size: 1.15rem;
            flex-shrink: 0;
            transition: background 0.18s ease, color 0.18s ease;
        }

        .admin-stat:hover .admin-stat__icon {
            background: var(--color-primary, #cd1b4f);
            color: #fff;
        }

        .admin-stat__label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b6573;
            letter-spacing: 0.01em;
        }

        .admin-stat__value {
            font-size: 1.95rem;
            font-weight: 700;
            line-height: 1.1;
            color: #18181b;
            letter-spacing: -0.02em;
        }

        .admin-stat__meta {
            font-size: 0.82rem;
            line-height: 1.45;
            color: #6b6573;
        }

        .admin-stat__meta-warn {
            color: #b35900;
            font-weight: 600;
        }

        .admin-stat__meta-danger {
            color: #b3261e;
            font-weight: 600;
        }

        .admin-card {
            display: flex;
            flex-direction: column;
            background: #fff;
            border: 1px solid #ebecf0;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(20, 20, 30, 0.04);
            padding: 1.25rem 1.35rem 1.1rem;
        }

        .admin-card__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.85rem;
        }

        .admin-card__title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 1rem;
            font-weight: 700;
            color: #18181b;
            margin: 0 0 0.15rem;
            letter-spacing: -0.01em;
        }

        .admin-card__title::before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--color-primary, #cd1b4f);
            box-shadow: 0 0 0 3px rgba(205, 27, 79, 0.12);
            flex-shrink: 0;
        }

        .admin-card__subtitle {
            font-size: 0.82rem;
            color: #6b6573;
            margin: 0;
            line-height: 1.45;
        }

        .admin-revenue {
            display: flex;
            flex-direction: column;
        }

        .admin-revenue__row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.7rem 0;
            border-top: 1px solid #f0f1f4;
        }

        .admin-revenue__row:first-child {
            border-top: 0;
            padding-top: 0.25rem;
        }

        .admin-revenue__label {
            font-size: 0.85rem;
            color: #6b6573;
            font-weight: 600;
        }

        .admin-revenue__value {
            font-size: 1rem;
            font-weight: 700;
            color: #18181b;
            letter-spacing: -0.01em;
        }

        .admin-revenue__row--hero .admin-revenue__label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b6573;
        }

        .admin-revenue__row--hero .admin-revenue__value {
            font-size: 1.85rem;
            line-height: 1.15;
            font-weight: 700;
            color: var(--color-primary, #cd1b4f);
            letter-spacing: -0.025em;
        }

        .admin-actions {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .admin-actions li + li a {
            border-top: 1px solid #f3f4f7;
        }

        .admin-actions a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 0.5rem;
            color: #1d1b22;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .admin-actions a:hover {
            background: #f7f7fa;
            color: #18181b;
            text-decoration: none;
        }

        .admin-actions__icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(205, 27, 79, 0.08);
            color: var(--color-primary, #cd1b4f);
            font-size: 1rem;
            flex-shrink: 0;
        }

        .admin-actions__icon--neutral {
            background: #f1f2f5;
            color: #4b4753;
        }

        .admin-actions__icon--danger {
            background: rgba(179, 38, 30, 0.08);
            color: #b3261e;
        }

        .admin-actions__label {
            flex: 1 1 auto;
            font-size: 0.92rem;
            font-weight: 500;
            min-width: 0;
        }

        .admin-actions__hint {
            font-size: 0.78rem;
            font-weight: 700;
            color: #6b6573;
            background: #f1f2f5;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .admin-actions__arrow {
            color: #b8b3bf;
            font-size: 1.1rem;
            flex-shrink: 0;
            transition: transform 0.15s ease, color 0.15s ease;
        }

        .admin-actions a:hover .admin-actions__arrow {
            color: var(--color-primary, #cd1b4f);
            transform: translateX(2px);
        }

        .admin-tile {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.95rem 1.1rem;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #ebecf0;
            box-shadow: 0 1px 2px rgba(20, 20, 30, 0.04);
            text-decoration: none;
            color: inherit;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        }

        .admin-tile:hover {
            border-color: rgba(205, 27, 79, 0.35);
            box-shadow: 0 8px 24px rgba(205, 27, 79, 0.1);
            color: inherit;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .admin-tile__icon {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(205, 27, 79, 0.08);
            color: var(--color-primary, #cd1b4f);
            font-size: 1.2rem;
            flex-shrink: 0;
            transition: background 0.18s ease, color 0.18s ease;
        }

        .admin-tile:hover .admin-tile__icon {
            background: var(--color-primary, #cd1b4f);
            color: #fff;
        }

        .admin-tile__body {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1 1 auto;
        }

        .admin-tile__label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b6573;
            letter-spacing: 0.01em;
        }

        .admin-tile__value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #18181b;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .admin-tile__arrow {
            color: #b8b3bf;
            font-size: 1.1rem;
            flex-shrink: 0;
            transition: color 0.15s ease, transform 0.15s ease;
        }

        .admin-tile:hover .admin-tile__arrow {
            color: var(--color-primary, #cd1b4f);
            transform: translateX(2px);
        }

        .admin-revenue__value .dirham,
        .admin-stat__value .dirham {
            margin-right: 0.15em;
        }
    </style>
@endpush
