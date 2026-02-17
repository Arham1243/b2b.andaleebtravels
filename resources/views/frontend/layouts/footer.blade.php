<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-12 mb-4">
                <a href="{{ route('frontend.index') }}" class="footer-logo">
                    <img src="{{ isset($config['SITE_LOGO']) ? asset($config['SITE_LOGO']) : asset('frontend/assets/images/logo.webp') }}"
                        alt="Rayna Logo" class="main-logo" />
                </a>

                <h6 class="heading">Book with confidence</h6>
                <div class="award-images">
                    <img src="{{ asset('frontend/assets/images/partners/4.svg') }}" alt="Image" class="award-img" />
                    <img src="{{ asset('frontend/assets/images/partners/2.png') }}" alt="Image" class="award-img" />
                    <img src="{{ asset('frontend/assets/images/partners/3.png') }}" alt="Image" class="award-img" />
                    <img src="{{ asset('frontend/assets/images/partners/1.svg') }}" alt="Image"
                        class="award-img award-img--lg" />
                </div>
            </div>
            <div class="col-lg-2 offset-lg-2 col-md-6 mb-4">
                <h6 class="heading">Quick Links</h6>
                <ul class="footer-list">
                    <li><a href="{{ route('frontend.about-us') }}">About us</a></li>
                    <li><a href="{{ route('frontend.contact-us') }}">Contact us</a></li>
                    <li><a href="{{ route('frontend.company-profile') }}">Company Profile</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="heading">Explore Options</h6>
                <ul class="footer-list">
                    <li><a href="{{ route('frontend.uae-services') }}">Dubai Tours</a></li>
                    <li><a href="{{ route('frontend.packages.index') }}">Holidays</a></li>
                    <li><a href="{{ route('frontend.hotels.index') }}">Hotels</a></li>
                    <li><a href="{{ route('frontend.travel-insurance.index') }}">Insurance</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <h6 class="heading">Get Help 24/7</h6>
                <ul class="footer-list">
                    <li>
                        <a href="mailto:{{ $config['COMPANYEMAIL'] ?? 'info@andaleebtours.com' }}">
                            <i class="bx bxs-envelope"></i>
                            <span>{{ $config['COMPANYEMAIL'] ?? 'info@andaleebtours.com' }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="tel:{{ $config['COMPANYPHONE'] ?? '+97145766068' }}">
                            <i class="bx bxs-phone"></i>
                            <span>{{ $config['COMPANYPHONE'] ?? '+971 45766068' }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="tel:{{ $config['WHATSAPP'] ?? '+971525748986' }}">
                            <i class="bx bxl-whatsapp"></i>
                            <span>{{ $config['WHATSAPP'] ?? '+971 525748986' }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="copyright-text">
                        <span>{{ $config['COPYRIGHT'] ?? '© ' . date('Y') . ' Andaleeb Travel Agency. All Rights Reserved.' }}
                        </span>
                        <div class="copyright-links">
                            <a href="{{ route('frontend.privacy-policy') }}">Privacy Policy</a>
                            <span style="color: #ddd">|</span>
                            <a href="{{ route('frontend.terms-and-conditions') }}">Terms & conditions</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="footer-social-icons">
                        <a href="{{ $config['FACEBOOK'] ?? 'https://www.facebook.com/AndaleebTravelAgency' }}"
                            target="_blank" class="social-link"><i class="bx bxl-facebook"></i></a>
                        <a href="{{ $config['TWITTER'] ?? 'https://twitter.com/AndaleebTravels' }}"
                            class="social-link"><i class="bx bxl-twitter"></i></a>
                        <a href="{{ $config['INSTAGRAM'] ?? 'https://www.instagram.com/andaleeb_tours/' }}"
                            class="social-link"><i class="bx bxl-instagram"></i></a>
                        <a href="{{ $config['LINKEDIN'] ?? 'https://www.linkedin.com/company/andaleeb-travel-agency/' }}"
                            class="social-link"><i class="bx bxl-linkedin"></i></a>
                        <a href="{{ $config['YOUTUBE'] ?? 'https://www.youtube.com/@AndaleebTravelAgency' }}"
                            class="social-link"><i class="bx bxl-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-disclaimer">
        {{ $config['DISCLAIMER'] ?? 'Disclaimer: Andaleeb Travel Agency is a private travel agency and not affiliated with any government body.' }}
    </div>
</footer>
