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
                                $leg = $result['legs'][0];
                                $firstSeg = $leg['segments'][0];
                                $lastSeg = end($leg['segments']);
                                $stopCount = (int) ($firstSeg['stop_count'] ?? 0);

                                // Clean Time Formatting (Removes +04:00 and Seconds)
                                $depTime = \Carbon\Carbon::parse($firstSeg['departure_time'])->format('H:i');
                                $arrTime = \Carbon\Carbon::parse($lastSeg['arrival_time'])->format('H:i');
                            @endphp

                            <div class="flight-card">
                                <!-- Journey Details -->
                                <div class="fc-details">
                                    <div class="fc-airline">
                                        <img src="https://logo.clearbit.com/{{ strtolower($firstSeg['carrier']) }}.com"
                                            onerror="this.src='https://ui-avatars.com/api/?name={{ $firstSeg['carrier'] }}&background=cd1b4f&color=fff'"
                                            alt="Airline">
                                        <div class="fc-airline-text">
                                            <b>{{ $firstSeg['carrier_name'] ?? $firstSeg['carrier'] }}</b>
                                            <span>FLIGHT {{ $firstSeg['carrier'] }}{{ $firstSeg['flight_number'] }}</span>
                                        </div>
                                    </div>
                                    <div class="fc-route">
                                        <div class="fc-point">
                                            <span class="time">{{ $depTime }}</span>
                                            <span class="iata">{{ $firstSeg['from'] }}</span>
                                        </div>

                                        <div class="fc-path">
                                            <span class="fc-duration">{{ $leg['elapsedTime'] }}m</span>
                                            <div class="fc-line"></div>
                                            <span class="fc-stops">
                                                {{ $stopCount === 0 ? 'Non-Stop' : $stopCount . ' Stop' . ($stopCount > 1 ? 's' : '') }}
                                            </span>
                                        </div>

                                        <div class="fc-point text-end">
                                            <span class="time">{{ $arrTime }}</span>
                                            <span class="iata">{{ $lastSeg['to'] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price & Action -->
                                <div class="fc-action">
                                    <span class="fc-price-label">Price Per Adult</span>
                                    <div class="fc-price">
                                        <span class="curr"><span class="dirham">D</span></span>
                                        <span class="amt">{{ number_format($result['totalPrice'], 0) }}</span>
                                    </div>
                                    <a href="javascript:void(0)" class="fc-btn">Select Flight</a>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        :root {
            --brand-pink: #cd1b4f;
            --brand-pink-dark: #c0073e;
            --brand-pink-soft: rgba(205, 27, 79, 0.05);
            --text-main: #1e293b;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --border-light: #f1f5f9;
        }

        /* Base Card Container */
        .flight-card {
            background: var(--card-bg);
            border-radius: 24px;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            margin-bottom: 24px;
            display: flex;
            flex-direction: row;
            /* Desktop default */
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .flight-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(205, 27, 79, 0.1);
            border-color: rgba(205, 27, 79, 0.2);
        }

        /* Left Section: Info & Route */
        .fc-details {
            flex: 3;
            padding: 30px;
        }

        /* Airline Branding */
        .fc-airline {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
        }

        .fc-airline img {
            width: 42px;
            height: 42px;
            object-fit: contain;
            background: #f8fafc;
            border-radius: 10px;
            padding: 6px;
        }

        .fc-airline-text b {
            color: var(--text-main);
            font-size: 1rem;
        }

        .fc-airline-text span {
            color: var(--text-muted);
            font-size: 0.75rem;
            display: block;
            letter-spacing: 0.5px;
        }

        /* Timeline/Route */
        .fc-route {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .fc-point .time {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-main);
            display: block;
            line-height: 1;
        }

        .fc-point .iata {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 5px;
            display: block;
        }

        /* Path Visualization */
        .fc-path {
            flex: 1;
            text-align: center;
            position: relative;
            padding: 0 10px;
        }

        .fc-duration {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }

        .fc-line {
            height: 2px;
            background: #e2e8f0;
            width: 100%;
            position: relative;
        }

        .fc-line::before,
        .fc-line::after {
            content: '';
            position: absolute;
            top: -3px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .fc-line::before {
            left: 0;
        }

        .fc-line::after {
            right: 0;
        }

        .fc-stops {
            display: inline-block;
            margin-top: 10px;
            background: var(--brand-pink-soft);
            color: var(--brand-pink);
            font-size: 0.7rem;
            font-weight: 800;
            padding: 3px 12px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        /* Right Section: Price & CTA */
        .fc-action {
            flex: 1.2;
            background: #fcfcfd;
            border-left: 1px solid var(--border-light);
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .fc-price-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .fc-price {
            color: var(--brand-pink);
            margin: 8px 0 20px;
        }

        .fc-price .curr {
            font-size: 2rem;
            font-weight: 700;
            margin-right: 2px;
        }

        .fc-price .amt {
            font-size: 2.4rem;
            font-weight: 900;
            letter-spacing: -1.5px;
        }

        .fc-btn {
            background: var(--brand-pink);
            color: white !important;
            width: 100%;
            padding: 14px;
            border-radius: 15px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 8px 20px -5px rgba(205, 27, 79, 0.4);
            transition: 0.3s;
        }

        .fc-btn:hover {
            background: var(--brand-pink-dark);
            transform: scale(1.02);
        }

        /* RESPONSIVE BREAKPOINT */
        @media (max-width: 991px) {
            .flight-card {
                flex-direction: column;
            }

            .fc-action {
                border-left: none;
                border-top: 1px solid var(--border-light);
                width: 100%;
                padding: 25px;
                background: white;
            }

            .fc-details {
                padding: 25px;
            }

            .fc-point .time {
                font-size: 1.5rem;
            }
        }
    </style>
@endpush
