@extends('admin.layouts.main')
@php
    extract(
        \App\Support\B2bAdminPortalUi::crud([
            'add' => 'portal_admins_add',
            'edit' => 'portal_admins_edit',
            'delete' => 'portal_admins_delete',
        ]),
    );
@endphp
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.portal-users.index') }}
            <div class="table-container universal-table">
                <div class="custom-sec">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">{{ $title }}</h3>
                        </div>
                        @if ($canAdd)
                            <a href="{{ route('admin.portal-users.create') }}" class="themeBtn">Add Administrator</a>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="no-sort">Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    @if ($showRowActions)
                                        <th class="no-sort">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($admins as $row)
                                    <tr>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ $row->email }}</td>
                                        <td>
                                            <span
                                                class="badge rounded-pill bg-{{ $row->adminRole?->is_super ? 'dark' : 'secondary' }}">
                                                {{ $row->adminRole?->name ?? ' - ' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $row->isPortalActive() ? 'success' : 'danger' }}">
                                                {{ $row->isPortalActive() ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        @if ($showRowActions)
                                            <td>
                                                <div class="dropstart">
                                                    <button type="button" class="recent-act__icon dropdown-toggle"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class='bx bx-dots-vertical-rounded'></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        @if ($canEdit)
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="{{ route('admin.portal-users.edit', $row) }}">
                                                                    <i class="bx bx-edit-alt"></i>
                                                                    Edit
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if ($canDelete)
                                                            <li>
                                                                <form action="{{ route('admin.portal-users.destroy', $row) }}"
                                                                    method="POST"
                                                                    onsubmit="return confirm('Remove this administrator?');">
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
                                            </td>
                                        @endif
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
