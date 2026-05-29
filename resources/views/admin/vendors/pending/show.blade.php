@extends('admin.layouts.main')
@php
    $canManagePending = \App\Support\B2bAdminPortalUi::can('vendors_pending_manage');
    $canEditVendor = \App\Support\B2bAdminPortalUi::can('vendors_edit');
@endphp
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.vendors.pending.show', $vendor) }}

            <header class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h1 style="font-size:clamp(1.4rem,2vw,1.75rem); font-weight:700; margin:0 0 .35rem;">
                        {{ $vendor->display_agency_name }}
                    </h1>
                    <p class="text-muted mb-0" style="font-size:.88rem;">
                        Signup request · Submitted {{ formatDateTime($vendor->created_at) }}
                    </p>
                    <span class="badge rounded-pill bg-warning mt-2">Pending Approval</span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if ($canEditVendor)
                    <a href="{{ route('admin.vendors.edit', $vendor) }}" class="btn btn-outline-secondary btn-sm fw-semibold px-3">
                        <i class="bx bx-edit"></i> Edit
                    </a>
                    @endif
                    @if ($canManagePending)
                    <form action="{{ route('admin.vendors.pending.approve', $vendor) }}" method="POST"
                          onsubmit="return confirm('Approve this agency? The user will receive an email and can log in to the portal.');">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm fw-semibold px-3">
                            <i class="bx bx-check-circle"></i> Approve
                        </button>
                    </form>
                    <form action="{{ route('admin.vendors.pending.reject', $vendor) }}" method="POST"
                          onsubmit="return confirm('Reject and delete this signup request? This cannot be undone.');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm fw-semibold px-3">
                            <i class="bx bx-x-circle"></i> Reject
                        </button>
                    </form>
                    @endif
                </div>
            </header>

            <div class="vs-card mb-4" style="background:#fff; border:1px solid #ebecf0; border-radius:12px; padding:1.25rem;">
                <div class="row g-4">
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted" style="font-size:.72rem; text-transform:uppercase; font-weight:600;">Name</div>
                                <div>{{ $vendor->contact_name ?: '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted" style="font-size:.72rem; text-transform:uppercase; font-weight:600;">Designation</div>
                                <div>{{ $vendor->designation ?: '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted" style="font-size:.72rem; text-transform:uppercase; font-weight:600;">Email</div>
                                <div>{{ $vendor->email }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted" style="font-size:.72rem; text-transform:uppercase; font-weight:600;">Username</div>
                                <div>{{ $vendor->username }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted" style="font-size:.72rem; text-transform:uppercase; font-weight:600;">Agent Code</div>
                                <div><code>{{ $vendor->agent_code }}</code></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted" style="font-size:.72rem; text-transform:uppercase; font-weight:600;">Trade License</div>
                                <div>{{ $vendor->trade_license_number ?: '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted" style="font-size:.72rem; text-transform:uppercase; font-weight:600;">License Expiry</div>
                                <div>{{ $vendor->trade_license_expiry ? $vendor->trade_license_expiry->format('d M Y') : '—' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="text-muted mb-2" style="font-size:.72rem; text-transform:uppercase; font-weight:600;">Agency Logo</div>
                        @if ($vendor->agency_logo)
                            <img src="{{ asset($vendor->agency_logo) }}" alt="Agency Logo"
                                style="max-height:100px; max-width:180px; object-fit:contain; border-radius:8px; border:1px solid #eee;">
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.vendors.pending.index') }}" class="text-muted" style="font-size:13px;">← Back to signup requests</a>
        </div>
    </div>
@endsection
