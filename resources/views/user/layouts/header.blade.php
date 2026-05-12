@php
    $vendor = Auth::user();
    $agencyTitle = $vendor->name ?: config('app.name');
@endphp
<nav class="topbar" id="userTopbar">
    {{-- Tier 1: agency + balance + account --}}
    <div class="topbar-row topbar-row--meta">
        <div class="container topbar-meta-container">
            <div class="topbar-meta-inner">
                <a href="{{ route('user.dashboard') }}" class="topbar-brand-title">
                    {{ mb_strtoupper($agencyTitle, 'UTF-8') }}
                </a>

                <div class="topbar-meta-right">
                    <div class="topbar-balance-cluster">
                        <button type="button" class="topbar-balance-refresh" id="topbarBalanceRefresh" title="{{ __('Refresh') }}" aria-label="Refresh balance">
                            <i class='bx bx-revision'></i>
                        </button>
                        <a href="{{ route('user.wallet.recharge') }}" class="topbar-balance-link">
                            <div class="topbar-balance-label-row">
                                <span class="topbar-balance-label">Main Balance</span>
                                <i class='bx bx-chevron-down'></i>
                            </div>
                            <span class="topbar-balance-value">{!! formatPrice($vendor->main_balance ?? 0) !!}</span>
                        </a>
                    </div>

                    <div class="topbar-divider-vertical" aria-hidden="true"></div>

                    <div class="topbar-agency dropdown">
                        <div class="topbar-agency__info topbar-agency__info--compact dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                            <div class="topbar-agency__details">
                                <span class="topbar-agency__name">{{ $vendor->name }}</span>
                                <span class="topbar-agency__code">{{ __('Agent Code') }}: {{ $vendor->agent_code ?? 'N/A' }}</span>
                            </div>
                            <div class="topbar-agency__avatar">
                                @if ($vendor->avatar)
                                    <img src="{{ asset($vendor->avatar) }}" alt="{{ $vendor->name }}">
                                @else
                                    <i class='bx bxs-user-circle'></i>
                                @endif
                            </div>
                            <i class='bx bx-chevron-down topbar-agency__arrow'></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="{{ route('user.profile.personalInfo') }}" class="dropdown-item">
                                    <i class='bx bx-user'></i> {{ __('Profile') }}
                                </a>
                            </li>
                            @if (auth()->user()->auth_provider !== 'google')
                                <li>
                                    <a href="{{ route('user.profile.changePassword') }}" class="dropdown-item">
                                        <i class='bx bx-lock-alt'></i> {{ __('Change Password') }}
                                    </a>
                                </li>
                            @endif
                            <li class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('user.logout') }}" method="POST">
                                    @csrf
                                    <button onclick="return confirm('Are you sure you want to logout?')" type="submit" class="dropdown-item">
                                        <i class='bx bx-log-out'></i> {{ __('Logout') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>

                    <button class="topbar-mobile-toggle" id="topbarMobileToggle" type="button" aria-label="Menu" aria-expanded="false" aria-controls="userTopbarNav">
                        <i class='bx bx-menu'></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tier 2: primary navigation --}}
    <div class="topbar-row topbar-row--nav">
        <div class="container topbar-nav-container">
            <div class="topbar-nav-wrap">
                <ul class="topbar-nav" id="userTopbarNav">
                    <li class="topbar-nav__item">
                        <a href="{{ route('user.flights.index') }}" class="topbar-nav__link {{ request()->routeIs('user.flights.*') ? 'active' : '' }}">
                            <i class='bx bxs-plane-alt topbar-nav__icon'></i>
                            <span class="topbar-nav__label">{{ __('Flights') }}</span>
                        </a>
                    </li>
                    <li class="topbar-nav__item">
                        <a href="{{ route('user.hotels.index') }}" class="topbar-nav__link {{ request()->routeIs('user.hotels.*') ? 'active' : '' }}">
                            <i class='bx bxs-building-house topbar-nav__icon'></i>
                            <span class="topbar-nav__label">{{ __('Hotels') }}</span>
                        </a>
                    </li>
                    <li class="topbar-nav__item topbar-nav__item--badge">
                        <a href="javascript:void(0)" class="topbar-nav__link">
                            <span class="topbar-nav__badge">New</span>
                            <i class='bx bxs-shield-plus topbar-nav__icon'></i>
                            <span class="topbar-nav__label">{{ __('Insurance') }}</span>
                        </a>
                    </li>
                    <li class="topbar-nav__item">
                        <a href="javascript:void(0)" class="topbar-nav__link">
                            <i class='bx bxs-map topbar-nav__icon'></i>
                            <span class="topbar-nav__label">{{ __('Activities') }}</span>
                        </a>
                    </li>
                    <li class="topbar-nav__item">
                        <a href="javascript:void(0)" class="topbar-nav__link">
                            <i class='bx bxs-sun topbar-nav__icon'></i>
                            <span class="topbar-nav__label">{{ __('Holidays') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const topbar = document.getElementById('userTopbar');
        const toggle = document.getElementById('topbarMobileToggle');
        const nav = document.getElementById('userTopbarNav');
        const refreshBal = document.getElementById('topbarBalanceRefresh');

        function setNavOpen(open) {
            if (!nav) return;
            nav.classList.toggle('show', open);
            if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        if (toggle && nav) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                setNavOpen(!nav.classList.contains('show'));
            });
            nav.querySelectorAll('.topbar-nav__link').forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.matchMedia('(max-width: 576px)').matches) {
                        setNavOpen(false);
                    }
                });
            });
        }

        document.addEventListener('click', function(e) {
            if (!topbar || !nav || !toggle || !nav.classList.contains('show')) return;
            if (!window.matchMedia('(max-width: 576px)').matches) return;
            if (toggle.contains(e.target) || nav.contains(e.target)) return;
            setNavOpen(false);
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && nav && nav.classList.contains('show')) setNavOpen(false);
        });

        let resizeNavTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeNavTimer);
            resizeNavTimer = setTimeout(function() {
                if (nav && !window.matchMedia('(max-width: 576px)').matches) {
                    setNavOpen(false);
                }
            }, 150);
        });

        if (refreshBal) {
            refreshBal.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.reload();
            });
        }
    });
</script>
