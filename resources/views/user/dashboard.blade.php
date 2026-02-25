@extends('user.layouts.main')

@section('content')
    <div class="dashboard-content">
        <div class="quick-actions-section">
            <div class="section-content mb-4">
                <h3 class="heading">Quick Actions</h3>
            </div>
            <div class="quick-actions-grid">
                <a href="{{ route('user.profile.personalInfo') }}" class="quick-action-item">
                    <div class="quick-action-circle">
                        <i class='bx bxs-user'></i>
                    </div>
                    <span class="quick-action-label">Profile</span>
                </a>
                <a href="{{ route('user.profile.changePassword') }}" class="quick-action-item">
                    <div class="quick-action-circle">
                        <i class='bx bxs-lock-alt'></i>
                    </div>
                    <span class="quick-action-label">Change Password</span>
                </a>
                <a href="{{ route('user.wallet.recharge') }}" class="quick-action-item">
                    <div class="quick-action-circle">
                        <i class='bx bxs-wallet'></i>
                    </div>
                    <span class="quick-action-label">Recharge Wallet</span>
                </a>
                <a href="{{ route('user.bookings.index') }}" class="quick-action-item">
                    <div class="quick-action-circle">
                        <i class='bx bxs-book-content'></i>
                    </div>
                    <span class="quick-action-label">My Bookings</span>
                </a>
            </div>
        </div>
    </div>
@endsection
