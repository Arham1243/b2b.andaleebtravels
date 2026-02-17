@extends('frontend.layouts.main')
@section('content')
    <section class="section-gap">
        <div class="container">
            @php
                $adultCount = (int) request('adult_count', 0);
                $childrenCount = (int) request('children_count', 0);
                $infantCount = (int) request('infant_count', 0);
                $selectedPlan = request('plan', '');
                [$planCode, $ssrFeeCode] = explode('~', $selectedPlan . '~');
            @endphp

            <form id="checkoutForm" action="{{ route('frontend.travel-insurance.payment.process') }}" method="POST">
                @csrf

                @if ($selectedPlanData)
                    <input type="hidden" name="total_premium" value="{{ $selectedPlanData['TotalPremiumAmount'] ?? 0 }}">
                    <input type="hidden" name="plan_title"
                        value="{{ html_entity_decode($selectedPlanData['PlanTitle'] ?? '') }}">
                @endif

                {{-- Hidden inputs to preserve all search parameters --}}
                <input type="hidden" name="plan_code" value="{{ $planCode }}">
                <input type="hidden" name="ssr_fee_code" value="{{ $ssrFeeCode }}">
                <input type="hidden" name="origin" value="{{ request('origin') }}">
                <input type="hidden" name="destination" value="{{ request('destination') }}">
                <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                <input type="hidden" name="return_date" value="{{ request('return_date') }}">
                <input type="hidden" name="adult_count" value="{{ $adultCount }}">
                <input type="hidden" name="children_count" value="{{ $childrenCount }}">
                <input type="hidden" name="infant_count" value="{{ $infantCount }}">
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

                <div class="row">
                    <div class="col-lg-8">

                        <div class="modern-card">
                            <div class="card-title">
                                <i class='bx bx-user'></i> Lead guest details
                            </div>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="custom-info-alert my-2">
                                        <div class="icon"><i class="bx bx-info-circle"></i></div>
                                        <div class="content">All names of those travelling must exactly match their
                                            passport as
                                            charges may apply to change a name. If you have autofill enabled on your
                                            browser or
                                            device, please check all names and details are correct.</div>
                                    </div>
                                    <p class="text-muted fw-bold pt-3 mb-1">The main contact for this booking</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="lead[fname]" class="custom-input" autocomplete="off"
                                        required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Main Contact Email</label>
                                    <input type="email" name="lead[email]" class="custom-input" autocomplete="off"
                                        required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Mobile No.</label>
                                    <input type="text" name="lead[number]" class="custom-input" autocomplete="off"
                                        required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Country Of Residence</label>
                                    <select class="custom-select" name="lead[country_of_residence]" required>
                                        <option value="" selected disabled>-- Select --</option>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country->iso_code }}">{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        @for ($i = 0; $i < $adultCount; $i++)
                            <div class="modern-card">
                                <div class="card-title">
                                    <i class='bx bx-user'></i> #{{ $i + 1 }} Adult Details
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="adult[fname][]" class="custom-input" autocomplete="off"
                                            required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="adult[lname][]" class="custom-input" autocomplete="off"
                                            required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Date Of Birth</label>
                                        <input type="date" name="adult[dob][]" class="custom-input"
                                            autocomplete="off" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Gender</label>
                                        <select name="adult[gender][]" class="custom-select" required>
                                            <option value="" selected disabled>Select</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Passport No.</label>
                                        <input type="text" name="adult[passport][]" class="custom-input"
                                            autocomplete="off" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nationality</label>
                                        <select name="adult[nationality][]" class="custom-select" required>
                                            <option value="" selected disabled>Select</option>
                                            @foreach ($countries as $nationality)
                                                <option value="{{ $nationality->iso_code }}">{{ $nationality->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Country Of Residence</label>
                                        <select name="adult[country_of_residence][]" class="custom-select" required>
                                            <option value="" selected disabled>Select</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->iso_code }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endfor

                        @for ($i = 0; $i < $childrenCount; $i++)
                            <div class="modern-card">
                                <div class="card-title">
                                    <i class='bx bx-user'></i> #{{ $i + 1 }} Child Details
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="child[fname][]" class="custom-input"
                                            autocomplete="off" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="child[lname][]" class="custom-input"
                                            autocomplete="off" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Date Of Birth</label>
                                        <input type="date" name="child[dob][]" class="custom-input"
                                            autocomplete="off" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Gender</label>
                                        <select name="child[gender][]" class="custom-select" required>
                                            <option value="" selected disabled>Select</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Passport No.</label>
                                        <input type="text" name="child[passport][]" class="custom-input"
                                            autocomplete="off" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nationality</label>
                                        <select name="child[nationality][]" class="custom-select" required>
                                            <option value="" selected disabled>Select</option>
                                            @foreach ($countries as $nationality)
                                                <option value="{{ $nationality->iso_code }}">{{ $nationality->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Country Of Residence</label>
                                        <select name="child[country_of_residence][]" class="custom-select" required>
                                            <option value="" selected disabled>Select</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->iso_code }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endfor

                        @for ($i = 0; $i < $infantCount; $i++)
                            <div class="modern-card">
                                <div class="card-title">
                                    <i class='bx bx-user'></i> #{{ $i + 1 }} Infant Details
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="infant[fname][]" class="custom-input"
                                            autocomplete="off" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="infant[lname][]" class="custom-input"
                                            autocomplete="off" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Date Of Birth</label>
                                        <input type="date" name="infant[dob][]" class="custom-input"
                                            autocomplete="off" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Gender</label>
                                        <select name="infant[gender][]" class="custom-select" required>
                                            <option value="" selected disabled>Select</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Passport No.</label>
                                        <input type="text" name="infant[passport][]" class="custom-input"
                                            autocomplete="off" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nationality</label>
                                        <select name="infant[nationality][]" class="custom-select" required>
                                            <option value="" selected disabled>Select</option>
                                            @foreach ($countries as $nationality)
                                                <option value="{{ $nationality->iso_code }}">{{ $nationality->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Country Of Residence</label>
                                        <select name="infant[country_of_residence][]" class="custom-select" required>
                                            <option value="" selected disabled>Select</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->iso_code }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endfor

                    </div>

                    <div class="col-lg-4">
                        <div class="sticky-sidebar">
                            @if ($selectedPlanData)
                                @php
                                    $planTitle = html_entity_decode($selectedPlanData['PlanTitle'] ?? 'Selected Plan');
                                    $planContent = html_entity_decode($selectedPlanData['PlanContent'] ?? '');
                                    $finalPrice =
                                        $selectedPlanData['TotalPremiumAmount'] +
                                        $selectedPlanData['TotalPremiumAmount'] * 0.3;
                                    $currencyCode = $selectedPlanData['CurrencyCode'] ?? 'AED';
                                @endphp

                                <label class="plan-card-item plan-card-item--single">
                                    <input type="radio" name="insurance_plan" class="plan-radio-input" checked="">
                                    <div class="plan-card-inner">
                                        <div class="plan-info">
                                            <h6 class="plan-title">{{ $planTitle }}</h6>
                                            <a href="javascript:void(0)" data-popup-trigger
                                                data-popup-title="{{ $planTitle }}"
                                                data-popup-id="popup-selected-plan" class="plan-link">More Benefits <i
                                                    class="bx bx-chevron-right"></i></a>
                                            <div id="popup-selected-plan" class="d-none">
                                                {!! $planContent !!}
                                            </div>
                                        </div>

                                        <div class="plan-cost">
                                            <div class="price-tag">{{ number_format($finalPrice, 2) }}
                                                <small>{{ $currencyCode }}</small></div>
                                            <span class="tax-note">Including Tax</span>
                                        </div>
                                    </div>
                                </label>
                            @else
                                <div class="alert alert-warning">
                                    <i class='bx bx-info-circle'></i> No plan selected. Please go back and select a plan.
                                </div>
                            @endif
                            <div class="modern-card">
                                <div class="card-title">
                                    <i class='bx bx-credit-card'></i> Choose Payment Method
                                </div>

                                <!-- Option 1: Card -->
                                <label class="payment-option">
                                    <div class="payment-header">
                                        <input type="radio" name="payment_method" class="payment-radio" value="payby"
                                            checked required>
                                        <span class="payment-label">Credit / Debit Card</span>
                                    </div>
                                    <div class="payment-desc">
                                        Note: You will be redirected to the secure payment gateway to complete your
                                        purchase.
                                    </div>
                                </label>

                                <!-- Option 2: Tabby -->
                                <label class="payment-option">
                                    <div class="payment-header">
                                        <input type="radio" name="payment_method" class="payment-radio" value="tabby"
                                            required>
                                        <span class="payment-label">Tabby - Buy Now Pay Later</span>
                                    </div>
                                    <div class="payment-desc">
                                        Pay in 4 interest-free installments. No fees, no hidden costs.
                                    </div>
                                </label>
                            </div>

                            <button type="submit" class="btn-primary-custom mt-2">
                                <i class='bx bx-lock-alt'></i> Proceed to Payment
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

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

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const popupWrapper = document.querySelector('[data-popup-wrapper]');
            const popupClose = document.querySelector('[data-popup-close]');
            const popupTriggers = document.querySelectorAll('[data-popup-trigger]');

            if (popupTriggers.length > 0) {
                popupTriggers.forEach(el => {
                    el.addEventListener('click', () => {
                        const title = el.dataset.popupTitle;
                        const popupId = el.dataset.popupId;
                        const htmlContent = document.getElementById(popupId)?.innerHTML || '';

                        document.querySelector('[data-popup-title]').innerHTML = title;
                        document.querySelector('[data-popup-text]').innerHTML = htmlContent;
                        popupWrapper.classList.add('open');
                    });
                });
            }

            if (popupClose) {
                popupClose.addEventListener('click', () => {
                    popupWrapper.classList.remove('open');
                });
            }

            if (popupWrapper) {
                popupWrapper.addEventListener('click', function(e) {
                    if (e.target === popupWrapper) {
                        popupWrapper.classList.remove('open');
                    }
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const checkoutForm = document.getElementById('checkoutForm');
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            checkoutForm.addEventListener('submit', function(e) {
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';
            });
        });
    </script>
@endpush
