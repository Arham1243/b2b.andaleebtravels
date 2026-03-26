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

            <div class="flight-results-container">
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

                        <div class="fl-card">
                            <div class="fl-card__body">

                                <!-- Journey Details -->
                                <div class="fl-info-section">
                                    <!-- Airline Header -->
                                    <div class="fl-airline-row">
                                        <div class="fl-airline-logo">
                                            <img src="https://img.logo.dev/{{ $firstSeg['carrier'] }}.png?token=YOUR_API_KEY"
                                                onerror="this.src='https://ui-avatars.com/api/?name={{ $firstSeg['carrier'] }}&background=cd1b4f&color=fff'"
                                                alt="Airline" width="30">
                                        </div>
                                        <span class="fl-airline-name">
                                            {{ $firstSeg['carrier_name'] ?? $firstSeg['carrier'] }}
                                            <span style="font-weight:400; color:#94a3b8; margin-left:5px;">•
                                                {{ $firstSeg['carrier'] }}{{ $firstSeg['flight_number'] }}</span>
                                        </span>
                                    </div>

                                    <!-- Route Display -->
                                    <div class="fl-route-display">
                                        <div class="fl-node">
                                            <span class="time">{{ $firstSeg['departure_time'] }}</span>
                                            <span class="code">{{ $firstSeg['from'] }}</span>
                                        </div>

                                        <div class="fl-path">
                                            <span class="fl-duration">{{ $firstLeg['elapsedTime'] ?? '-' }}m</span>
                                            <div class="fl-line-art">
                                                <i class="bx bxs-plane-takeoff fl-plane-icon"></i>
                                            </div>
                                            <span class="fl-stops">
                                                {{ $stopCount === 0 ? 'Non-stop' : $stopCount . ' Stop' . ($stopCount > 1 ? 's' : '') }}
                                            </span>
                                        </div>

                                        <div class="fl-node text-end">
                                            <span class="time">{{ $lastSeg['arrival_time'] ?? '-' }}</span>
                                            <span class="code">{{ $lastSeg['to'] ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price & Booking (Pink Section) -->
                                <div class="fl-price-section">
                                    <span class="fl-price-label">Price per adult</span>
                                    <div class="fl-price-amount">
                                        <span class="fl-currency">{{ $currency }}</span>
                                        <span class="fl-amount">{{ number_format($result['totalPrice'], 0) }}</span>
                                    </div>

                                    <a href="javascript:void(0)" class="fl-select-btn">Select Flight</a>

                                    <a href="#" class="fl-details-link">
                                        View details <i class="bx bx-chevron-down"></i>
                                    </a>
                                </div>

                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Professional Empty State -->
                    <div class="text-center py-5">
                        <div
                            style="background: var(--brand-pink-light); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                            <i class="bx bx-map-alt" style="font-size: 2.5rem; color: var(--brand-pink);"></i>
                        </div>
                        <h3 style="font-weight: 800; color: var(--text-main);">No Flights Available</h3>
                        <p style="color: var(--text-light); max-width: 400px; margin: 10px auto 25px;">We couldn't find any
                            flights for this route. Try changing your dates or search for nearby airports.</p>
                        <a href="{{ route('user.flights.index') }}" class="fl-select-btn d-inline-block"
                            style="width: auto; padding-left: 40px; padding-right: 40px;">Modify Search</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        :root {
            --brand-pink: #cd1b4f;
            --brand-pink-dark: #c0073e;
            --brand-pink-light: rgba(205, 27, 79, 0.05);
            --text-main: #2d3748;
            --text-light: #718096;
            --border-color: #edf2f7;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        }

        .flight-results-container {
            font-family: 'Inter', sans-serif;
            /* Modern clean font */
        }

        .fl-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .fl-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08);
            border-color: var(--brand-pink);
        }

        .fl-card__body {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            padding: 0;
        }

        /* --- Left Side: Flight Info --- */
        .fl-info-section {
            flex: 1;
            padding: 2rem;
            min-width: 300px;
        }

        .fl-airline-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .fl-airline-logo {
            width: 40px;
            height: 40px;
            background: #f8fafc;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
        }

        .fl-airline-name {
            font-weight: 700;
            color: var(--text-main);
            font-size: 0.95rem;
            letter-spacing: -0.2px;
        }

        /* --- The Route Timeline --- */
        .fl-route-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .fl-node .time {
            display: block;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1;
        }

        .fl-node .code {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-light);
            margin-top: 5px;
            display: block;
        }

        .fl-path {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .fl-duration {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 8px;
            display: block;
        }

        .fl-line-art {
            height: 2px;
            background: #e2e8f0;
            width: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fl-line-art::before,
        .fl-line-art::after {
            content: '';
            width: 8px;
            height: 8px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 50%;
            position: absolute;
        }

        .fl-line-art::before {
            left: -4px;
        }

        .fl-line-art::after {
            right: -4px;
        }

        .fl-plane-icon {
            background: white;
            color: var(--brand-pink);
            padding: 0 10px;
            font-size: 1.2rem;
            z-index: 2;
        }

        .fl-stops {
            margin-top: 8px;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--brand-pink);
            text-transform: uppercase;
            background: var(--brand-pink-light);
            padding: 2px 12px;
            border-radius: 50px;
            display: inline-block;
        }

        /* --- Right Side: Price Section --- */
        .fl-price-section {
            width: 240px;
            background: #fcfcfd;
            border-left: 1px solid var(--border-color);
            padding: 2rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .fl-price-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .fl-price-amount {
            margin: 8px 0 20px;
        }

        .fl-currency {
            font-size: 1rem;
            font-weight: 600;
            color: var(--brand-pink);
            vertical-align: top;
        }

        .fl-amount {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--brand-pink);
            letter-spacing: -1px;
        }

        .fl-select-btn {
            background: var(--brand-pink);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.2s;
            display: block;
            box-shadow: 0 4px 12px rgba(205, 27, 79, 0.2);
        }

        .fl-select-btn:hover {
            background: var(--brand-pink-dark);
            transform: scale(1.02);
            color: white;
        }

        /* Details toggle link */
        .fl-details-link {
            margin-top: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-light);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        /* --- Mobile Responsive --- */
        @media (max-width: 768px) {
            .fl-price-section {
                width: 100%;
                border-left: none;
                border-top: 1px solid var(--border-color);
                padding: 1.5rem;
            }

            .fl-info-section {
                padding: 1.5rem;
            }
        }
    </style>
@endpush
