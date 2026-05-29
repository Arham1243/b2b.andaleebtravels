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
                                        <input type="url" name="B2B_FACEBOOK" class="field"
                                            value="{{ \App\Support\SocialMediaConfig::adminValue(\App\Models\Config::B2B_FACEBOOK_KEY, 'FACEBOOK') }}">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Instagram</label>
                                        <input type="url" name="B2B_INSTAGRAM" class="field"
                                            value="{{ \App\Support\SocialMediaConfig::adminValue(\App\Models\Config::B2B_INSTAGRAM_KEY, 'INSTAGRAM') }}">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12 mt-3">
                                    <div class="form-fields">
                                        <label class="title">Twitter</label>
                                        <input type="url" name="B2B_TWITTER" class="field"
                                            value="{{ \App\Support\SocialMediaConfig::adminValue(\App\Models\Config::B2B_TWITTER_KEY, 'TWITTER') }}">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12 mt-3">
                                    <div class="form-fields">
                                        <label class="title">LinkedIn</label>
                                        <input type="url" name="B2B_LINKEDIN" class="field"
                                            value="{{ \App\Support\SocialMediaConfig::adminValue(\App\Models\Config::B2B_LINKEDIN_KEY, 'LINKEDIN') }}">
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12 mt-3">
                                    <div class="form-fields">
                                        <label class="title">YouTube</label>
                                        <input type="url" name="B2B_YOUTUBE" class="field"
                                            value="{{ \App\Support\SocialMediaConfig::adminValue(\App\Models\Config::B2B_YOUTUBE_KEY, 'YOUTUBE') }}">
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
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">WhatsApp number</label>
                                        <input type="text" name="B2B_WHATSAPP" class="field"
                                            value="{{ \App\Support\B2bConfig::value(\App\Models\Config::B2B_WHATSAPP_KEY, 'WHATSAPP') }}">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Support email</label>
                                        <input type="email" name="B2B_SUPPORT_EMAIL" class="field"
                                            value="{{ \App\Support\B2bConfig::value(\App\Models\Config::B2B_SUPPORT_EMAIL_KEY, 'SUPPORT_EMAIL') }}"
                                            autocomplete="email">
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

                    @php
                        $flightPromosEnabled = filter_var($config['FLIGHT_PROMOS_ENABLED'] ?? '1', FILTER_VALIDATE_BOOLEAN);
                    @endphp

                    <div class="form-box">
                        <div class="form-box__header d-flex align-items-center justify-content-between gap-3">
                            <div class="title mb-0">Flight search promo boxes</div>
                            <div class="form-check form-switch mb-0">
                                <input type="hidden" name="FLIGHT_PROMOS_ENABLED" value="0">
                                <input class="form-check-input" type="checkbox" name="FLIGHT_PROMOS_ENABLED" value="1"
                                    id="flight_promos_enabled" role="switch"
                                    @checked($flightPromosEnabled)>
                                <label class="form-check-label" for="flight_promos_enabled">
                                    {{ $flightPromosEnabled ? 'Enabled' : 'Disabled' }}
                                </label>
                            </div>
                        </div>
                        <div class="form-box__body" id="flight_promos_body" @if(! $flightPromosEnabled) hidden @endif>
                            
                            <h6 class="mb-2">Box 1 (gold)</h6>
                            <div class="row mb-3">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Kicker</label>
                                        <input type="text" name="FLIGHT_PROMO_1_KICKER" class="field flight-promo-field"
                                            value="{{ $config['FLIGHT_PROMO_1_KICKER'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Title</label>
                                        <input type="text" name="FLIGHT_PROMO_1_TITLE" class="field flight-promo-field"
                                            value="{{ $config['FLIGHT_PROMO_1_TITLE'] ?? '' }}">
                                    </div>
                                </div>
                            </div>

                            <h6 class="mb-2">Box 2 (blue)</h6>
                            <div class="row mb-3">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Kicker</label>
                                        <input type="text" name="FLIGHT_PROMO_2_KICKER" class="field flight-promo-field"
                                            value="{{ $config['FLIGHT_PROMO_2_KICKER'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Title</label>
                                        <textarea name="FLIGHT_PROMO_2_TITLE" class="field flight-promo-field" rows="2">{{ $config['FLIGHT_PROMO_2_TITLE'] ?? '' }}</textarea>
                                        <small class="text-muted d-block mt-1">Use a new line for a line break in the banner.</small>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-12 mt-2">
                                    <div class="form-fields">
                                        <label class="title">Subtext</label>
                                        <input type="text" name="FLIGHT_PROMO_2_CTA" class="field flight-promo-field"
                                            value="{{ $config['FLIGHT_PROMO_2_CTA'] ?? '' }}">
                                    </div>
                                </div>
                            </div>

                            <h6 class="mb-2">Box 3 (dark)</h6>
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Title</label>
                                        <input type="text" name="FLIGHT_PROMO_3_TITLE" class="field flight-promo-field"
                                            value="{{ $config['FLIGHT_PROMO_3_TITLE'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Subtext</label>
                                        <input type="text" name="FLIGHT_PROMO_3_CTA" class="field flight-promo-field"
                                            value="{{ $config['FLIGHT_PROMO_3_CTA'] ?? '' }}">
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

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('flight_promos_enabled');
    var body = document.getElementById('flight_promos_body');
    var label = toggle ? toggle.closest('.form-switch').querySelector('.form-check-label') : null;
    var fields = document.querySelectorAll('.flight-promo-field');

    if (!toggle || !body) return;

    function syncFlightPromosPanel() {
        var on = toggle.checked;
        body.hidden = !on;
        if (label) {
            label.textContent = on ? 'Enabled' : 'Disabled';
        }
        fields.forEach(function (field) {
            field.disabled = !on;
        });
    }

    toggle.addEventListener('change', syncFlightPromosPanel);
    syncFlightPromosPanel();
});
</script>
@endpush
