<nav class="topbar">
    <div class="topbar-inner">
        <!-- Logo -->
        <a href="{{ route('user.dashboard') }}" class="topbar-logo">
            <img src='{{ isset($config['SITE_LOGO']) ? asset($config['SITE_LOGO']) : asset('frontend/assets/images/logo.webp') }}' alt='logo'>
        </a>

        <!-- Navigation Menu -->
        <ul class="topbar-nav">
            <li class="topbar-nav__item">
                <a href="javascript:void(0)" class="topbar-nav__link">
                    <i class='bx bxs-plane-alt'></i> Flights
                </a>
            </li>
            <li class="topbar-nav__item">
                <a href="{{ route('user.hotels.index') }}" class="topbar-nav__link">
                    <i class='bx bxs-building-house'></i> Hotels
                </a>
            </li>
            <li class="topbar-nav__item">
                <a href="javascript:void(0)" class="topbar-nav__link">
                    <i class='bx bxs-shield-plus'></i> Travel Insurance
                </a>
            </li>
            <li class="topbar-nav__item">
                <a href="javascript:void(0)" class="topbar-nav__link">
                    <i class='bx bxs-map'></i> Activities
                </a>
            </li>
            <li class="topbar-nav__item">
                <a href="javascript:void(0)" class="topbar-nav__link">
                    <i class='bx bxs-sun'></i> Holidays
                </a>
            </li>
        </ul>

        <!-- Right Section -->
        <div class="topbar-right">
            <!-- Balance Pill -->
            <a href="#" class="mh-balance-pill">
                <div class="mh-balance-icon">
                    <i class='bx bxs-wallet'></i>
                </div>
                <div class="mh-balance-info">
                    <span class="mh-balance-label">Main Balance</span>
                    <span class="mh-balance-amount">{!! formatPrice(Auth::user()->main_balance) !!}</span>
                </div>
            </a>

            <!-- Agency Info + Dropdown -->
            <div class="topbar-agency dropdown">
                <div class="topbar-agency__info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                    <div class="topbar-agency__avatar">
                        @if (Auth::user()->avatar)
                            <img src="{{ asset(Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
                        @else
                            <i class='bx bxs-user-circle'></i>
                        @endif
                    </div>
                    <div class="topbar-agency__details">
                        <span class="topbar-agency__name">{{ Auth::user()->name }}</span>
                        <span class="topbar-agency__code">{{ Auth::user()->agent_code ?? 'N/A' }}</span>
                    </div>
                    <i class='bx bx-chevron-down topbar-agency__arrow'></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a href="{{ route('user.profile.personalInfo') }}" class="dropdown-item">
                            <i class='bx bx-user'></i> Profile
                        </a>
                    </li>
                    @if (auth()->user()->auth_provider !== 'google')
                        <li>
                            <a href="{{ route('user.profile.changePassword') }}" class="dropdown-item">
                                <i class='bx bx-lock-alt'></i> Change Password
                            </a>
                        </li>
                    @endif
                    <li class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('user.logout') }}" method="POST">
                            @csrf
                            <button onclick="return confirm('Are you sure you want to logout?')" type="submit"
                                class="dropdown-item text-danger">
                                <i class='bx bx-log-out'></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Mobile Toggle -->
        <button class="topbar-mobile-toggle" id="topbarMobileToggle">
            <i class='bx bx-menu'></i>
        </button>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('topbarMobileToggle');
        const nav = document.querySelector('.topbar-nav');
        if (toggle && nav) {
            toggle.addEventListener('click', function() {
                nav.classList.toggle('show');
            });
        }
    });
</script>
