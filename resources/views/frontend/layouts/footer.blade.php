<footer class="footer">
    <div class="container">
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="copyright-text">
                        <span>{{ $config['COPYRIGHT'] ?? '© ' . date('Y') . ' Andaleeb Travel Agency. All Rights Reserved.' }}
                        </span>

                    </div>
                </div>

                <div class="col-md-5">
                    @if (isset($config['FACEBOOK']) ||
                            isset($config['TWITTER']) ||
                            isset($config['INSTAGRAM']) ||
                            isset($config['LINKEDIN']) ||
                            isset($config['YOUTUBE']))
                        <div class="footer-social-icons">
                            @if (isset($config['FACEBOOK']) && $config['FACEBOOK'])
                                <a href="{{ $config['FACEBOOK'] ?? 'https://www.facebook.com/AndaleebTravelAgency' }}"
                                    target="_blank" class="social-link"><i class="bx bxl-facebook"></i></a>
                            @endif
                            @if (isset($config['TWITTER']) && $config['TWITTER'])
                                <a href="{{ $config['TWITTER'] ?? 'https://twitter.com/AndaleebTravels' }}"
                                    class="social-link"><i class="bx bxl-twitter"></i></a>
                            @endif
                            @if (isset($config['INSTAGRAM']) && $config['INSTAGRAM'])
                                <a href="{{ $config['INSTAGRAM'] ?? 'https://www.instagram.com/andaleeb_tours/' }}"
                                    class="social-link"><i class="bx bxl-instagram"></i></a>
                            @endif
                            @if (isset($config['LINKEDIN']) && $config['LINKEDIN'])
                                <a href="{{ $config['LINKEDIN'] ?? 'https://www.linkedin.com/company/andaleeb-travel-agency/' }}"
                                    class="social-link"><i class="bx bxl-linkedin"></i></a>
                            @endif
                            @if (isset($config['YOUTUBE']) && $config['YOUTUBE'])
                                <a href="{{ $config['YOUTUBE'] ?? 'https://www.youtube.com/@AndaleebTravelAgency' }}"
                                    class="social-link"><i class="bx bxl-youtube"></i></a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</footer>
