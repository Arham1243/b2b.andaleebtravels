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
                                $firstSeg = $firstLeg['segments'][0] ?? null;
                                $lastSeg = end($firstLeg['segments']);
                                $stopCount = (int) ($firstSeg['stop_count'] ?? 0);
                                $currency = strtoupper((string) ($result['currency'] ?? 'AED'));
                            @endphp

                            <div class="flight-card">
                                <div class="flight-card__main">
                                    <!-- Airline Logo & Name -->
                                    <div class="flight-info__airline">
                                        <div class="airline-logo">
                                            <img src="https://img.logo.dev/{{ $firstSeg['carrier'] }}.png?token=YOUR_TOKEN"
                                                onerror="this.src='https://ui-avatars.com/api/?name={{ $firstSeg['carrier'] }}&background=random'"
                                                alt="Carrier">
                                        </div>
                                        <span
                                            class="airline-name">{{ $firstSeg['carrier_name'] ?? $firstSeg['carrier'] }}</span>
                                    </div>

                                    <!-- Route Timeline -->
                                    <div class="flight-info__path">
                                        <div class="path-point">
                                            <span class="path-time">{{ $firstSeg['departure_time'] }}</span>
                                            <span class="path-code">{{ $firstSeg['from'] }}</span>
                                        </div>

                                        <div class="path-line">
                                            <span class="path-duration">{{ $firstLeg['elapsedTime'] ?? '-' }}m</span>
                                            <div class="line-visual">
                                                <div class="dot"></div>
                                                <div class="connector"></div>
                                                <div class="plane-icon"><i class="bx bxs-plane-alt"></i></div>
                                                <div class="connector"></div>
                                                <div class="dot"></div>
                                            </div>
                                            <span class="path-stops {{ $stopCount > 0 ? 'has-stops' : 'non-stop' }}">
                                                {{ $stopCount === 0 ? 'Non-stop' : $stopCount . ' Stop' . ($stopCount > 1 ? 's' : '') }}
                                            </span>
                                        </div>

                                        <div class="path-point">
                                            <span class="path-time">{{ $lastSeg['arrival_time'] ?? '-' }}</span>
                                            <span class="path-code">{{ $lastSeg['to'] ?? '-' }}</span>
                                        </div>
                                    </div>

                                    <!-- Price & Booking -->
                                    <div class="flight-info__action">
                                        <div class="price-tag">
                                            <span class="currency">{{ $currency }}</span>
                                            <span class="amount">{{ number_format($result['totalPrice'], 2) }}</span>
                                        </div>
                                        <button class="book-btn">Select Flight</button>
                                        <a href="#details-{{ $loop->index }}" class="details-toggle"
                                            data-bs-toggle="collapse">
                                            View Details <i class="bx bx-chevron-down"></i>
                                        </a>
                                    </div>
                                </div>

                                <!-- Collapsible Segment Details -->
                                <div class="collapse flight-card__details" id="details-{{ $loop->index }}">
                                    <div class="details-inner">
                                        @foreach ($result['legs'] as $leg)
                                            @foreach ($leg['segments'] as $seg)
                                                <div class="segment-row">
                                                    <div class="seg-time-col">
                                                        <strong>{{ $seg['departure_time'] }}</strong>
                                                        <span>{{ $seg['from'] }}</span>
                                                    </div>
                                                    <div class="seg-divider">
                                                        <i class="bx bx-down-arrow-alt"></i>
                                                        <span
                                                            class="flight-no">{{ $seg['carrier'] }}{{ $seg['flight_number'] }}</span>
                                                    </div>
                                                    <div class="seg-time-col text-end">
                                                        <strong>{{ $seg['arrival_time'] }}</strong>
                                                        <span>{{ $seg['to'] }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="hl-empty">
                            <i class="bx bx-search-alt"></i>
                            <h3>No flights found</h3>
                            <p>We couldn't find any results for your selected criteria. Try changing dates or airports.</p>
                            <div class="hl-empty__actions">
                                <a href="{{ route('user.flights.index') }}" class="themeBtn">Search again</a>
                                <a href="{{ route('user.flights.search', $query) }}" class="themeBtn">Reset search</a>
                            </div>
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
            --primary: #2563eb;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        .flight-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .flight-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .flight-card__main {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            gap: 2rem;
        }

        /* Airline Section */
        .flight-info__airline {
            flex: 0 0 140px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .airline-logo img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            margin-bottom: 0.5rem;
        }

        .airline-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
        }

        /* Path Visualization */
        .flight-info__path {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .path-point {
            display: flex;
            flex-direction: column;
        }

        .path-time {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .path-code {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .path-line {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            padding: 0 1rem;
        }

        .line-visual {
            width: 100%;
            display: flex;
            align-items: center;
            margin: 0.5rem 0;
        }

        .connector {
            height: 2px;
            flex: 1;
            background: #cbd5e1;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .plane-icon {
            padding: 0 10px;
            color: var(--primary);
            font-size: 1.2rem;
        }

        .path-duration {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .path-stops {
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 4px;
        }

        .path-stops.non-stop {
            color: #10b981;
        }

        .path-stops.has-stops {
            color: #f59e0b;
        }

        /* Action Section */
        .flight-info__action {
            flex: 0 0 180px;
            border-left: 1px solid var(--border);
            padding-left: 2rem;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .price-tag .currency {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .price-tag .amount {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }

        .book-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            margin: 1rem 0 0.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .book-btn:hover {
            background: #1d4ed8;
        }

        .details-toggle {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-decoration: none;
        }

        /* Collapsible Area */
        .flight-card__details {
            background: #f8fafc;
            border-top: 1px solid var(--border);
        }

        .details-inner {
            padding: 1.5rem;
        }

        .segment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 500px;
            margin: 0 auto 1rem;
        }

        .seg-divider {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .flight-card__main {
                flex-direction: column;
                gap: 1.5rem;
            }

            .flight-info__action {
                border-left: none;
                padding-left: 0;
                border-top: 1px solid var(--border);
                padding-top: 1.5rem;
                width: 100%;
                align-items: center;
            }
        }
    </style>
@endpush
