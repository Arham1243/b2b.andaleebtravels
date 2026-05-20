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

            <div class="mh-user-area {{ Auth::check() ? 'mh-user-area--logged' : 'mh-user-area--guest' }}">
                @auth
                    <a href="{{ route('user.dashboard') }}" class="themeBtn themeBtn--primary">
                        <i class="bx bx-grid-alt"></i> Portal
                    </a>
                @else
                    <a href="{{ route('auth.signup') }}" class="themeBtn themeBtn--outline">Sign Up</a>
                    <a href="{{ route('auth.login') }}" class="themeBtn themeBtn--primary">Login</a>
                @endauth
            </div>
        </div>
    </div>
</header>

<div class="first-section"></div>
