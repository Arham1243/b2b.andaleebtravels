<div id="page-progress"></div>

<header class="mh-header">
    <div class="container">
        <div class="mh-row">

            <!-- Logo -->
            <a href="{{ route('frontend.index') }}" class="mh-logo">
                <img src="{{ isset($config['SITE_LOGO']) ? asset($config['SITE_LOGO']) : asset('frontend/assets/images/logo.webp') }}"
                    alt="Andaleeb Travel Agency" />
            </a>

            <!-- Navigation -->
            <nav class="mh-nav">
                <ul class="mh-nav-list">
                    <li><a href="{{ route('frontend.about-us') }}">About Us </a></li>
                    <li><a href="{{ route('frontend.uae-services') }}">Dubai Tours</a></li>
                    <li><a href="{{ route('frontend.packages.index') }}">Holidays</a></li>
                    <li><a href="{{ route('frontend.hotels.index') }}">Hotels</a></li>
                    <li><a href="{{ route('frontend.travel-insurance.index') }}">Insurance</a></li>
                    <li><a href="{{ route('frontend.contact-us') }}">Contact Us </a></li>
                </ul>
            </nav>

            <!-- Actions -->
            <div class="mh-actions">

                <!-- Contact -->
                <div class="mh-contact-group">
                    <a href="tel:{{ $config['WHATSAPP'] ?? '+971525748986' }}" class="mh-link">
                        <i class="bx bxl-whatsapp"></i>
                        <span>WhatsApp</span>
                    </a>
                    <a href="tel:{{ $config['COMPANYPHONE'] ?? '+97145766068' }}" class="mh-btn-primary">
                        <i class="bx bx-phone"></i>
                        <span>Helpline</span>
                    </a>
                </div>

                <span class="mh-divider"></span>

                <!-- User/Cart -->
                <div class="mh-icons-group">
                    <a href="{{ route('auth.login') }}" class="mh-icon-link">
                        <i class="bx bx-user"></i>
                    </a>
                    @php
                        $cartData = session()->get('cart', ['tours' => []]);
                    @endphp
                    <a href="{{ route('frontend.cart.index') }}" class="mh-icon-link mh-cart">
                        <i class='bx bx-shopping-bag'></i>
                        <span class="mh-badge">{{ count($cartData['tours'] ?? []) }}</span>
                    </a>
                    <a href="javascript:void(0);" class="mh-icon-link menu-btn" data-menu-button
                        onclick="openSideBar()">
                        <i class="bx bx-menu"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>
</header>

<div class="sideBar" id="sideBar">
    <a href="javascript:void(0)" class="sideBar__close" onclick="closeSideBar()">×</a>
    <a href="{{ route('frontend.index') }}" class="sideBar__logo">
        <img src="{{ isset($config['SITE_LOGO']) ? asset($config['SITE_LOGO']) : asset('frontend/assets/images/logo.webp') }}"
            alt="Logo" class="imgFluid">
    </a>
    <ul class="sideBar__nav">
        <li><a href="{{ route('frontend.about-us') }}">About Us </a></li>
        <li><a href="{{ route('frontend.uae-services') }}">Dubai Tour Packages</a></li>
        <li><a href="{{ route('frontend.packages.index') }}">Holiday Packages</a></li>
        <li><a href="{{ route('frontend.hotels.index') }}">Hotels</a></li>
        <li><a href="{{ route('frontend.travel-insurance.index') }}">Travel Insurance</a></li>
        <li><a href="{{ route('frontend.contact-us') }}">Contact Us</a></li>
    </ul>
    <div class="sidebar-btns-wrapper">
        <a href="tel:{{ $config['COMPANYPHONE'] ?? '+971 45766068' }}" class="themeBtn"><i
                class="bx bx-phone"></i>Helpline</a>
        <a href="tel:{{ $config['WHATSAPP'] ?? '+971 525748986' }}" class="themeBtn"><i
                class="bx bxl-whatsapp"></i>WhatsApp</a>
    </div>
</div>


<div class="first-section"></div>
