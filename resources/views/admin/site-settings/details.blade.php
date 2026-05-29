@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            <form action="{{ route('admin.settings.details') }}" method="POST">
                <div class="custom-sec">
                    <div class="custom-sec__header mt-4">
                        <div class="section-content">
                            <h3 class="heading">{{ isset($title) ? $title : '' }}</h3>
                        </div>
                        <button class="themeBtn">Save Changes</button>
                    </div>

                    <div class="form-box">
                        <div class="form-box__header">
                            <div class="title">Social Media</div>
                        </div>
                        <div class="form-box__body">
                            @csrf
                            <div class="row">

                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Facebook</label>
                                        <input type="url" name="FACEBOOK" class="field"
                                            value="{{ $config['FACEBOOK'] ?? '' }} " placeholder="Enter Facebook Address">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Instagram</label>
                                        <input type="url" name="INSTAGRAM" class="field"
                                            value="{{ $config['INSTAGRAM'] ?? '' }}" placeholder="Enter Instagram Address"
                                            >
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12 mt-3">
                                    <div class="form-fields">
                                        <label class="title">Twitter</label>
                                        <input type="url" name="TWITTER" class="field"
                                            value="{{ $config['TWITTER'] ?? '' }}" placeholder="Enter Twitter Address"
                                            required>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12 mt-3">
                                    <div class="form-fields">
                                        <label class="title">LinkedIn</label>
                                        <input type="url" name="LINKEDIN" class="field"
                                            value="{{ $config['LINKEDIN'] ?? '' }}" placeholder="Enter LinkedIn Address">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12 mt-3">
                                    <div class="form-fields">
                                        <label class="title">YouTube</label>
                                        <input type="url" name="YOUTUBE" class="field"
                                            value="{{ $config['YOUTUBE'] ?? '' }}" placeholder="Enter YouTube Channel URL">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-box">
                        <div class="form-box__header">
                            <div class="title">Notifications</div>
                        </div>
                        <div class="form-box__body">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Admin notification email</label>
                                        <input type="email" name="ADMIN_NOTIFICATION_EMAIL" class="field"
                                            value="{{ $config['ADMIN_NOTIFICATION_EMAIL'] ?? '' }}"
                                            placeholder="e.g. ops@youragency.com"
                                            autocomplete="email">
                                        <small class="text-muted d-block mt-1">Flight booking confirmations and alerts are sent to this address.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-box">
                        <div class="form-box__header">
                            <div class="title">Contact Information</div>
                        </div>
                        <div class="form-box__body">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-12 ">
                                    <div class="form-fields">
                                        <label class="title">Whatsapp</label>
                                        <div class="relative-div">
                                            <input type="text" name="WHATSAPP" class="field"
                                                value="{{ $config['WHATSAPP'] ?? '' }}" placeholder="Enter Whatsapp Number"
                                                required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-box">
                        <div class="form-box__header">
                            <div class="title">Wallet bank transfer (agents)</div>
                        </div>
                        <div class="form-box__body">
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-12">
                                    <div class="form-fields">
                                        <label class="title">Bank account details</label>
                                        <textarea name="WALLET_BANK_TRANSFER_DETAILS" class="field" rows="8"
                                            >{{ $config['WALLET_BANK_TRANSFER_DETAILS'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-box">
                        <div class="form-box__header">
                            <div class="title">Flight search promo boxes</div>
                        </div>
                        <div class="form-box__body">
                            <p class="text-muted mb-3" style="font-size:13px;">
                                Text shown on the flight search page promo banners. Leave a field empty to use the default shown in the placeholder.
                            </p>

                            <h6 class="mb-2">Box 1 (gold)</h6>
                            <div class="row mb-3">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Kicker</label>
                                        <input type="text" name="FLIGHT_PROMO_1_KICKER" class="field"
                                            value="{{ $config['FLIGHT_PROMO_1_KICKER'] ?? '' }}"
                                            placeholder="{{ $flightPromoDefaults['FLIGHT_PROMO_1_KICKER'] }}">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Title</label>
                                        <input type="text" name="FLIGHT_PROMO_1_TITLE" class="field"
                                            value="{{ $config['FLIGHT_PROMO_1_TITLE'] ?? '' }}"
                                            placeholder="{{ $flightPromoDefaults['FLIGHT_PROMO_1_TITLE'] }}">
                                    </div>
                                </div>
                            </div>

                            <h6 class="mb-2">Box 2 (blue)</h6>
                            <div class="row mb-3">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Kicker</label>
                                        <input type="text" name="FLIGHT_PROMO_2_KICKER" class="field"
                                            value="{{ $config['FLIGHT_PROMO_2_KICKER'] ?? '' }}"
                                            placeholder="{{ $flightPromoDefaults['FLIGHT_PROMO_2_KICKER'] }}">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Title</label>
                                        <textarea name="FLIGHT_PROMO_2_TITLE" class="field" rows="2"
                                            placeholder="{{ str_replace("\n", ' / ', $flightPromoDefaults['FLIGHT_PROMO_2_TITLE']) }}">{{ $config['FLIGHT_PROMO_2_TITLE'] ?? '' }}</textarea>
                                        <small class="text-muted d-block mt-1">Use a new line for a line break in the banner.</small>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-12 mt-2">
                                    <div class="form-fields">
                                        <label class="title">Subtext</label>
                                        <input type="text" name="FLIGHT_PROMO_2_CTA" class="field"
                                            value="{{ $config['FLIGHT_PROMO_2_CTA'] ?? '' }}"
                                            placeholder="{{ $flightPromoDefaults['FLIGHT_PROMO_2_CTA'] }}">
                                    </div>
                                </div>
                            </div>

                            <h6 class="mb-2">Box 3 (dark)</h6>
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Title</label>
                                        <input type="text" name="FLIGHT_PROMO_3_TITLE" class="field"
                                            value="{{ $config['FLIGHT_PROMO_3_TITLE'] ?? '' }}"
                                            placeholder="{{ $flightPromoDefaults['FLIGHT_PROMO_3_TITLE'] }}">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Subtext</label>
                                        <input type="text" name="FLIGHT_PROMO_3_CTA" class="field"
                                            value="{{ $config['FLIGHT_PROMO_3_CTA'] ?? '' }}"
                                            placeholder="{{ $flightPromoDefaults['FLIGHT_PROMO_3_CTA'] }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-box">
                        <div class="form-box__header">
                            <div class="title">Footer Content</div>
                        </div>
                        <div class="form-box__body">
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-12 mt-3">
                                    <div class="form-fields">
                                        <label class="title">Copyright Text</label>
                                        <input type="text" name="COPYRIGHT" class="field"
                                            value="{{ $config['COPYRIGHT'] ?? '' }}"
                                            placeholder="e.g., © {{date('Y')}} Andaleeb Travel Agency. All Rights Reserved.">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
