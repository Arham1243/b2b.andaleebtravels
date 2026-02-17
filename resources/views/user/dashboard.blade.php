@extends('user.layouts.main')

@section('content')
    <div class="col-md-9">
        <div class="dashboard-content">
            <div class="revenue">
                <div class="custom-sec custom-sec--form mt-4">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">Quick Links</h3>
                        </div>
                    </div>
                </div>

                <div class="row">
                    
                <div class="col-md-6">
                    <a href="{{ route('user.orders.index') }}" class="revenue-card mt-0">
                        <div class="revenue-card__icon">
                            <i class='bx bxs-shopping-bag'></i>
                        </div>
                        <div class="revenue-card__content">
                            <div class="title">View my Orders</div>
                            <div class="num">{{ $orders->count() }}</div>
                        </div>
                    </a>
                </div>
                    @if (auth()->user()->auth_provider !== 'google')
                        <div class="col-md-6">
                            <a href="{{ route('user.profile.changePassword') }}" class="revenue-card mt-0">
                                <div class="revenue-card__icon">
                                    <i class='bx bx-lg bxs-calendar-check'></i>
                                </div>
                                <div class="revenue-card__content">
                                    <div class="title">Account Settings</div>
                                    <div class="num">Change Password</div>
                                </div>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
