@extends('user.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('user.sub-agents.index') }}
            <div class="table-container universal-table">
                <div class="custom-sec">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">Sub Agents</h3>
                        </div>
                        <a href="{{ route('user.sub-agents.create') }}" class="themeBtn">
                            <i class="bx bx-plus"></i> Add Sub Agent
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Agent Code</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subAgents as $agent)
                                    <tr>
                                        <td>{{ $agent->name }}</td>
                                        <td>{{ $agent->email }}</td>
                                        <td>{{ $agent->username }}</td>
                                        <td>{{ $agent->agent_code }}</td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $agent->status === 'active' ? 'success' : 'danger' }}">
                                                {{ $agent->status === 'active' ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ formatDateTime($agent->created_at) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">No sub agents found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
