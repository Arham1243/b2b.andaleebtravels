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
                    <span class="fl-results-note">Filters coming soon</span>
                </div>
                <div class="hl-results-header__right">
                    <label class="hl-sort-label">Sort by:</label>
                    <select class="hl-sort-select" disabled>
                        <option>Recommended</option>
                        <option>Price Low to High</option>
                        <option>Price High to Low</option>
                        <option>Fastest</option>
                    </select>
                </div>
            </div>

            <div class="row">
                {{-- FILTERS SIDEBAR --}}
                <div class="col-lg-3">
                    <div class="hl-sidebar">
                        <div class="hl-sidebar__title">Filters</div>

                        <div class="hl-filter-group collapsed">
                            <div class="hl-filter-group__header">
                                <span>Stops</span>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="hl-filter-group__body">
                                <div class="fl-placeholder">Coming soon</div>
                            </div>
                        </div>

                        <div class="hl-filter-group collapsed">
                            <div class="hl-filter-group__header">
                                <span>Airlines</span>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="hl-filter-group__body">
                                <div class="fl-placeholder">Coming soon</div>
                            </div>
                        </div>

                        <div class="hl-filter-group collapsed">
                            <div class="hl-filter-group__header">
                                <span>Departure Time</span>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="hl-filter-group__body">
                                <div class="fl-placeholder">Coming soon</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FLIGHT CARDS --}}
                <div class="col-lg-9">
                    @if (!empty($messages))
                        <div class="mb-3">
                            @foreach ($messages as $msg)
                                @php
                                    $isError = strtolower($msg['severity'] ?? '') === 'error';
                                @endphp
                                <div class="hc-alert {{ $isError ? 'hc-alert--warning' : '' }}">
                                    <i class='bx {{ $isError ? 'bx-error-circle' : 'bx-info-circle' }}'></i>
                                    <span>{{ $msg['text'] ?? 'Notice' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

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
                                <div class="hl-card__img fl-card__thumb">
                                    <i class="bx bx-paper-plane"></i>
                                </div>
                                <div class="hl-card__body">
                                    <div class="hl-card__info">
                                        <div class="hl-card__name">
                                            {{ $firstSeg['from'] ?? '-' }} → {{ $lastSeg['to'] ?? '-' }}
                                        </div>
                                        <div class="hl-card__location">
                                            <i class="bx bx-time-five"></i>
                                            {{ $firstLeg['elapsedTime'] ?? '-' }} min
                                        </div>

                                        <div class="fl-card__segments">
                                            @foreach ($result['legs'] as $legIndex => $leg)
                                                <div class="fl-card__leg">
                                                    <span>Leg {{ $legIndex + 1 }}</span>
                                                </div>
                                                @foreach ($leg['segments'] as $seg)
                                                    <div class="fl-card__segment">
                                                        <span class="fl-card__segment-code">
                                                            {{ $seg['from'] }} → {{ $seg['to'] }}
                                                        </span>
                                                        <span class="fl-card__segment-time">
                                                            {{ $seg['departure_time'] }} - {{ $seg['arrival_time'] }}
                                                        </span>
                                                        <span class="fl-card__segment-flight">
                                                            {{ $seg['carrier'] }}{{ $seg['flight_number'] }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="hl-card__price-col">
                                        <span class="hl-card__price-label">Total price</span>
                                        <div class="hl-card__price">
                                            {{ $result['currency'] ?? '' }} {{ $result['totalPrice'] ?? '-' }}
                                        </div>
                                        <a href="javascript:void(0)" class="hl-card__btn">Select</a>
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
        .fl-results-note {
            margin-left: 10px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #888;
        }
        .fl-placeholder {
            font-size: 0.85rem;
            color: #777;
            background: #f7f7f7;
            border: 1px dashed #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }
        .hl-card--flight .hl-card__img.fl-card__thumb {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f3f7 0%, #fff 100%);
        }
        .fl-card__thumb i {
            font-size: 2rem;
            color: var(--color-primary);
        }
        .fl-card__segments {
            margin-top: 0.6rem;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .fl-card__leg {
            font-size: 0.78rem;
            font-weight: 700;
            color: #444;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .fl-card__segment {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 0.78rem;
            color: #666;
        }
        .fl-card__segment-code {
            font-weight: 700;
            color: #333;
        }
        .fl-card__segment-flight {
            font-weight: 600;
            color: var(--color-primary);
        }
    </style>
@endpush
