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
                {{-- FLIGHT CARDS --}}
                <div class="col-lg-12">
                    @if (!empty($results))
                        @foreach ($results as $result)
                            @php
                                $firstLeg = $result['legs'][0] ?? null;
                                $firstSeg = $firstLeg['segments'][0] ?? null;
                                $lastSeg = null;
                                if (!empty($firstLeg['segments'])) {
                                    $lastSeg = $firstLeg['segments'][count($firstLeg['segments']) - 1];
                                }
                            @endphp
                            <div class="hl-card hl-card--flight">
                                <div class="hl-card__body">
                                    <!-- Airline & Basic Info -->
                                    <div class="fl-airline-col">
                                        <img src="https://pics.avs.io/100/40/{{ $firstSeg['carrier'] }}.png"
                                            alt="{{ $firstSeg['carrier'] }}" class="fl-logo">
                                        <div class="fl-name">{{ $firstSeg['carrier'] }}</div>
                                    </div>

                                    <!-- Timeline -->
                                    <div class="fl-timeline-col">
                                        <div class="fl-time-group">
                                            <div class="fl-time">{{ $firstSeg['departure_time'] }}</div>
                                            <div class="fl-airport">{{ $firstSeg['from'] }}</div>
                                        </div>

                                        <div class="fl-middle">
                                            <span class="fl-duration">{{ $firstLeg['elapsedTime'] ?? '-' }} min</span>
                                            <div class="fl-line"></div>
                                            <span
                                                class="fl-stops">{{ (int) ($firstLeg['segments'][0]['stop_count'] ?? 0) }}
                                                stop</span>
                                        </div>

                                        <div class="fl-time-group">
                                            <div class="fl-time">{{ $lastSeg['arrival_time'] }}</div>
                                            <div class="fl-airport">{{ $lastSeg['to'] }}</div>
                                        </div>
                                    </div>

                                    <!-- Price & Action -->
                                    <div class="hl-card__price-col">
                                        <div class="hl-price">
                                            <small>{{ strtoupper((string) ($result['currency'] ?? '')) }}</small>
                                            <span>{{ $result['totalPrice'] ?? '-' }}</span>
                                        </div>
                                        <a href="javascript:void(0)" class="btn-book">Book Flight</a>
                                        <span class="fl-note">Includes taxes & fees</span>
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
      .hl-card--flight {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    border: 1px solid #edf2f7;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.hl-card--flight:hover {
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.hl-card__body {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.fl-airline-col { text-align: center; width: 120px;}
.fl-logo { max-width: 60px; margin-bottom: 8px; }

.fl-timeline-col {
    display: flex;
    align-items: center;
    gap: 40px;
    flex: 1;
    justify-content: center;
}

.fl-time-group { text-align: center; }
.fl-time { font-size: 1.5rem; font-weight: 700; color: #1a202c;}
.fl-airport { font-size: 0.875rem; color: #718096;}

.fl-middle { display: flex; flex-direction: column; align-items: center; gap: 5px;}
.fl-line { width: 120px; height: 2px; background: #cbd5e0; position: relative;}
.fl-line::after { content: '✈'; position: absolute; top: -10px; left: 50%; transform: translateX(-50%); color: #a0aec0;}
.fl-duration { font-size: 0.8rem; color: #718096;}
.fl-stops { font-size: 0.75rem; color: #e53e3e; font-weight: 600;}

.hl-card__price-col { text-align: right; border-left: 1px solid #edf2f7; padding-left: 24px;}
.hl-price span { font-size: 1.8rem; font-weight: 800; color: #2b6cb0; display: block;}
.hl-price small { color: #a0aec0; font-weight: 600; }
.btn-book { background: #2b6cb0; color: #fff; padding: 12px 32px; border-radius: 8px; font-weight: 600; text-decoration: none; display: block; margin-top: 10px; transition: 0.2s;}
.btn-book:hover { background: #2c5282;}
.fl-note { font-size: 0.75rem; color: #a0aec0; margin-top: 5px; display: block;}
    </style>
@endpush
