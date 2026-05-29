@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.vendors.edit', $vendor) }}
            <form action="{{ route('admin.vendors.update', $vendor) }}" method="POST" enctype="multipart/form-data" id="validation-form">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-wrapper">
                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">
                                        @if ($vendor->parent_vendor_id)
                                            Edit Sub Agent
                                        @else
                                            Edit Vendor
                                        @endif
                                    </div>
                                </div>
                                <div class="form-box__body">
                                    @include('admin.vendors.partials._form', ['vendor' => $vendor])
                                </div>
                            </div>
                        </div>

                        @if (!$vendor->parent_vendor_id)
                            @include('admin.vendors.partials._form-providers', [
                                'vendor' => $vendor,
                                'adminProviders' => $adminProviders,
                                'adminFlightProviders' => $adminFlightProviders,
                            ])
                        @endif
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
                                            value="active" {{ old('status', $vendor->status) == 'active' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">Active</label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="status" id="inactive"
                                            value="inactive" {{ old('status', $vendor->status) == 'inactive' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="inactive">Inactive</label>
                                    </div>
                                    @if ($vendor->isPendingApproval() && $vendor->isAgencyAccount())
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="radio" name="status" id="pending"
                                                value="pending" {{ old('status', $vendor->status) == 'pending' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="pending">Pending Approval</label>
                                        </div>
                                    @endif

                                    <div class="text-end mt-4">
                                        <button class="themeBtn" type="submit">Save Changes</button>
                                    </div>
                                </div>
                            </div>

                            @if (!$vendor->parent_vendor_id)
                                <div class="form-box mt-3">
                                    <div class="form-box__header">
                                        <div class="title">Credit Limit</div>
                                    </div>
                                    <div class="form-box__body">
                                        <div class="row g-2 mb-3">
                                            <div class="col-4">
                                                <div class="text-muted" style="font-size:11px; text-transform:uppercase; font-weight:600;">Used</div>
                                                <div class="fw-semibold" style="font-size:14px; color:#b45309;">{!! formatPrice($vendor->creditUsedAmount()) !!}</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-muted" style="font-size:11px; text-transform:uppercase; font-weight:600;">Available</div>
                                                <div class="fw-semibold" style="font-size:14px; color:#15803d;">{!! formatPrice($vendor->creditAvailableAmount()) !!}</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-muted" style="font-size:11px; text-transform:uppercase; font-weight:600;">Spendable</div>
                                                <div class="fw-semibold" style="font-size:14px;">{!! formatPrice($vendor->totalSpendableBalance()) !!}</div>
                                            </div>
                                        </div>
                                        <p class="text-muted mb-2" style="font-size:12px;">
                                            Prepaid wallet: <strong>{!! formatPrice($vendor->main_balance ?? 0) !!}</strong>
                                        </p>
                                        <div class="form-group mb-0">
                                            <label for="credit_limit" class="form-label">Credit limit (AED)</label>
                                            <input type="number" name="credit_limit" id="credit_limit" class="form-control"
                                                step="0.01" min="0" max="99999999.99"
                                                value="{{ old('credit_limit', $vendor->credit_limit ?? 0) }}">
                                            @error('credit_limit')
                                                <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                                            @enderror
                                            @if ($vendor->creditUsedAmount() > 0)
                                                <p class="text-muted mt-2 mb-0" style="font-size:11px;">
                                                    Minimum allowed: {!! formatPrice($vendor->creditUsedAmount()) !!} (current credit in use).
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="text-end mt-3">
                                @if ($vendor->isPendingApproval() && $vendor->isAgencyAccount())
                                    <a href="{{ route('admin.vendors.pending.show', $vendor) }}" class="text-muted" style="font-size:13px;">← Back to signup request</a>
                                @elseif ($vendor->parent_vendor_id)
                                    <a href="{{ route('admin.vendors.show', $vendor->parent_vendor_id) }}" class="text-muted" style="font-size:13px;">← Back to agency</a>
                                @else
                                    <a href="{{ route('admin.vendors.show', $vendor) }}" class="text-muted" style="font-size:13px;">← Back to vendor</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
