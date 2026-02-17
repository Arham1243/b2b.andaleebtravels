@extends('frontend.layouts.main')
@section('content')
    @if(isset($banner) && $banner)
    <section class="hotels-banner page-header py-5 d-flex align-items-center"
        style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('{{ asset($banner->image) }}'); background-size: cover; background-position: center;">
    @else
    <section class="hotels-banner page-header py-5 d-flex align-items-center"
        style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('{{ asset('frontend/assets/images/banners/3.webp') }}'); background-size: cover; background-position: center;">
    @endif
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="main-page-search">
                        @include('frontend.vue.main', [
                            'appId' => 'hotels-search',
                            'appComponent' => 'hotels-search',
                            'appJs' => 'hotels-search',
                        ])
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- <section class="section-hotel-offers py-5 bg-light">
        <div class="container">
            <div class="row align-items-end mb-4 pb-4">
                <div class="col-md-8">
                    <div class="section-content">
                        <div class="pro-badge">Luxury Stays</div>
                        <h3 class="heading">Exclusive Hotel Deals</h3>
                    </div>
                </div>
            </div>

            <div class="row g-4 hotels-slider">

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
                                            class="text-muted fw-normal">/
                                            night</small>
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
                                            class="text-muted fw-normal">/
                                            night</small>
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
                                            class="text-muted fw-normal">/
                                            night</small>
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


    <!-- Services Grid -->
    <section class="section-services mar-y">
        <div class="container py-4">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center">
                    <h3 class="fw-bold">Why Choose Andaleeb?</h3>
                    <p class="text-muted">A complete range of travel and tourism services designed for you.</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Service 1 -->
                <div class="col-md-6 col-lg-4">
                    <div class="service-box p-4 bg-white rounded-3 h-100">
                        <i class='bx bxs-plane service-icon'></i>
                        <h5 class="fw-bold mt-3">Flight Bookings</h5>
                        <p class="text-muted small mb-0">Domestic and international ticketing with top airlines.</p>
                    </div>
                </div>
                <!-- Service 2 -->
                <div class="col-md-6 col-lg-4">
                    <div class="service-box p-4 bg-white rounded-3 h-100">
                        <i class='bx bx-building-house service-icon'></i>
                        <h5 class="fw-bold mt-3">Hotel Reservations</h5>
                        <p class="text-muted small mb-0">Comfortable stays at the best rates worldwide.</p>
                    </div>
                </div>
                <!-- Service 3 -->
                <div class="col-md-6 col-lg-4">
                    <div class="service-box p-4 bg-white rounded-3 h-100">
                        <i class='bx bx-map-alt service-icon'></i>
                        <h5 class="fw-bold mt-3">Tour Packages</h5>
                        <p class="text-muted small mb-0">Tailor-made holiday packages for families and groups.</p>
                    </div>
                </div>
                <!-- Service 4 -->
                <div class="col-md-6 col-lg-4">
                    <div class="service-box p-4 bg-white rounded-3 h-100">
                        <i class='bx bx-moon service-icon'></i>
                        <h5 class="fw-bold mt-3">Umrah & Hajj</h5>
                        <p class="text-muted small mb-0">Organized and spiritually fulfilling pilgrimage services.</p>
                    </div>
                </div>
                <!-- Service 5 -->
                <div class="col-md-6 col-lg-4">
                    <div class="service-box p-4 bg-white rounded-3 h-100">
                        <i class='bx bx-car service-icon'></i>
                        <h5 class="fw-bold mt-3">Transportation</h5>
                        <p class="text-muted small mb-0">Airport transfers, car rentals, and local transport.</p>
                    </div>
                </div>
                <!-- Service 6 -->
                <div class="col-md-6 col-lg-4">
                    <div class="service-box p-4 bg-white rounded-3 h-100">
                        <i class='bx bx-briefcase-alt service-icon'></i>
                        <h5 class="fw-bold mt-3">Corporate Travel</h5>
                        <p class="text-muted small mb-0">Efficient and cost-effective business travel planning.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- <section class="travel-stories-pro mar-y py-5 bg-light">
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

    <section class="section-values py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h3 class="fw-bold">Our Core Values</h3>
                <div class="about-divider mx-auto"></div>
            </div>
            <div class="row g-4 justify-content-center text-center">
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="value-item">
                        <i class='bx bx-heart-circle text-muted fs-1 mb-2'></i>
                        <h6 class="fw-bold value-title">Customer Satisfaction</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="value-item">
                        <i class='bx bx-shield-quarter text-muted fs-1 mb-2'></i>
                        <h6 class="fw-bold value-title">Integrity</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="value-item">
                        <i class='bx bx-star text-muted fs-1 mb-2'></i>
                        <h6 class="fw-bold value-title">Excellence</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="value-item">
                        <i class='bx bx-bulb text-muted fs-1 mb-2'></i>
                        <h6 class="fw-bold value-title">Innovation</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="value-item">
                        <i class='bx bx-group text-muted fs-1 mb-2'></i>
                        <h6 class="fw-bold value-title">Teamwork</h6>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
