<div id="page-progress"></div>
<a target="_blank"
    href="https://api.whatsapp.com/send?phone={{ $config['WHATSAPP_NUMBER'] ?? '+971525748986' }}&amp;text=I%27m%20interested%20in%20your%20services"
    class="whatsapp-contact" style="display: flex !important;">
    <i class="bx bxl-whatsapp"></i>
</a>

<header class="mh-header">
    <div class="container">
        <div class="mh-row">
            <a href="{{ route('frontend.index') }}" class="mh-logo">
                <img src="{{ isset($config['SITE_LOGO']) ? asset($config['SITE_LOGO']) : asset('frontend/assets/images/logo.webp') }}"
                    alt="Andaleeb Travel Agency" />
            </a>

            <nav class="mh-nav">
                <ul class="mh-nav-list">
                    <li><a href="">Flights</a></li>
                    <li><a href="">Hotels</a></li>
                    <li><a href="">Activities</a></li>
                    <li><a href="">Travel Insurance </a></li>
                    <li><a href="">Holidays</a></li>
                </ul>
            </nav>



            <div class="mh-user-area">
                <a href="javascript:void(0);" class="mh-icon-link menu-btn" data-menu-button onclick="openSideBar()">
                    <i class="bx bx-menu"></i>
                </a>
                @if (Auth::check())
                    <a href="#" class="mh-balance-pill">
                        <div class="mh-balance-icon">
                            <i class='bx bxs-wallet'></i>
                        </div>
                        <div class="mh-balance-info">
                            <span class="mh-balance-label">Balance</span>
                            <span class="mh-balance-amount">{!! formatPrice(Auth::user()->main_balance) !!}</span>
                        </div>
                    </a>
                    <a href="{{ route('user.dashboard') }}" class="mh-agency-profile">
                        <div class="mh-agency-logo">
                            <img src="{{ asset(Auth::user()->avatar ?? 'frontend/assets/images/favicon.ico') }}" alt="Agency">
                        </div>
                        <div class="mh-agency-info">
                            <span class="mh-agency-name">{{ Auth::user()->name }}</span>
                            <span class="mh-agency-code">Agent Code: {{ Auth::user()->agent_code }}</span>
                        </div>
                    </a>
                @else
                    <a href="{{ route('auth.login') }}" class="themeBtn themeBtn--primary">Login</a>
                @endif
            </div>
        </div>
    </div>
</header>

<div class="sideBar" id="sideBar">
    <a href="javascript:void(0)" class="sideBar__close" onclick="closeSideBar()">×</a>
    <a href="{{ route('frontend.index') }}" class="sideBar__logo">
        <img src="{{ isset($config['SITE_LOGO']) ? asset($config['SITE_LOGO']) : asset('frontend/assets/images/logo.webp') }}"
            alt="Andaleeb Travel Agency" />
    </a>
    <ul class="sideBar__nav">
        <li><a href="">Flights</a></li>
        <li><a href="">Hotels</a></li>
        <li><a href="">Activities</a></li>
        <li><a href="">Travel Insurance </a></li>
        <li><a href="">Holidays</a></li>
    </ul>
    @if (!Auth::check())
        <a href="{{ route('auth.login') }}" class="themeBtn themeBtn--primary">Login</a>
    @endif
</div>

<div class="first-section"></div>
