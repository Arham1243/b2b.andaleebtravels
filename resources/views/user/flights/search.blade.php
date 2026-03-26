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
                            $leg = $result['legs'][0];
                            $first = $leg['segments'][0];
                            $last = end($leg['segments']);
                            $stopCount = (int) ($first['stop_count'] ?? 0);

                            // Formatter to remove the +04:00 and seconds
                            $depTime = date('H:i', strtotime($first['departure_time']));
                            $arrTime = date('H:i', strtotime($last['arrival_time']));
                        @endphp

                        <div class="flight-card">
                            <div class="fc-main">
                                <!-- Airline Info -->
                                <div class="fc-airline">
                                    <div class="fc-logo">
                                        <img src="https://img.logo.dev/{{ $first['carrier'] }}.png?token=YOUR_TOKEN"
                                            onerror="this.src='https://ui-avatars.com/api/?name={{ $first['carrier'] }}&background=cd1b4f&color=fff'"
                                            alt="Airline">
                                    </div>
                                    <div class="fc-airline-info">
                                        <b>{{ $first['carrier_name'] ?? $first['carrier'] }}</b>
                                        <span>Flight {{ $first['carrier'] }}{{ $first['flight_number'] }}</span>
                                    </div>
                                </div>

                                <!-- Route Info -->
                                <div class="fc-route">
                                    <div class="fc-point">
                                        <span class="time">{{ $depTime }}</span>
                                        <span class="iata">{{ $first['from'] }}</span>
                                    </div>

                                    <div class="fc-path">
                                        <span class="fc-duration">{{ $leg['elapsedTime'] }}m</span>
                                        <div class="fc-line">
                                            <div class="fc-icon">
                                                <i class="bx bxs-plane-takeoff"></i>
                                            </div>
                                        </div>
                                        <span class="fc-stop-badge">
                                            {{ $stopCount === 0 ? 'Non-Stop' : $stopCount . ' Stop' . ($stopCount > 1 ? 's' : '') }}
                                        </span>
                                    </div>

                                    <div class="fc-point text-end">
                                        <span class="time">{{ $arrTime }}</span>
                                        <span class="iata">{{ $last['to'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Price Section -->
                            <div class="fc-side">
                                <span class="fc-price-label">Price per adult</span>
                                <div class="fc-price">
                                    <small><span class="dirham">D</span></small>
                                    <b>{{ number_format($result['totalPrice'], 0) }}</b>
                                </div>
                                <a href="#" class="fc-btn">Select Flight</a>
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
    --brand-pink-soft: rgba(205, 27, 79, 0.04);
    --text-deep: #1e293b;
    --text-gray: #64748b;
}

.flight-card {
    background: #fff;
    border-radius: 24px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
    margin-bottom: 24px;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    display: flex;
    overflow: hidden;
}

.flight-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px -10px rgba(205, 27, 79, 0.15);
    border-color: rgba(205, 27, 79, 0.2);
}

/* Left Section: Flight Info */
.fc-main {
    flex: 1;
    padding: 32px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.fc-airline {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}

.fc-logo {
    width: 44px;
    height: 44px;
    background: #f8fafc;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6px;
    border: 1px solid #f1f5f9;
}

.fc-airline-info b {
    color: var(--text-deep);
    font-size: 1rem;
    display: block;
}

.fc-airline-info span {
    color: var(--text-gray);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* The Route Timeline */
.fc-route {
    display: grid;
    grid-template-columns: 100px 1fr 100px;
    align-items: center;
    gap: 15px;
}

.fc-point .time {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--text-deep);
    letter-spacing: -1px;
    display: block;
}

.fc-point .iata {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-gray);
    margin-top: 4px;
    display: block;
}

.fc-path {
    text-align: center;
    padding-bottom: 10px;
}

.fc-duration {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-gray);
    margin-bottom: 8px;
    display: block;
}

.fc-line {
    height: 2px;
    background: #e2e8f0;
    width: 100%;
    position: relative;
    border-radius: 2px;
}

.fc-line::before, .fc-line::after {
    content: '';
    position: absolute;
    top: -3px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cbd5e1;
}
.fc-line::before { left: 0; }
.fc-line::after { right: 0; }

.fc-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 0 10px;
    color: var(--brand-pink);
    font-size: 1.2rem;
}

.fc-stop-badge {
    display: inline-block;
    margin-top: 12px;
    background: var(--brand-pink-soft);
    color: var(--brand-pink);
    font-size: 0.65rem;
    font-weight: 800;
    padding: 4px 14px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Right Section: Price */
.fc-side {
    width: 260px;
    background: linear-gradient(145deg, #ffffff 0%, #fdf2f5 100%);
    border-left: 1px solid #f1f5f9;
    padding: 32px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.fc-price-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-gray);
    text-transform: uppercase;
    margin-bottom: 5px;
}

.fc-price {
    color: var(--brand-pink);
    margin-bottom: 20px;
}

.fc-price small {
    font-size: 1rem;
    font-weight: 700;
}

.fc-price b {
    font-size: 2.4rem;
    font-weight: 900;
    letter-spacing: -1.5px;
}

.fc-btn {
    background: var(--brand-pink);
    color: white;
    width: 100%;
    padding: 14px;
    border-radius: 14px;
    font-weight: 700;
    text-decoration: none;
    box-shadow: 0 8px 20px -6px rgba(205, 27, 79, 0.4);
    transition: all 0.3s;
}

.fc-btn:hover {
    background: var(--brand-pink-dark);
    transform: translateY(-2px);
    box-shadow: 0 12px 25px -6px rgba(205, 27, 79, 0.5);
    color: white;
}

.fc-details {
    margin-top: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-gray);
    text-decoration: none;
}

@media (max-width: 768px) {
    .flight-card { flex-direction: column; }
    .fc-side { width: 100%; border-left: none; border-top: 1px solid #f1f5f9; }
}
    </style>
@endpush
