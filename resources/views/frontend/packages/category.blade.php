@extends('frontend.layouts.main')
@section('content')
    @if (isset($banner) && $banner)
        <section class="page-header py-5 d-flex align-items-center"
            style="background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)), url('{{ asset($banner->image) }}'); background-size: cover; background-position: center; height:288px;">
            <div class="container text-center text-white">
                <h1 class="fw-bold display-4">{{ strtoupper($category->name) }}</h1>
            </div>
        </section>
    @else
        <section class="page-header py-5 d-flex align-items-center"
            style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('{{ asset('frontend/assets/images/about-banner.jpeg') }}'); background-size: cover; background-position: center; height:288px;">
            <div class="container text-center text-white">
                <h1 class="fw-bold display-4">{{ strtoupper($category->name) }}</h1>
            </div>
        </section>
    @endif

    @if ($category->short_description)
        <section class="section-about text-center py-5 bg-light">
            <div class="container">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-10">
                        <span class="text-uppercase fw-bold letter-spacing-2" style="color: var(--color-primary);">Andaleeb
                            Travel Agency</span>
                        <h2 class="fw-bold mb-4 mt-2">{{ strtoupper($category->name) }}</h2>
                        <p>{{ $category->short_description }}</p>
                    </div>
                </div>
            </div>
        </section>
    @endif


    <section class="activities mar-y">
        <div class="container">
            <div class="row">
                @forelse($packages as $package)
                    <div class="col-md-4">
                        <div class="activity-card">
                            <div class="act-img-box">
                                <a href="{{ route('frontend.packages.details', $package->slug) }}">
                                    <img class="imgFluid lazyload"
                                        data-src="{{ $package->image ? asset($package->image) : asset('frontend/assets/images/placeholder.jpg') }}"
                                        alt="{{ $package->name }}">
                                </a>
                            </div>
                            <div class="act-details">
                                <div style="font-size: 1.25rem;" class="act-title line-clamp-1">{{ $package->name }}
                                </div>
                                @if ($package->short_description)
                                    <div class="act-rating">
                                        <span style="height: 61px"
                                            class="review-count line-clamp-3">{{ $package->short_description }}</span>
                                    </div>
                                @endif
                                @if ($package->price)
                                    <div class="act-price"><span class="dirham">D</span>
                                        {{ number_format($package->price, 2) }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-results " aria-labelledby="no-results-title">
                        <div class="row justify-content-center">
                            <div class="col-12 text-center">

                                <!-- Visual Cue -->
                                <div class="mb-3">
                                    <i class='bx bx-search-alt empty-icon' aria-hidden="true"></i>
                                </div>

                                <!-- Content -->
                                <h2 id="no-results-title" class="h4 fw-bold mb-2">
                                    No packages available in this category at the moment
                                </h2>

                                <p class="text-muted mb-4">
                                    Please check back later or explore other categories.
                                </p>

                                <!-- Actions -->
                                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                                    <a href="{{ route('frontend.hotels.index') }}" type="button"
                                        class="themeBtn themeBtn--primary">
                                        Browse All Categories
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
