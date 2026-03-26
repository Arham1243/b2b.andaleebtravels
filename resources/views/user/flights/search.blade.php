@extends('user.layouts.main')
@section('content')
    <div class="my-4">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    @include('user.vue.main', [
                        'appId' => 'flights-search',
                        'appComponent' => 'flights-search',
                        'appJs' => 'flights-search',
                    ])
                </div>
                <div class="col-md-4">
                    <div class="hs-sidebar-grid">
                        <div class="hs-sidebar-card hs-sidebar-card--yellow">
                            <div class="hs-sidebar-card__icon"><i class='bx bxs-bell-ring'></i></div>
                            <div class="hs-sidebar-card__title">Notice Board</div>
                        </div>
                        <div class="hs-sidebar-card hs-sidebar-card--teal">
                            <div class="hs-sidebar-card__icon"><i class='bx bx-time-five'></i></div>
                            <div class="hs-sidebar-card__title">Hold Itineraries</div>
                        </div>
                        <div class="hs-sidebar-card hs-sidebar-card--mint">
                            <div class="hs-sidebar-card__icon"><i class='bx bxs-calendar-event'></i></div>
                            <div class="hs-sidebar-card__title">Travel Calendar</div>
                        </div>
                        <div class="hs-sidebar-card hs-sidebar-card--blue">
                            <div class="hs-sidebar-card__icon"><i class='bx bx-support'></i></div>
                            <div class="hs-sidebar-card__title">24/7 Support</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <h5 class="mb-3">Flight Results</h5>

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

                <div class="mb-3">
                    <strong>{{ $itineraryCount ?? 0 }}</strong> itineraries found
                </div>

                @if (!empty($results))
                    <div class="row">
                        @foreach ($results as $result)
                            <div class="col-md-6 mb-3">
                                <div class="hc-card h-100">
                                    <div class="hc-card__header">
                                        <i class='bx bx-paper-plane'></i>
                                        <div>
                                            <div class="hc-card__title">Itinerary #{{ $result['id'] ?? '-' }}</div>
                                            <div class="hc-card__subtitle">
                                                {{ $result['currency'] ?? '' }} {{ $result['totalPrice'] ?? '-' }}
                                            </div>
                                        </div>
                                    </div>

                                    @foreach ($result['legs'] as $leg)
                                        <div class="mb-2">
                                            <div class="hc-label">Leg ({{ $leg['elapsedTime'] ?? '-' }} min)</div>
                                            @foreach ($leg['segments'] as $seg)
                                                <div class="hc-alert">
                                                    <i class='bx bx-transfer'></i>
                                                    <span>
                                                        {{ $seg['from'] }} → {{ $seg['to'] }}
                                                        ({{ $seg['carrier'] }}{{ $seg['flight_number'] }})
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="hc-alert hc-alert--warning">
                        <i class='bx bx-error-circle'></i>
                        <span>No flights found for the selected criteria.</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
