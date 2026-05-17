@php
    $siteLogo = \App\Models\Config::where('config_key', 'SITE_LOGO')->value('config_value');
    $fallbackLogo = asset('frontend/assets/images/logo.webp');
    $bannerLogoSrc = $emailLogoSrc ?? ($siteLogo ? asset($siteLogo) : $fallbackLogo);
    $bannerLogoAlt = trim((string) ($emailLogoAlt ?? (config('app.name') ?: '')));
    if ($bannerLogoAlt === '') {
        $bannerLogoAlt = 'Travel';
    }
@endphp
<img src="{{ $bannerLogoSrc }}" alt="{{ $bannerLogoAlt }}" width="144" border="0"
    style="display:block;border:0;outline:none;text-decoration:none;margin:0 auto;height:auto;line-height:0;width:144px;max-width:76%;max-height:42px;" />
