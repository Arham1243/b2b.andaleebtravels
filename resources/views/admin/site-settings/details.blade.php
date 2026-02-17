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
                                            required>
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
                            <div class="title">Contact Information</div>
                        </div>
                        <div class="form-box__body">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-12 ">
                                    <div class="form-fields">
                                        <label class="title">Phone</label>
                                        <div class="relative-div">
                                            <input type="text" name="COMPANYPHONE" class="field"
                                                value="{{ $config['COMPANYPHONE'] ?? '' }}" placeholder="Enter Phone Number"
                                                required>
                                        </div>
                                    </div>
                                </div>

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

                                <div class="col-lg-6 col-md-6 col-12 mt-3">
                                    <div class="form-fields">
                                        <label class="title">Email</label>
                                        <div class="relative-div">
                                            <input type="email" name="COMPANYEMAIL" class="field"
                                                value="{{ $config['COMPANYEMAIL'] ?? '' }}"
                                                placeholder="Enter Email Address" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12 mt-3">
                                    <div class="form-fields">
                                        <label class="title">Admin Email</label>
                                        <div class="relative-div">
                                            <input type="email" name="ADMINEMAIL" class="field"
                                                value="{{ $config['ADMINEMAIL'] ?? '' }}"
                                                placeholder="Enter Admin Email Address" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12 col-md-12 col-12 mt-3">
                                    <div class="form-fields">
                                        <label class="title">Address</label>
                                        <textarea name="ADDRESS" class="field" rows="3" placeholder="Enter Company Address" required>{{ $config['ADDRESS'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-box">
                        <div class="form-box__header">
                            <div class="title">Commission</div>
                        </div>
                        <div class="form-box__body">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Hotel Commission In Percent</label>
                                        <div class="relative-div">
                                            <input type="number" name="HOTEL_COMMISSION_PERCENTAGE" class="field"
                                                value="{{ $config['HOTEL_COMMISSION_PERCENTAGE'] ?? '10' }}"
                                                placeholder="Enter Hotel Commission In Percent" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Insurance Commission In Percent</label>
                                        <div class="relative-div">
                                            <input type="number" name="INSURANCE_COMMISSION_PERCENTAGE" class="field"
                                                value="{{ $config['INSURANCE_COMMISSION_PERCENTAGE'] ?? '30' }}"
                                                placeholder="Enter Insurance Commission In Percent" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-box">
                        <div class="form-box__header">
                            <div class="title">Taxes</div>
                        </div>
                        <div class="form-box__body">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Vat Percentage</label>
                                        <div class="relative-div">
                                            <input type="number" name="VAT_PERCENTAGE" class="field"
                                                value="{{ $config['VAT_PERCENTAGE'] ?? '' }}"
                                                placeholder="Enter Vat Percentage" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-fields">
                                        <label class="title">Service Tax Percentage</label>
                                        <div class="relative-div">
                                            <input type="number" name="SERVICE_TAX_PERCENTAGE" class="field"
                                                value="{{ $config['SERVICE_TAX_PERCENTAGE'] ?? '' }}"
                                                placeholder="Enter Service Tax Percentage" required>
                                        </div>
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

                                <div class="col-lg-12 col-md-12 col-12 mt-3">
                                    <div class="form-fields">
                                        <label class="title">Disclaimer</label>
                                        <textarea name="DISCLAIMER" class="field" rows="3" placeholder="Enter Footer Disclaimer">{{ $config['DISCLAIMER'] ?? '' }}</textarea>
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
