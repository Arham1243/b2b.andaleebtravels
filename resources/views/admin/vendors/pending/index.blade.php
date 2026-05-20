@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.vendors.pending.index') }}
            <div class="table-container universal-table">
                <div class="custom-sec">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">Signup Requests</h3>
                            <p class="text-muted mb-0" style="font-size:13px;">Agency registrations awaiting approval</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Travel Agency</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Agent Code</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vendors as $vendor)
                                    <tr>
                                        <td>{{ $vendor->display_agency_name }}</td>
                                        <td>{{ $vendor->contact_name ?: '—' }}</td>
                                        <td>{{ $vendor->email }}</td>
                                        <td>{{ $vendor->username }}</td>
                                        <td><code>{{ $vendor->agent_code }}</code></td>
                                        <td>{{ formatDateTime($vendor->created_at) }}</td>
                                        <td>
                                            <a href="{{ route('admin.vendors.pending.show', $vendor) }}" class="themeBtn" style="font-size:.78rem; padding:.25rem .65rem;">
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
