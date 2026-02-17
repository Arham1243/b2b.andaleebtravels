@extends('frontend.layouts.main')
@section('content')
    @if (isset($banner) && $banner)
        <section class="page-header py-5 d-flex align-items-center"
            style="background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)), url('{{ asset($banner->image) }}'); background-size: cover; background-position: center; height:288px;">
            @if ($banner->heading || $banner->paragraph)
                <div class="container text-center text-white">
                    @if ($banner->heading)
                        <h1 class="fw-bold display-4">{{ $banner->heading }}</h1>
                    @endif
                    @if ($banner->paragraph)
                        <p class="lead mb-0">{{ $banner->paragraph }}</p>
                    @endif
                </div>
            @endif
        </section>
    @else
        <section class="page-header py-5 d-flex align-items-center"
            style="background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)), url('{{ asset('frontend/assets/images/banners/2.jpg') }}'); background-size: cover; background-position: center; height:288px;">
        </section>
    @endif


    <section class="activities mar-y">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="fw-bold mb-2">Discover Our Packages</h2>
                <p class="lead">
                    Handpicked tours and travel experiences to make your UAE holidays unforgettable
                </p>
            </div>
            <div class="row">
                @forelse($categories as $category)
                    <div class="col-md-3">
                        <a href="{{ route('frontend.packages.category', $category->slug) }}"
                            class="category-card category-card--lg">
                            <div class="category-card__img">
                                <img data-src="{{ asset($category->image) }}" alt="{{ $category->name }}"
                                    class="imgFluid lazyload">
                            </div>
                            <div class="category-card__content">
                                <div class="title line-clamp-1">{{ $category->name }}</div>
                                @if ($category->short_description)
                                    <div class="desc line-clamp-3">{{ $category->short_description }}</div>
                                @endif
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center py-5">
                            <p class="text-muted">No package categories available at the moment.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Info & Features Section -->
    <section class="section-info py-5 bg-light">
        <div class="container">

            <!-- Intro Content -->
            <div class="row justify-content-center mb-5">
                <div class="col-lg-10 text-center">
                    <span class="text-uppercase fw-bold ls-2 small" style="color: var(--color-primary);">Andaleeb Travel
                        Agency</span>
                    <h2 class="fw-bold mb-2 mt-2">UAE Travel Packages</h2>
                    <p class="lead">
                        Travel packages are pre-arranged trips that bundle flights, accommodations, transportation, and
                        activities into a single seamless deal. Designed for convenience and value, our packages let you
                        experience the best of the UAE without the stress of planning.
                    </p>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="row g-4">

                <!-- Feature 1: Convenience -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 bg-white rounded-3 shadow-sm">
                        <div class="icon-wrapper mb-3">
                            <i class='bx bx-check-shield'></i>
                        </div>
                        <h5 class="fw-bold">Convenience</h5>
                        <p class="text-muted small mb-0">
                            Eliminate the hassle of booking separately. We provide a complete itinerary with pre-arranged
                            bookings for flights, hotels, and activities, saving you time and effort.
                        </p>
                    </div>
                </div>

                <!-- Feature 2: Cost Savings -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 bg-white rounded-3 shadow-sm">
                        <div class="icon-wrapper mb-3">
                            <i class='bx bx-wallet-alt'></i>
                        </div>
                        <h5 class="fw-bold">Cost Savings</h5>
                        <p class="text-muted small mb-0">
                            Benefit from our bulk buying power. We negotiate special rates with airlines and hotels to offer
                            you discounted prices compared to booking individually.
                        </p>
                    </div>
                </div>

                <!-- Feature 3: Itinerary Planning -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 bg-white rounded-3 shadow-sm">
                        <div class="icon-wrapper mb-3">
                            <i class='bx bx-map-pin'></i>
                        </div>
                        <h5 class="fw-bold">Itinerary Planning</h5>
                        <p class="text-muted small mb-0">
                            First time visiting? Enjoy a well-planned itinerary featuring popular attractions and
                            experiences, ensuring a hassle-free and memorable travel experience.
                        </p>
                    </div>
                </div>

                <!-- Feature 4: Expert Guidance -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 bg-white rounded-3 shadow-sm">
                        <div class="icon-wrapper mb-3">
                            <i class='bx bx-user-voice'></i>
                        </div>
                        <h5 class="fw-bold">Expert Guidance</h5>
                        <p class="text-muted small mb-0">
                            Gain insights from our travel consultants and guides. We handle logistics and offer local
                            recommendations to enhance your journey.
                        </p>
                    </div>
                </div>

                <!-- Feature 5: Added Value -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 bg-white rounded-3 shadow-sm">
                        <div class="icon-wrapper mb-3">
                            <i class='bx bx-gift'></i>
                        </div>
                        <h5 class="fw-bold">Added Value</h5>
                        <p class="text-muted small mb-0">
                            Enjoy exclusive perks like complimentary breakfasts, guided city tours, airport transfers, and
                            access to special events not available to the public.
                        </p>
                    </div>
                </div>

                <!-- Feature 6: Group Travel -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 bg-white rounded-3 shadow-sm">
                        <div class="icon-wrapper mb-3">
                            <i class='bx bx-group'></i>
                        </div>
                        <h5 class="fw-bold">Group Travel</h5>
                        <p class="text-muted small mb-0">
                            Perfect for families and friends. Share experiences, enjoy group discounts, and take advantage
                            of organized excursions designed for groups.
                        </p>
                    </div>
                </div>

            </div>

            <!-- Disclaimer / Footer Note -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="alert custom-alert d-flex align-items-start" role="alert">
                        <i class='bx bx-info-circle fs-4 me-3 flex-shrink-0' style="color: var(--color-primary);"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Important Note</h6>
                            <p class="mb-0 small text-muted">
                                When considering a package, please review details regarding destinations, inclusions,
                                exclusions, and cancellation policies. Andaleeb Travel Agency is committed to transparency
                                and customer satisfaction.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection
