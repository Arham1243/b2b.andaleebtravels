@extends('frontend.layouts.main')
@section('content')
    <section class="page-header py-5 d-flex align-items-center"
        style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('{{ asset('frontend/assets/images/about-banner.jpeg') }}'); background-size: cover; background-position: center; height:288px;">
        <div class="container">
            <div class="row justify-content-center mt-5 pt-5">
                <div class="col-md-6">
                    @include('frontend.vue.main', [
                        'appId' => 'packages-search',
                        'appComponent' => 'packages-search',
                        'appJs' => 'packages-search',
                    ])
                </div>
            </div>
        </div>
    </section>

    <section class="activities mar-y">
        <div class="container">
            @if (request('search'))
                <p class="text-muted mt-1 mb-0">
                    Showing results for: <span class="fw-bold">"{{ request('search') }}"</span>

                    <a href="{{ route('frontend.packages.search') }}" class="fw-medium text-primary ms-2">Reset</a>
                </p>
            @endif
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
                    <div class="empty-results mt-4" aria-labelledby="no-results-title">
                        <div class="row justify-content-center">
                            <div class="col-12 text-center">

                                <!-- Visual Cue -->
                                <div class="mb-3">
                                    <i class='bx bx-search-alt empty-icon' aria-hidden="true"></i>
                                </div>

                                @if (request('search'))
                                    <!-- Content -->
                                    <h2 id="no-results-title" class="h4 fw-bold mb-2">
                                        No packages found matching "{{ request('search') }}"
                                    </h2>
                                @else
                                    <h2 id="no-results-title" class="h4 fw-bold mb-2">
                                        No packages available in this category at the moment
                                    </h2>
                                @endif

                                <p class="text-muted mb-4">
                                    Please check back later or explore other categories.
                                </p>

                                <!-- Actions -->
                                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                                    <a href="{{ route('frontend.packages.index') }}" type="button"
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
