@extends('user.layouts.main')

@section('css')
    @include('user.profile-settings._styles')
@endsection

@section('content')
<div class="ps">
    <div class="container">

        <div class="ps-page-head">
            <div class="ps-page-head__icon"><i class="bx bx-user-plus"></i></div>
            <div>
                <h1 class="ps-page-head__title">Account Settings</h1>
                <p class="ps-page-head__sub">Manage your profile</p>
            </div>
        </div>

        <div class="ps-shell">
            @include('user.profile-settings._sidebar')

            <main class="ps-main">
                <div class="ps-card">
                    <div class="ps-card__head">
                        <h2 class="ps-card__title">
                            <i class="bx bx-group"></i> Sub Agents
                        </h2>
                        <a href="{{ route('user.sub-agents.create') }}" class="ps-btn-save">
                            <i class="bx bx-plus"></i> Add Sub Agent
                        </a>
                    </div>
                    <div class="ps-card__body" style="padding:16px 20px;">
                        <div class="table-responsive">
                            <table class="data-table" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Username</th>
                                        <th>Agent Code</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th class="no-sort">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subAgents as $agent)
                                        <tr>
                                            <td>{{ $agent->name }}</td>
                                            <td>{{ $agent->email }}</td>
                                            <td>{{ $agent->username }}</td>
                                            <td><code>{{ $agencyAgentCode ?? $agent->loginAgentCode() }}</code></td>
                                            <td>
                                                <span class="badge rounded-pill bg-{{ $agent->status === 'active' ? 'success' : 'danger' }}">
                                                    {{ $agent->status === 'active' ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>{{ formatDateTime($agent->created_at) }}</td>
                                            <td>
                                                <a href="{{ route('user.sub-agents.edit', $agent) }}" class="ps-btn-save" style="font-size:.78rem; padding:.3rem .65rem;">
                                                    <i class="bx bx-edit-alt"></i> Edit
                                                </a>
                                                <form action="{{ route('user.sub-agents.destroy', $agent) }}" method="POST" class="d-inline"
                                                    onsubmit="return confirm('Delete this sub agent? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ps-btn-save" style="font-size:.78rem; padding:.3rem .65rem; background:#fef2f2; color:#b91c1c; border-color:#fecaca;">
                                                        <i class="bx bx-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection
