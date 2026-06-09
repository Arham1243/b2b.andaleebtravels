@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.vendors.create') }}
            <form action="{{ route('admin.vendors.store') }}" method="POST" enctype="multipart/form-data" id="validation-form">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-wrapper">
                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">Vendor Details</div>
                                </div>
                                <div class="form-box__body">
                                    @include('admin.vendors.partials._form')
                                    <div class="form-fields">
                                        <small class="text-muted">An agent code is generated automatically. The vendor will receive a portal invite email immediately (no approval required).</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @include('admin.vendors.partials._form-providers', [
                            'vendor' => $vendor,
                            'adminProviders' => $adminProviders,
                            'adminFlightProviders' => $adminFlightProviders,
                        ])
                    </div>
                    <div class="col-md-4">
                        <div class="seo-wrapper">
                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">Status</div>
                                </div>
                                <div class="form-box__body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="active"
                                            value="active" {{ old('status', 'active') == 'active' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">Active</label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="status" id="inactive"
                                            value="inactive" {{ old('status') == 'inactive' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="inactive">Inactive</label>
                                    </div>

                                    <div class="text-end mt-4">
                                        <button class="themeBtn" type="submit">Create Vendor</button>
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
