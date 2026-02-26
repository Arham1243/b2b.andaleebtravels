@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.vendors.index') }}
            <form id="bulkActionForm" method="POST" action="{{ route('admin.bulk-actions', ['resource' => 'vendors']) }}">
                @csrf
                <div class="table-container universal-table">
                    <div class="custom-sec">
                        <div class="custom-sec__header">
                            <div class="section-content">
                                <h3 class="heading">Manage Vendors</h3>
                            </div>
                            <a href="{{ route('admin.vendors.create') }}" class="themeBtn">
                                <i class="bx bx-plus"></i> Add Vendor
                            </a>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-5">
                                <form class="custom-form">
                                    <div class="form-fields d-flex gap-3">
                                        <select class="field" id="bulkActions" name="bulk_actions" required>
                                            <option value="" disabled selected>Bulk Actions</option>
                                            <option value="active">Make Active</option>
                                            <option value="inactive">Make Inactive</option>
                                            <option value="delete">Delete</option>
                                        </select>
                                        <button type="submit" onclick="confirmBulkAction(event)"
                                            class="themeBtn">Apply</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th class="no-sort">
                                            <div class="selection select-all-container">
                                                <input type="checkbox" id="select-all">
                                            </div>
                                        </th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Username</th>
                                        <th>Agent Code</th>
                                        <th>Wallet Balance</th>
                                        <th>Status</th>
                                        <th>Registration Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($vendors as $vendor)
                                        <tr>
                                            <td>
                                                <div class="selection item-select-container">
                                                    <input type="checkbox" class="bulk-item" name="bulk_select[]"
                                                        value="{{ $vendor->id }}">
                                                </div>
                                            </td>
                                            <td>{{ $vendor->name }}</td>
                                            <td>{{ $vendor->email }}</td>
                                            <td>{{ $vendor->username }}</td>
                                            <td>{{ $vendor->agent_code }}</td>
                                            <td>{!! formatPrice($vendor->main_balance ?? 0) !!}</td>
                                            <td>
                                                <span
                                                    class="badge rounded-pill bg-{{ $vendor->status === 'active' ? 'success' : 'danger' }}">
                                                    {{ $vendor->status === 'active' ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>{{ formatDateTime($vendor->created_at) }}</td>
                                            <td>
                                                <div class="dropstart">
                                                    <button type="button" class="recent-act__icon dropdown-toggle"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class='bx bx-dots-vertical-rounded'></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('admin.vendors.show', $vendor->id) }}">
                                                                <i class="bx bx-show"></i> View Details
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('admin.vendors.change-status', $vendor->id) }}">
                                                                <i
                                                                    class="bx {{ $vendor->status === 'active' ? 'bx-x' : 'bx-check' }}"></i>
                                                                {{ $vendor->status === 'active' ? 'Make Inactive' : 'Make Active' }}
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form
                                                                action="{{ route('admin.vendors.destroy', $vendor->id) }}"
                                                                method="POST"
                                                                onsubmit="return confirm('Are you sure you want to delete this vendor?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="bx bx-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
