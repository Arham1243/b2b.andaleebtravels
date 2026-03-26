@extends('user.layouts.main')
@section('content')
    @php
        $query = request()->query();
    @endphp

    <div class="hl-page">
        <div class="container">
            {{-- SEARCH BAR --}}
            <div class="hl-search-bar mb-5">
                @include('user.vue.main', [
                    'appId' => 'flights-search',
                    'appComponent' => 'flights-search',
                    'appJs' => 'flights-search',
                ])
            </div>

            {{-- RESULTS HEADER --}}
            <div class="hl-results-header">
                <div class="hl-results-header__left">
                    <span class="hl-results-count">{{ $itineraryCount ?? 0 }} results found</span>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    @if (!empty($results))
                        @foreach ($results as $result)
                            @php
                                $firstLeg = $result['legs'][0] ?? null;
                                $segments = $firstLeg['segments'] ?? [];
                                $firstSeg = $segments[0] ?? null;
                                $lastSeg = end($segments);
                                $stopCount = (int) ($firstSeg['stop_count'] ?? 0);
                                $currency = strtoupper((string) ($result['currency'] ?? 'AED'));
                            @endphp

                            <div class="flight-card">
                                <div class="flight-card__main">

                                    <!-- 1. Airline Logo & Info -->
                                    <div class="airline-wrapper">
                                        <div class="airline-logo-box">
                                            <img src="https://img.logo.dev/{{ $firstSeg['carrier'] }}.png?token=YOUR_TOKEN"
                                                onerror="this.src='https://ui-avatars.com/api/?name={{ $firstSeg['carrier'] }}&background=cd1b4f&color=fff'"
                                                alt="Airline">
                                        </div>
                                        <div class="airline-details">
                                            <span
                                                class="airline-name">{{ $firstSeg['carrier_name'] ?? $firstSeg['carrier'] }}</span>
                                            <div style="font-size: 0.7rem; color: #94a3b8;">
                                                {{ $firstSeg['carrier'] }}{{ $firstSeg['flight_number'] }}</div>
                                        </div>
                                    </div>

                                    <!-- 2. The Route Path -->
                                    <div class="route-wrapper">
                                        <div class="route-point">
                                            <span class="time">{{ $firstSeg['departure_time'] }}</span>
                                            <span class="airport-code">{{ $firstSeg['from'] }}</span>
                                        </div>

                                        <div class="route-path">
                                            <span class="duration-text">{{ $firstLeg['elapsedTime'] ?? '-' }} min</span>
                                            <div class="path-line">
                                                <i class="bx bxs-plane-alt plane-icon"></i>
                                            </div>
                                            <span class="stops-badge">
                                                {{ $stopCount === 0 ? 'Non-stop' : $stopCount . ' Stop' . ($stopCount > 1 ? 's' : '') }}
                                            </span>
                                        </div>

                                        <div class="route-point">
                                            <span class="time">{{ $lastSeg['arrival_time'] ?? '-' }}</span>
                                            <span class="airport-code">{{ $lastSeg['to'] ?? '-' }}</span>
                                        </div>
                                    </div>

                                    <!-- 3. Action Section -->
                                    <div class="action-wrapper">
                                        <span class="price-label">Total Price</span>
                                        <div class="price-value">
                                            <small style="font-size: 1rem; font-weight: 600;">{{ $currency }}</small>
                                            {{ number_format($result['totalPrice'], 0) }}
                                        </div>
                                        <a href="javascript:void(0)" class="select-btn">Select Flight</a>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    @else
                        <!-- Keep your existing Empty State here -->
                        <div class="text-center py-5">
                            <i class="bx bx-search-alt"
                                style="font-size: 4rem; color: var(--color-primary); opacity: 0.3;"></i>
                            <h3 class="mt-3">No flights found</h3>
                            <p class="text-muted">Try adjusting your search filters.</p>
                            <a href="{{ route('user.flights.index') }}"
                                class="btn btn-outline-secondary rounded-pill px-4">Search Again</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        :root {
            --color-primary: #cd1b4f;
            --color-primary-dark: #c0073e;
            --text-dark: #1a1f2b;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
        }

        .flight-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }

        .flight-card:hover {
            box-shadow: 0 10px 20px rgba(205, 27, 79, 0.1);
            border-color: var(--color-primary);
        }

        .flight-card__main {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            padding: 24px;
            align-items: center;
        }

        /* 1. Airline Section */
        .airline-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .airline-logo-box {
            width: 50px;
            height: 50px;
            background: var(--bg-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
        }

        .airline-logo-box img {
            max-width: 100%;
            filter: grayscale(0.2);
        }

        .airline-name {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        /* 2. Route Timeline Section */
        .route-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: relative;
        }

        .route-point {
            text-align: center;
            min-width: 80px;
        }

        .time {
            display: block;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }

        .airport-code {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .route-path {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            padding: 0 15px;
        }

        .duration-text {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .path-line {
            width: 100%;
            height: 2px;
            background: #e2e8f0;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .path-line::before,
        .path-line::after {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .path-line::before {
            left: 0;
        }

        .path-line::after {
            right: 0;
        }

        .plane-icon {
            background: #fff;
            padding: 0 8px;
            color: var(--color-primary);
            font-size: 1.2rem;
            z-index: 1;
        }

        .stops-badge {
            margin-top: 8px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--color-primary);
            background: rgba(205, 27, 79, 0.08);
            padding: 2px 10px;
            border-radius: 20px;
        }

        /* 3. Price & Action Section */
        .action-wrapper {
            text-align: right;
            border-left: 1px solid var(--border-color);
            padding-left: 30px;
        }

        .price-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: block;
        }

        .price-value {
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--color-primary);
            margin: 4px 0;
        }

        .select-btn {
            background: var(--color-primary);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 700;
            width: 100%;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .select-btn:hover {
            background: var(--color-primary-dark);
            color: #fff;
            box-shadow: 0 4px 12px rgba(205, 27, 79, 0.3);
        }

        /* Responsive fixes */
        @media (max-width: 992px) {
            .flight-card__main {
                grid-template-columns: 1fr;
                gap: 20px;
                text-align: center;
            }

            .action-wrapper {
                border-left: none;
                border-top: 1px solid var(--border-color);
                padding: 20px 0 0 0;
                text-align: center;
            }

            .route-wrapper {
                padding: 0;
            }
        }
    </style>
@endpush
