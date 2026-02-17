@extends('frontend.layouts.main')
@section('content')
    <section class="section-plans mar-y">
        <div class="container">
            <h4 class="fw-bold mb-3">Select Passenger</h4>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="main-page-search">
                        @include('frontend.vue.main', [
                            'appId' => 'insurance-search',
                            'appComponent' => 'insurance-search',
                            'appJs' => 'insurance-search',
                        ])
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if (isset($data) && !empty($data['available_plans']))
        <section class="section-plans mar-y">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="text-center">
                            <h4 class="fw-bold mb-1">Available Insurance Plans</h4>
                            <p class="text-muted small">Choose the coverage that suits you best.</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('frontend.travel-insurance.details') }}" method="GET">
                    @php
                        $availablePlans = $data['available_plans'];
                        usort($availablePlans, function ($a, $b) {
                            $finalA = $a['TotalPremiumAmount'] * 1.3;
                            $finalB = $b['TotalPremiumAmount'] * 1.3;
                            return $finalA <=> $finalB;
                        });
                    @endphp

                    <div class="row justify-content-center">
                        <input type="hidden" name="origin" value="{{ request('origin') }}">
                        <input type="hidden" name="destination" value="{{ request('destination') }}">
                        <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                        <input type="hidden" name="return_date" value="{{ request('return_date') }}">
                        <input type="hidden" name="adult_count" value="{{ request('adult_count') }}">
                        <input type="hidden" name="children_count" value="{{ request('children_count') }}">
                        <input type="hidden" name="infant_count" value="{{ request('infant_count') }}">
                        <input type="hidden" name="residence_country" value="{{ request('residence_country') }}">
                        @if (request('adult_ages'))
                            @foreach (request('adult_ages') as $age)
                                <input type="hidden" name="adult_ages[]" value="{{ $age }}">
                            @endforeach
                        @endif

                        @if (request('children_ages'))
                            @foreach (request('children_ages') as $age)
                                <input type="hidden" name="children_ages[]" value="{{ $age }}">
                            @endforeach
                        @endif

                        <div class="col-md-8">
                            <div class="plans-list-wrapper">
                                @foreach ($availablePlans as $index => $plan)
                                    @php
                                        $planTitle = html_entity_decode($plan['PlanTitle'] ?? '');
                                        $planContent = html_entity_decode($plan['PlanContent'] ?? '');
                                        $finalPrice = $plan['TotalPremiumAmount'] + $plan['TotalPremiumAmount'] * 0.3;
                                        $currencyCode = $plan['CurrencyCode'] ?? 'AED';
                                    @endphp

                                    <label class="plan-card-item">
                                        <input type="radio" name="plan"
                                            value="{{ $plan['PlanCode'] }}~{{ $plan['SSRFeeCode'] }}"
                                            class="plan-radio-input" {{ $index === 0 ? 'checked' : '' }} required>
                                        <div class="plan-card-inner">
                                            <div class="plan-info">
                                                <h6 class="plan-title">{{ $planTitle }}</h6>
                                                <a href="javascript:void(0)" data-popup-trigger
                                                    data-popup-title="{{ $planTitle }}"
                                                    data-popup-id="popup-{{ $index }}" class="plan-link">More
                                                    Benefits <i class="bx bx-chevron-right"></i></a>
                                                <div id="popup-{{ $index }}" class="d-none">
                                                    {!! $planContent !!}
                                                </div>
                                            </div>

                                            <div class="plan-cost">
                                                <div class="price-tag">{{ number_format($finalPrice, 2) }}
                                                    <small>{{ $currencyCode }}</small>
                                                </div>
                                                <span class="tax-note">Including Tax</span>
                                            </div>

                                            <div class="plan-check-icon">
                                                <i class='bx bx-check'></i>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    @php
                        $upsellPlans = $data['available_upsell_plans'] ?? [];

                        // Filter out excluded plans
                        $excludedPlans = ['Travel Visit Assurance (Covid Plus)', 'Travel Assurance (Covid Plus)'];
                        $filteredPlans = array_filter($upsellPlans, function ($upsellGroup) use ($excludedPlans) {
                            $plan = $upsellGroup['UpsellPlans']['UpsellPlan'] ?? [];
                            $planType = $plan['PlanType'] ?? '';
                            return !in_array($planType, $excludedPlans);
                        });

                        // Sort the filtered plans
                        usort($filteredPlans, function ($a, $b) {
                            $finalA = $a['UpsellPlans']['UpsellPlan']['TotalPremiumAmount'] * 1.3;
                            $finalB = $b['UpsellPlans']['UpsellPlan']['TotalPremiumAmount'] * 1.3;
                            return $finalA <=> $finalB;
                        });
                    @endphp

                    @if (!empty($filteredPlans))
                        <div class="row mb-4 mt-5">
                            <div class="col-12">
                                <div class="text-center">
                                    <h4 class="fw-bold mb-1">Other Plans</h4>
                                    <p class="text-muted small">Additional coverage options available.</p>
                                </div>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="plans-list-wrapper">
                                    @foreach ($filteredPlans as $index => $upsellGroup)
                                        @php
                                            $plan = $upsellGroup['UpsellPlans']['UpsellPlan'];
                                            $planType = $plan['PlanType'] ?? '';

                                            if (in_array($planType, $excludedPlans)) {
                                                continue;
                                            }

                                            $planTitle = html_entity_decode($plan['PlanTitle'] ?? '');
                                            $planContent = html_entity_decode($plan['PlanContent'] ?? '');
                                            $finalPrice =
                                                $plan['TotalPremiumAmount'] + $plan['TotalPremiumAmount'] * 0.3;
                                            $currencyCode = $plan['CurrencyCode'] ?? 'AED';
                                            $upsellIndex = 'upsell-' . $index;
                                        @endphp

                                        <label class="plan-card-item">
                                            <input type="radio" name="plan"
                                                value="{{ $plan['PlanCode'] }}~{{ $plan['SSRFeeCode'] }}"
                                                class="plan-radio-input" required>

                                            <div class="plan-card-inner">
                                                <div class="plan-info">
                                                    <h6 class="plan-title">{{ $planTitle }}</h6>
                                                    <a href="javascript:void(0)" data-popup-trigger
                                                        data-popup-title="{{ $planTitle }}"
                                                        data-popup-id="popup-{{ $upsellIndex }}" class="plan-link">More
                                                        Benefits <i class="bx bx-chevron-right"></i></a>
                                                    <div id="popup-{{ $upsellIndex }}" class="d-none">
                                                        {!! $planContent !!}
                                                    </div>
                                                </div>

                                                <div class="plan-cost">
                                                    <div class="price-tag">{{ number_format($finalPrice, 2) }}
                                                        <small>{{ $currencyCode }}</small>
                                                    </div>
                                                    <span class="tax-note">Including Tax</span>
                                                </div>

                                                <div class="plan-check-icon">
                                                    <i class='bx bx-check'></i>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Sticky Bottom Continue Bar -->
                    <div class="continue-bar">
                        <div class="container">
                            <div class="continue-bar-padding">
                                <div class="row align-items-center justify-content-center">
                                    <div class="col-12 col-md-6">
                                        <div class="details-wrapper">
                                            <div class="details">
                                                <div class="total-price" style="font-size: 0.9rem; color: #666;">
                                                    Choose a plan to continue
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="details-btn-wrapper">
                                            <button type="submit" class="btn-primary-custom">
                                                Continue <i class='bx bx-right-arrow-alt'></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    @else
        <section class="section-plans mar-y">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="text-center">
                            <h4 class="fw-bold mb-1">Select Insurance Plan</h4>
                            <p class="text-muted small">Please fill in the search form above to view available plans.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif


    <div class="custom-popup-wrapper" data-popup-wrapper="">
        <div class="custom-popup">
            <div class="custom-popup__header">
                <div class="title" data-popup-title="">
                </div>
                <div class="close-icon" data-popup-close="">
                    <i class="bx bx-x"></i>
                </div>
            </div>
            <div class="custom-popup__content">
                <div data-popup-text=""> </div>
            </div>
        </div>
    </div>
@endsection
