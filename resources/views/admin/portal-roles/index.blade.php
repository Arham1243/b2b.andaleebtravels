@extends('admin.layouts.main')
@php
    extract(
        \App\Support\B2bAdminPortalUi::crud([
            'add' => 'portal_roles_add',
            'edit' => 'portal_roles_edit',
            'delete' => 'portal_roles_delete',
        ]),
    );
@endphp
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.portal-roles.index') }}
            <div class="table-container universal-table">
                <div class="custom-sec">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">{{ $title }}</h3>
                        </div>
                        @if ($canAdd)
                            <a href="{{ route('admin.portal-roles.create') }}" class="themeBtn">Add role</a>
                        @endif
                    </div>
                    <div class="table-responsive mt-4">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="no-sort">Name</th>
                                    <th>Access</th>
                                    <th class="no-sort">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $row)
                                    <tr>
                                        <td><strong>{{ $row->name }}</strong></td>
                                        <td>
                                            @if ($row->is_super)
                                                <span class="badge rounded-pill bg-dark">Super (all areas)</span>
                                            @else
                                                <span class="small text-muted">{{ count($row->permissionList()) }}
                                                    permission(s)</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($row->is_super)
                                                <span class="text-muted small"> - </span>
                                            @elseif ($showRowActions)
                                                <div class="dropstart">
                                                    <button type="button" class="recent-act__icon dropdown-toggle"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class='bx bx-dots-vertical-rounded'></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        @if ($canEdit)
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="{{ route('admin.portal-roles.edit', $row) }}">
                                                                    <i class="bx bx-edit-alt"></i>
                                                                    Edit
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if ($canDelete)
                                                            <li>
                                                                <form
                                                                    action="{{ route('admin.portal-roles.destroy', $row) }}"
                                                                    method="POST"
                                                                    onsubmit="return confirm('Delete this role?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="bx bx-trash"></i>
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
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
