@php($socialLinks = \App\Support\SocialMediaConfig::links())
@if ($socialLinks !== [])
    <div class="footer-social-icons">
        @foreach ($socialLinks as $social)
            <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                class="social-link" aria-label="{{ ucfirst($social['label']) }}">
                <i class="{{ $social['icon'] }}"></i>
            </a>
        @endforeach
    </div>
@endif
