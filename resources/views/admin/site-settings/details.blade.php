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
