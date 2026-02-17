@extends('frontend.layouts.main')
@section('content')
    @if (isset($banners) && $banners)
        <div class="banner-slider">
            @foreach ($banners as $banner)
            <div class="banner">
                <div class="banner__img">
                    <img src="{{ asset($banner->image) }}" alt="{{ $banner->heading ?? 'Banner' }}" class="imgFluid">
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="banner-slider">
            <div class="banner">
                <div class="banner__img">
                    <img src="{{ asset('frontend/assets/images/banners/1.webp') }}" alt="Image"
                        class="imgFluid">
                </div>
            </div>
            <div class="banner">
                <div class="banner__img">
                    <img src="{{ asset('frontend/assets/images/banners/2.webp') }}" alt="Image"
                        class="imgFluid">
                </div>
            </div>
            <div class="banner">
                <div class="banner__img">
                    <img src="{{ asset('frontend/assets/images/banners/4.webp') }}" alt="Image"
                        alt="Image" class="imgFluid">
                </div>
            </div>
        </div>
    @endif

    <div class="global-search">
        <div class="container">
            <div id="pills-tab" role="tablist">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <ul class="search-pills-wrapper">
                            <li role="presentation">
                                <button class="search-pill active" id="pills-home-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home"
                                    aria-selected="true">Flight</button>
                            </li>
                            <li role="presentation">
                                <button class="search-pill" id="pills-holidays-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-holidays" type="button" role="tab"
                                    aria-controls="pills-holidays" aria-selected="false">Activities</button>
                            </li>
                            <li role="presentation">
                                <button class="search-pill" id="pills-pkgs-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-pkgs" type="button" role="tab"
                                    aria-controls="pills-pkgs" aria-selected="false">Holidays </button>
                            </li>
                            <li role="presentation">
                                <button class="search-pill" id="pills-profile-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-profile" type="button" role="tab"
                                    aria-controls="pills-profile" aria-selected="false">Hotels</button>
                            </li>
                            <li role="presentation">
                                <button class="search-pill" id="pills-contact-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-contact" type="button" role="tab"
                                    aria-controls="pills-contact" aria-selected="false">Insurance</button>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane global-search-content  show active" id="pills-home" role="tabpanel"
                                aria-labelledby="pills-home-tab" tabindex="0">
                                @include('frontend.vue.main', [
                                    'appId' => 'flight-search',
                                    'appComponent' => 'flight-search',
                                    'appJs' => 'flight-search',
                                ])
                            </div>
                            <div class="tab-pane" id="pills-pkgs" role="tabpanel" aria-labelledby="pills-pkgs-tab"
                                tabindex="0">
                                @include('frontend.vue.main', [
                                    'appId' => 'packages-search',
                                    'appComponent' => 'packages-search',
                                    'appJs' => 'packages-search',
                                ])
                            </div>
                            <div class="tab-pane" id="pills-holidays" role="tabpanel" aria-labelledby="pills-holidays-tab"
                                tabindex="0">
                                @include('frontend.vue.main', [
                                    'appId' => 'activities-search',
                                    'appComponent' => 'activities-search',
                                    'appJs' => 'activities-search',
                                ])
                            </div>
                            <div class="tab-pane global-search-content " id="pills-profile" role="tabpanel"
                                aria-labelledby="pills-profile-tab" tabindex="0">
                                @include('frontend.vue.main', [
                                    'appId' => 'hotels-search',
                                    'appComponent' => 'hotels-search',
                                    'appJs' => 'hotels-search',
                                ])
                            </div>
                            <div class="tab-pane global-search-content " id="pills-contact" role="tabpanel"
                                aria-labelledby="pills-contact-tab" tabindex="0">
                                @include('frontend.vue.main', [
                                    'appId' => 'insurance-search',
                                    'appComponent' => 'insurance-search',
                                    'appJs' => 'insurance-search',
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @if($featuredCategories->isNotEmpty())
    <section class="categories categories--padd">
        <div class="container">
            <div class="section-content mb-4">
                <h3 class="heading">Handpicked Categories</h3>
            </div>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-6 g-3">
                @foreach ($featuredCategories as $featuredCategory)
                <div class="col">
                    <a href="{{ route('frontend.tour-category.details', $featuredCategory->slug) }}" class="category-card">
                        <div class="category-card__img">
                            <img src={{ asset($featuredCategory->image) }}"
                                alt="{{ $featuredCategory->name }}" class="imgFluid">
                        </div>
                        <div class="category-card__content">
                            <div class="title line-clamp-1">{{ $featuredCategory->name }}</div>
                            <div class="desc line-clamp-1">{{ $featuredCategory->tours->count() }}
                                            {{ Str::plural('Activity', $featuredCategory->tours->count()) }}</div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <section class="pro-dest-section py-5">
        <div class="container">
            <div class="row">

                <!-- LEFT: Sticky Header -->
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <div class="pro-dest">
                        <span class="pro-badge">Popular Destinations</span>
                        <h2 class="pro-heading">Experience UAE</h2>
                        <p class="pro-desc">
                            From futuristic skylines to timeless desert dunes, discover the iconic landmarks that define the
                            United
                            Arab Emirates.
                        </p>
                        <a href="{{ route('frontend.uae-services') }}" class="themeBtn">
                            View All Attractions <i class='bx bx-right-arrow-alt'></i>
                        </a>
                    </div>
                </div>

                <!-- RIGHT: The Interactive List -->
                <div class="col-lg-7 offset-lg-1">
                    <div class="pro-list-container">

                        <!-- Item 01: Downtown -->
                        <a href="{{ route('frontend.uae-services') }}" class="pro-item">
                            <span class="pro-num">01</span>
                            <div class="pro-info">
                                <h3 class="pro-city">Downtown Dubai</h3>
                                <span class="pro-cat">Burj Khalifa & The Mall</span>
                            </div>
                            <div class="pro-action">
                                <i class='bx bx-right-arrow-alt'></i>
                            </div>
                            <!-- Large Reveal Image -->
                            <div class="pro-reveal-card"
                                style="background-image: url('https://images.unsplash.com/photo-1582672060674-bc2bd808a8b5?q=80&w=800&auto=format&fit=crop');">
                                <div class="pro-card-meta">
                                    <span><i class='bx bx-map'></i> City Center</span>
                                </div>
                            </div>
                        </a>

                        <!-- Item 02: The Palm -->
                        <a href="{{ route('frontend.uae-services') }}" class="pro-item">
                            <span class="pro-num">02</span>
                            <div class="pro-info">
                                <h3 class="pro-city">Palm Jumeirah</h3>
                                <span class="pro-cat">Luxury Resorts & Beaches</span>
                            </div>
                            <div class="pro-action">
                                <i class='bx bx-right-arrow-alt'></i>
                            </div>
                            <div class="pro-reveal-card"
                                style="background-image: url('https://assets.bwbx.io/images/users/iqjWHBFdfxIU/iqbNmB9j8Z2Y/v1/2000x1092.webp');">
                                <div class="pro-card-meta">
                                    <span><i class='bx bx-map'></i> The Island</span>
                                </div>
                            </div>
                        </a>

                        <!-- Item 03: Dubai Marina -->
                        <a href="{{ route('frontend.uae-services') }}" class="pro-item">
                            <span class="pro-num">03</span>
                            <div class="pro-info">
                                <h3 class="pro-city">Dubai Marina</h3>
                                <span class="pro-cat">Yachts & Skyline Views</span>
                            </div>
                            <div class="pro-action">
                                <i class='bx bx-right-arrow-alt'></i>
                            </div>
                            <div class="pro-reveal-card"
                                style="background-image: url('https://images.unsplash.com/photo-1526495124232-a04e1849168c?q=80&w=800&auto=format&fit=crop');">
                                <div class="pro-card-meta">
                                    <span><i class='bx bx-map'></i> Waterfront</span>
                                </div>
                            </div>
                        </a>

                        <!-- Item 04: Desert Safari -->
                        <a href="{{ route('frontend.uae-services') }}" class="pro-item">
                            <span class="pro-num">04</span>
                            <div class="pro-info">
                                <h3 class="pro-city">Arabian Desert</h3>
                                <span class="pro-cat">Safari & Dunes</span>
                            </div>
                            <div class="pro-action">
                                <i class='bx bx-right-arrow-alt'></i>
                            </div>
                            <div class="pro-reveal-card"
                                style="background-image: url('https://avatars.mds.yandex.net/get-altay/15343885/2a00000196e568e5785ae838d0fdb2d8f17a/XXL_height');">
                                <div class="pro-card-meta">
                                    <span><i class='bx bx-map'></i> Adventure</span>
                                </div>
                            </div>
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </section>


    @if ($featuredTours->count() > 0)
        <section class="activities mar-y">
            <div class="container">
                <div class="section-header">
                    <div class="section-content">
                        <h3 class="heading mb-0">Top Activities & Experiences</h3>
                    </div>
                    <div class="custom-slider-arrows">
                        <div class="slick-arrow-btn activity-prev-slide"><i class='bx bx-chevron-left'></i></div>
                        <div class="slick-arrow-btn activity-next-slide"><i class='bx bx-chevron-right'></i></div>
                    </div>
                </div>

                <div class="row activity-slider">
                    @foreach ($featuredTours as $tour)
                        <div class="col-md-3">
                            <x-frontend.tour-card :tour="$tour" style="style1" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($featuredPackages->count() > 0)
        <section class="holiday-section mar-y">
            <div class="container">
                <div class="section-content mb-4">
                    <h3 class="heading">Trending Holiday Packages</h3>
                </div>

                @php
                    $bigPackageCard = $featuredPackages[0];
                    $smallPackageCards = $featuredPackages->skip(1)->take(2);
                    $horizontalPackageCard = $featuredPackages->skip(3)->take(1)->first();
                @endphp
                <div class="row g-3">
                    <div class="col-lg-6 col-md-12">
                        <a href="{{ route('frontend.packages.details', $bigPackageCard->slug) }}" class="promo-card h-500">
                            <img data-src="{{ asset($bigPackageCard->image) }}" alt="{{ $bigPackageCard->name }}"
                                class="lazyload">

                            <span class="duration-badge">{{ $bigPackageCard->nights }}
                                {{ Str::plural('Night', $bigPackageCard->nights) }} / {{ $bigPackageCard->days }}
                                {{ Str::plural('Day', $bigPackageCard->days) }}</span>

                            <div class="card-overlay">
                                <div class="promo-info">
                                    <div class="title">{{ $bigPackageCard->name }}</div>
                                    <p>Starts {{ formatPrice($bigPackageCard->price) }}</p>
                                    <div class="btn-explore">
                                        Explore now <i class='bx bx-right-arrow-alt'></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-6 col-md-12">
                        <div class="row g-3 h-100">
                            @if ($smallPackageCards->count() > 0)
                                @foreach ($smallPackageCards as $smallPackageCard)
                                    <div class="col-md-6">
                                        <a href="{{ route('frontend.packages.details', $smallPackageCard->slug) }}" class="promo-card h-240">
                                            <img data-src="{{ asset($smallPackageCard->image) }}"
                                                alt="{{ $smallPackageCard->name }}" class="lazyload">
                                            <span class="duration-badge">{{ $smallPackageCard->nights }}
                                                {{ Str::plural('Night', $smallPackageCard->nights) }} / 
                                                {{ $smallPackageCard->days }}
                                                {{ Str::plural('Day', $smallPackageCard->days) }}</span>
                                            <div class="card-overlay">
                                                <div class="promo-info">
                                                    <div class="title">{{ $smallPackageCard->name }}</div>
                                                    <p class="fs-6">Starts {{ formatPrice($smallPackageCard->price) }}
                                                    </p>
                                                    <div class="btn-explore">
                                                        Explore now <i class='bx bx-right-arrow-alt'></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endforeach
                            @endif

                            @if ($horizontalPackageCard)
                                <div class="col-12">
                                    <a href="{{ route('frontend.packages.details', $horizontalPackageCard->slug) }}" class="promo-card h-240">
                                        <img data-src="{{ asset($horizontalPackageCard->image) }}"
                                            alt="{{ $horizontalPackageCard->name }}" class="lazyload">
                                        <span class="duration-badge">{{ horizontalPackageCard->nights }}
                                            {{ Str::plural('Night', $horizontalPackageCard->nights) }} /
                                            {{ $horizontalPackageCard->days }}
                                            {{ Str::plural('Day', $horizontalPackageCard->days) }}</span>
                                        <div class="card-overlay">
                                            <div class="d-flex justify-content-between align-items-end w-100">
                                                <div class="promo-info">
                                                    <div class="title"> {{ $horizontalPackageCard->name }}</div>

                                                    <p>Starts {{ formatPrice($horizontalPackageCard->price) }}</p>
                                                    <div class="btn-explore">
                                                        Explore now <i class='bx bx-right-arrow-alt'></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="section-flight-offers mar-y">
        <div class="container">
            <div class="row align-items-end mb-2">
                <div class="col-md-8">
                    <div class="section-content">
                        <div class="pro-badge">Flight Deals</div>
                        <h3 class="heading">Exclusive Airfare Offers</h3>
                    </div>
                </div>
            </div>
            <div class="row g-4">

                <div class="col-md-6 col-lg-3">
                    <a href="#" class="flight-card">
                        <img data-src="https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                            alt="London" class="card-img lazyload">

                        <div class="card-badge"><i class="bx bxs-hot"></i>Trending</div>

                        <div class="card-content">
                            <div class="route-box">
                                <span class="code">DXB</span>
                                <div class="flight-line">
                                    <i class='bx bxs-plane-alt plane-icon'></i>
                                </div>
                                <span class="code">LHR</span>
                            </div>

                            <div class="text-white mb-3">
                                <h5 class="fw-bold mb-0">London</h5>
                                <small class="opacity-75"><i class='bx bx-calendar'></i> 20 Oct • 7h 10m</small>
                            </div>

                            <div
                                class="d-flex justify-content-between align-items-end border-top border-white border-opacity-25 pt-3">
                                <div>
                                    <small class="text-white d-block" style="font-size: 0.75rem;">Economy from</small>
                                    <span class="fw-bold text-white fs-5"><span class="dirham">D</span> 1,850</span>
                                </div>
                                <div class="btn-action"><i class='bx bx-right-arrow-alt'></i></div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-3">
                    <a href="#" class="flight-card">
                        <img data-src="https://images.unsplash.com/photo-1534430480872-3498386e7856?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                            alt="New York" class="card-img lazyload">


                        <div class="card-badge"><i class="bx bxs-hot"></i> Best Deal</div>

                        <div class="card-content">
                            <div class="route-box">
                                <span class="code">DXB</span>
                                <div class="flight-line">
                                    <i class='bx bxs-plane-alt plane-icon'></i>
                                </div>
                                <span class="code">JFK</span>
                            </div>

                            <div class="text-white mb-3">
                                <h5 class="fw-bold mb-0">New York</h5>
                                <small class="opacity-75"><i class='bx bx-calendar'></i> 15 Nov • 14h 20m</small>
                            </div>

                            <div
                                class="d-flex justify-content-between align-items-end border-top border-white border-opacity-25 pt-3">
                                <div>
                                    <small class="text-white d-block" style="font-size: 0.75rem;">Economy from</small>
                                    <span class="fw-bold text-white fs-5"><span class="dirham">D</span> 3,200</span>
                                </div>
                                <div class="btn-action"><i class='bx bx-right-arrow-alt'></i></div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-3">
                    <a href="#" class="flight-card">
                        <img data-src="https://images.unsplash.com/photo-1502602898657-3e91760cbb34?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                            alt="Paris" class="card-img lazyload">

                        <div class="card-badge"><i class="bx bxs-hot"></i> Trending</div>

                        <div class="card-content">
                            <div class="route-box">
                                <span class="code">DXB</span>
                                <div class="flight-line">
                                    <i class='bx bxs-plane-alt plane-icon'></i>
                                </div>
                                <span class="code">CDG</span>
                            </div>

                            <div class="text-white mb-3">
                                <h5 class="fw-bold mb-0">Paris</h5>
                                <small class="opacity-75"><i class='bx bx-calendar'></i> 05 Dec • 7h 25m</small>
                            </div>

                            <div
                                class="d-flex justify-content-between align-items-end border-top border-white border-opacity-25 pt-3">
                                <div>
                                    <small class="text-white d-block" style="font-size: 0.75rem;">Economy from</small>
                                    <span class="fw-bold text-white fs-5"><span class="dirham">D</span> 1,650</span>
                                </div>
                                <div class="btn-action"><i class='bx bx-right-arrow-alt'></i></div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-3">
                    <a href="#" class="flight-card">
                        <img data-src="https://images.unsplash.com/photo-1527838832700-5059252407fa?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                            alt="Istanbul" class="card-img lazyload">

                        <div class="card-badge"><i class="bx bxs-hot"></i> Best Deal</div>

                        <div class="card-content">
                            <div class="route-box">
                                <span class="code">DXB</span>
                                <div class="flight-line">
                                    <i class='bx bxs-plane-alt plane-icon'></i>
                                </div>
                                <span class="code">IST</span>
                            </div>

                            <div class="text-white mb-3">
                                <h5 class="fw-bold mb-0">Istanbul</h5>
                                <small class="opacity-75"><i class='bx bx-calendar'></i> 12 Jan • 4h 50m</small>
                            </div>

                            <div
                                class="d-flex justify-content-between align-items-end border-top border-white border-opacity-25 pt-3">
                                <div>
                                    <small class="text-white d-block" style="font-size: 0.75rem;">Economy from</small>
                                    <span class="fw-bold text-white fs-5"><span class="dirham">D</span> 1,100</span>
                                </div>
                                <div class="btn-action"><i class='bx bx-right-arrow-alt'></i></div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>
    
{{-- 
    <section class="section-hotel-offers py-5 bg-light">
        <div class="container">
            <div class="row align-items-end mb-4">
                <div class="col-md-8">
                    <div class="section-content">
                        <div class="pro-badge">Luxury Stays</div>
                        <h3 class="heading">Exclusive Hotel Deals</h3>
                    </div>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="hotel-card">
                        <div class="hotel-img-wrapper">
                            <img data-src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80"
                                alt="Atlantis The Royal" class="img-fluid lazyload">
                            <span class="badge primary-bg offer-badge">Featured</span>

                        </div>

                        <a href="#" class="hotel-details shadow-sm">
                            <span class="location-text text-muted"><i class='bx bxs-map'></i> Palm Jumeirah</span>
                            <h5 class="hotel-title mb-1">Atlantis The Royal</h5>
                            <p class="text-muted small mb-3">Ultra-luxury resort experience</p>

                            <div class="d-flex justify-content-between align-items-end border-top pt-3">
                                <div>
                                    <small class="text-muted text-decoration-line-through"><span class="dirham">D</span>
                                        2,500</small>
                                    <div class="hotel-price-tag"><span class="dirham">D</span> 1,999 <small
                                            class="text-muted fw-normal">/ night</small>
                                    </div>
                                </div>
                                <div class="btn btn-sm btn-book rounded-pill"><i class='bx bx-chevron-right'></i></div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="hotel-card">
                        <div class="hotel-img-wrapper">
                            <img data-src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80"
                                alt="Burj Al Arab" class="img-fluid lazyload">
                            <span class="badge primary-bg offer-badge">Featured</span>

                        </div>

                        <a href="#" class="hotel-details shadow-sm">
                            <span class="location-text text-muted"><i class='bx bxs-map'></i> Jumeirah Beach</span>
                            <h5 class="hotel-title mb-1">Burj Al Arab</h5>
                            <p class="text-muted small mb-3">The world's only 7-star hotel</p>

                            <div class="d-flex justify-content-between align-items-end border-top pt-3">
                                <div>
                                    <small class="text-muted text-decoration-line-through"><span class="dirham">D</span>
                                        4,500</small>
                                    <div class="hotel-price-tag"><span class="dirham">D</span> 3,800 <small
                                            class="text-muted fw-normal">/ night</small>
                                    </div>
                                </div>
                                <div class="btn btn-sm btn-book rounded-pill"><i class='bx bx-chevron-right'></i></div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="hotel-card">
                        <div class="hotel-img-wrapper">
                            <img data-src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80"
                                alt="Rixos Premium" class="img-fluid lazyload">
                            <span class="badge primary-bg offer-badge">Featured</span>

                        </div>

                        <a href="#" class="hotel-details shadow-sm">
                            <span class="location-text text-muted"><i class='bx bxs-map'></i> JBR, Dubai</span>
                            <h5 class="hotel-title mb-1">Rixos Premium</h5>
                            <p class="text-muted small mb-3">Lifestyle hotel on the beach</p>

                            <div class="d-flex justify-content-between align-items-end border-top pt-3">
                                <div>
                                    <small class="text-muted text-decoration-line-through"><span class="dirham">D</span>
                                        1,200</small>
                                    <div class="hotel-price-tag"><span class="dirham">D</span> 850 <small
                                            class="text-muted fw-normal">/ night</small>
                                    </div>
                                </div>
                                <div class="btn btn-sm btn-book rounded-pill"><i class='bx bx-chevron-right'></i></div>
                            </div>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section> --}}


    {{-- <section class="travel-stories-pro mar-y">
        <div class="container">

            <div class="ts-header">
                <div class="ts-title-wrap">
                    <div class="section-content">
                        <h3 class="heading">Travel Stories</h3>
                    </div>
                </div>
            </div>

            <div class="ts-main-slider">
                <div class="ts-slide-item">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <article class="ts-hero-card">
                                <img data-src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?q=80&w=1200&auto=format&fit=crop"
                                    alt="Swiss Alps" class="ts-hero-img lazyload">
                                <div class="ts-hero-overlay">
                                    <div class="ts-tags">
                                        <span class="ts-tag">Adventure</span>
                                        <span class="ts-time">5 min read</span>
                                    </div>
                                    <h3 class="ts-hero-title">The Untouched Peaks: Hiking the Swiss Alps in Autumn</h3>
                                </div>
                                <a href="#" class="ts-card-link"></a>
                            </article>
                        </div>
                        <div class="col-lg-6">
                            <div class="ts-list-stack">
                                <article class="ts-side-card">
                                    <a href="#" class="ts-side-img-box">
                                        <img data-src="https://images.unsplash.com/photo-1537996194471-e657df975ab4?q=80&w=600&auto=format&fit=crop"
                                            alt="Bali Food" class="lazyload">
                                    </a>
                                    <div class="ts-side-content">
                                        <span class="ts-category">Culinary</span>
                                        <a href="#" class="ts-side-title line-clamp-2">Best street food in Bali
                                            (Hidden Gems)</a>
                                        <p class="ts-side-desc line-clamp-1">From spicy Sambal to sweet Dadar Gulung.</p>
                                        <a href="#" class="ts-read-more">Read Story</a>
                                    </div>
                                </article>
                                <article class="ts-side-card">
                                    <a href="#" class="ts-side-img-box">
                                        <img data-src="https://images.unsplash.com/photo-1502602898657-3e91760cbb34?q=80&w=600&auto=format&fit=crop"
                                            alt="Paris" class="lazyload">
                                    </a>
                                    <div class="ts-side-content">
                                        <span class="ts-category">City Guide</span>
                                        <a href="#" class="ts-side-title line-clamp-2">Paris on a Budget: Luxury for
                                            less</a>
                                        <p class="ts-side-desc line-clamp-1">Enjoy the City of Lights without breaking the
                                            bank.</p>
                                        <a href="#" class="ts-read-more">Read Story</a>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ts-slide-item">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <article class="ts-hero-card">
                                <img data-src="https://images.unsplash.com/photo-1516483638261-f4dbaf036963?q=80&w=1200&auto=format&fit=crop"
                                    alt="Cinque Terre" class="ts-hero-img lazyload">
                                <div class="ts-hero-overlay">
                                    <div class="ts-tags">
                                        <span class="ts-tag">Coastal</span>
                                        <span class="ts-time">7 min read</span>
                                    </div>
                                    <h3 class="ts-hero-title">Cinque Terre: Walking the colorful villages of Italy</h3>
                                </div>
                                <a href="#" class="ts-card-link"></a>
                            </article>
                        </div>
                        <div class="col-lg-6">
                            <div class="ts-list-stack">
                                <article class="ts-side-card">
                                    <a href="#" class="ts-side-img-box">
                                        <img data-src="https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?q=80&w=600&auto=format&fit=crop"
                                            alt="Kyoto" class="lazyload">
                                    </a>
                                    <div class="ts-side-content">
                                        <span class="ts-category">Culture</span>
                                        <a href="#" class="ts-side-title line-clamp-2">Kyoto in Bloom: A Cherry
                                            Blossom Guide</a>
                                        <p class="ts-side-desc line-clamp-1">The best spots to see Sakura away from the
                                            crowds.</p>
                                        <a href="#" class="ts-read-more">Read Story</a>
                                    </div>
                                </article>
                                <article class="ts-side-card">
                                    <a href="#" class="ts-side-img-box">
                                        <img data-src="https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?q=80&w=600&auto=format&fit=crop"
                                            alt="Tokyo" class="lazyload">
                                    </a>
                                    <div class="ts-side-content">
                                        <span class="ts-category">Tech & Travel</span>
                                        <a href="#" class="ts-side-title line-clamp-2">Tokyo Future: The Neon
                                            Nights</a>
                                        <p class="ts-side-desc line-clamp-1">Exploring the electronic district of
                                            Akihabara.</p>
                                        <a href="#" class="ts-read-more">Read Story</a>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section> --}}

    <section class="newsletter mar-y">
        <div class="container">
            <div class="glass-panel">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <div class="horizon-content">
                            <span class="tag-badge">
                                <i class="bx bxs-hot"></i> Trending</span>
                            <h5 class="heading">Don't miss the next adventure.</h3>
                                <p>Subscribe now and receive the latest travel news.</p>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <form class="horizon-form" action="{{ route('frontend.newsletter.subscribe') }}" method="POST">
                            @csrf
                            <div class="input-wrapper">
                                <input autocomplete="off" type="email" class="horizon-input"
                                    placeholder="Enter your email address..." name="email" required />
                                <button type="submit" class="horizon-btn">
                                    Subscribe <i class="bx bx-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <img src="{{ asset('frontend/assets/images/globe.png') }}" alt="globe" class="imgFluid globe-icon">
            </div>
        </div>
    </section>
@endsection