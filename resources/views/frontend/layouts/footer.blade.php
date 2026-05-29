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
                    @include('partials.social-media-links')
                </div>
            </div>
        </div>
    </div>
</footer>
