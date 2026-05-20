@extends('user.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('user.sub-agents.edit', $subAgent) }}
            <form action="{{ route('user.sub-agents.update', $subAgent) }}" method="POST" id="validation-form">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-wrapper">
                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">Edit Sub Agent</div>
                                </div>
                                <div class="form-box__body">
                                    @include('admin.vendors.partials._sub-agent-agent-code', ['agencyAgentCode' => $agencyAgentCode ?? ''])

                                    <div class="form-fields">
                                        <label class="title">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="field" value="{{ old('name', $subAgent->name) }}" required>
                                        @error('name')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-fields">
                                        <label class="title">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="field" value="{{ old('email', $subAgent->email) }}" required>
                                        @error('email')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-fields">
                                        <label class="title">Username <span class="text-danger">*</span></label>
                                        <input type="text" name="username" class="field" value="{{ old('username', $subAgent->username) }}" required>
                                        @error('username')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-fields">
                                        <label class="title">Password</label>
                                        <input type="password" name="password" class="field" placeholder="Leave empty to keep current" autocomplete="new-password">
                                        @error('password')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="seo-wrapper">
                            <div class="form-box">
                                <div class="form-box__header">
                                    <div class="title">Status</div>
                                </div>
                                <div class="form-box__body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="active" value="active"
                                            {{ old('status', $subAgent->status) === 'active' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">Active</label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="status" id="inactive" value="inactive"
                                            {{ old('status', $subAgent->status) === 'inactive' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="inactive">Inactive</label>
                                    </div>

                                    <div class="text-end mt-4">
                                        <button class="themeBtn" type="submit">Save Changes</button>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <a href="{{ route('user.sub-agents.index') }}" class="text-muted" style="font-size:13px;">← Back to sub agents</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
