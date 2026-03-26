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
                                <div class="fl-card__header">
                                    <div class="fl-card__route">
                                        <span class="fl-card__code">{{ $firstSeg['from'] ?? '-' }}</span>
                                        <i class="bx bx-transfer"></i>
                                        <span class="fl-card__code">{{ $lastSeg['to'] ?? '-' }}</span>
                                    </div>
                                    <div class="fl-card__meta">
                                        <span><i class="bx bx-time-five"></i> {{ $firstLeg['elapsedTime'] ?? '-' }} min</span>
                                        <span><i class="bx bx-stopwatch"></i>
                                            {{ (int) (($firstLeg['segments'][0]['stop_count'] ?? 0)) }} stop{{ ((int) (($firstLeg['segments'][0]['stop_count'] ?? 0))) === 1 ? '' : 's' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="hl-card__body">
                                    <div class="hl-card__info">
                                        <div class="fl-card__timeline">
                                            @foreach ($result['legs'] as $legIndex => $leg)
                                                @php
                                                    $segCount = count($leg['segments'] ?? []);
                                                @endphp
                                                <div class="fl-card__leg">
                                                    <div class="fl-card__leg-title">
                                                        Leg {{ $legIndex + 1 }}
                                                        <span>{{ $segCount }} segment{{ $segCount === 1 ? '' : 's' }}</span>
                                                    </div>
                                                    @foreach ($leg['segments'] as $seg)
                                                        <div class="fl-card__segment">
                                                            <div class="fl-card__segment-left">
                                                                <div class="fl-card__segment-time">
                                                                    {{ $seg['departure_time'] }}
                                                                </div>
                                                                <div class="fl-card__segment-airport">
                                                                    {{ $seg['from'] }}
                                                                </div>
                                                            </div>
                                                            <div class="fl-card__segment-line">
                                                                <span class="fl-card__segment-flight">
                                                                    {{ $seg['carrier'] }}{{ $seg['flight_number'] }}
                                                                </span>
                                                                <div class="fl-card__segment-dots"></div>
                                                            </div>
                                                            <div class="fl-card__segment-right">
                                                                <div class="fl-card__segment-time">
                                                                    {{ $seg['arrival_time'] }}
                                                                </div>
                                                                <div class="fl-card__segment-airport">
                                                                    {{ $seg['to'] }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="hl-card__price-col">
                                        <span class="hl-card__price-label">Total price</span>
                                        <div class="hl-card__price">
                                            {{ $result['currency'] ?? '' }} {{ $result['totalPrice'] ?? '-' }}
                                        </div>
                                        <a href="javascript:void(0)" class="hl-card__btn">Select</a>
                                        <span class="fl-card__note">No hidden charges</span>
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
            padding: 0;
            overflow: hidden;
        }
        .fl-card__header {
            padding: 16px 20px;
            background: #f7f6fa;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .fl-card__route {
            font-size: 1.05rem;
            font-weight: 800;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .fl-card__route i {
            color: var(--color-primary);
        }
        .fl-card__meta {
            display: flex;
            gap: 14px;
            font-size: 0.8rem;
            color: #666;
            font-weight: 600;
        }
        .fl-card__meta i {
            color: var(--color-primary);
        }
        .fl-card__timeline {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .fl-card__leg {
            background: #fff;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            padding: 12px 14px;
        }
        .fl-card__leg-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #444;
            margin-bottom: 10px;
        }
        .fl-card__leg-title span {
            font-size: 0.72rem;
            font-weight: 600;
            color: #888;
            text-transform: none;
        }
        .fl-card__segment {
            display: grid;
            grid-template-columns: 80px 1fr 80px;
            gap: 12px;
            align-items: center;
            padding: 8px 0;
            border-top: 1px dashed #eee;
        }
        .fl-card__segment:first-of-type {
            border-top: none;
        }
        .fl-card__segment-time {
            font-size: 0.85rem;
            font-weight: 700;
            color: #222;
        }
        .fl-card__segment-airport {
            font-size: 0.75rem;
            font-weight: 600;
            color: #777;
        }
        .fl-card__segment-line {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            color: #999;
            font-size: 0.72rem;
        }
        .fl-card__segment-dots {
            width: 100%;
            height: 2px;
            background: repeating-linear-gradient(
                to right,
                #d9d9d9,
                #d9d9d9 6px,
                transparent 6px,
                transparent 12px
            );
        }
        .fl-card__segment-flight {
            font-weight: 700;
            color: var(--color-primary);
        }
        .fl-card__note {
            margin-top: 6px;
            font-size: 0.72rem;
            color: #999;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .fl-card__segment {
                grid-template-columns: 1fr;
            }
            .fl-card__segment-line {
                align-items: flex-start;
            }
        }
    </style>
@endpush
