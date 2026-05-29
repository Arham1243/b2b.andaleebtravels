@extends('admin.layouts.main')
@php
    extract(
        \App\Support\B2bAdminPortalUi::crud([
            'add' => 'vendors_add',
            'edit' => 'vendors_edit',
            'delete' => 'vendors_delete',
            'view' => 'vendors_view',
        ]),
    );
@endphp
@push('css')
    <style>
        .vendor-list__title {
            font-weight: 600;
            color: inherit;
            text-decoration: none;
        }
        .vendor-list__title:hover {
            text-decoration: underline;
        }
        .vendor-list__meta {
            display: block;
            font-size: 12px;
            color: #6b6573;
            line-height: 1.35;
            margin-top: 2px;
        }
        .vendor-list__code {
            font-size: 13px;
            font-weight: 600;
        }
    </style>
@endpush
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
                            @if ($canAdd)
                                <a href="{{ route('admin.vendors.create') }}" class="themeBtn">
                                    <i class="bx bx-plus"></i> Add Vendor
                                </a>
                            @endif
                        </div>
                        @if ($canBulk)
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
                        @endif
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th class="no-sort">
                                            <div class="selection select-all-container">
                                                <input type="checkbox" id="select-all">
                                            </div>
                                        </th>
                                        <th>Agency</th>
                                        <th>Login</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        @if ($showRowActions)
                                            <th class="no-sort">Actions</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($vendors as $vendor)
                                        @php
                                            $agencyName = $vendor->display_agency_name ?: $vendor->name;
                                            $statusClass = match ($vendor->status) {
                                                'active' => 'success',
                                                'inactive' => 'secondary',
                                                default => 'warning',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="selection item-select-container">
                                                    <input type="checkbox" class="bulk-item" name="bulk_select[]"
                                                        value="{{ $vendor->id }}">
                                                </div>
                                            </td>
                                            <td>
                                                @if ($canDetail)
                                                <a href="{{ route('admin.vendors.show', $vendor) }}"
                                                    class="vendor-list__title">{{ $agencyName }}</a>
                                                @else
                                                    <span class="vendor-list__title">{{ $agencyName }}</span>
                                                @endif
                                                @if ($vendor->contact_name)
                                                    <span class="vendor-list__meta">{{ $vendor->contact_name }}</span>
                                                @endif
                                                <span class="vendor-list__meta">{{ $vendor->email }}</span>
                                            </td>
                                            <td>
                                                <span class="vendor-list__code">{{ $vendor->agent_code }}</span>
                                                <span class="vendor-list__meta">{{ $vendor->username }}</span>
                                            </td>
                                            <td>{!! formatPrice($vendor->main_balance ?? 0) !!}</td>
                                            <td>
                                                <span class="badge rounded-pill bg-{{ $statusClass }}">
                                                    {{ formatKey($vendor->status) }}
                                                </span>
                                            </td>
                                            <td>{{ formatDate($vendor->created_at) }}</td>
                                            @if ($showRowActions)
                                            <td>
                                                <div class="dropstart">
                                                    <button type="button" class="recent-act__icon dropdown-toggle"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class='bx bx-dots-vertical-rounded'></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        @if ($canDetail)
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('admin.vendors.show', $vendor->id) }}">
                                                                <i class="bx bx-show"></i> View Details
                                                            </a>
                                                        </li>
                                                        @endif
                                                        @if ($canEdit)
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('admin.vendors.edit', $vendor->id) }}">
                                                                <i class="bx bx-edit"></i> Edit Vendor
                                                            </a>
                                                        </li>
                                                        @endif
                                                        @if ($canEdit && ! $vendor->parent_vendor_id)
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="{{ route('admin.vendors.sub-agents.create', $vendor->id) }}">
                                                                    <i class="bx bx-user-plus"></i> Add Sub Agent
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if ($canEdit)
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('admin.vendors.change-status', $vendor->id) }}">
                                                                <i
                                                                    class="bx {{ $vendor->status === 'active' ? 'bx-x' : 'bx-check' }}"></i>
                                                                {{ $vendor->status === 'active' ? 'Make Inactive' : 'Make Active' }}
                                                            </a>
                                                        </li>
                                                        @endif
                                                        @if ($canDelete)
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
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                            @endif
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
