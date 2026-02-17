@extends('frontend.layouts.main')
@section('content')
    @if (isset($banner) && $banner)
        <section class="page-header py-5 d-flex align-items-center"
            style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('{{ asset($banner->image) }}');">
            <div class="container text-center text-white">
                <h1 class="fw-bold display-4">{{ $package->name ?? 'Package Details' }}</h1>
            </div>
        </section>
    @else
        <section class="page-header py-5 d-flex align-items-center"
            style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('{{ asset('frontend/assets/images/banners/5.jpg') }}');">
            <div class="container text-center text-white">
                <h1 class="fw-bold display-4">Package Details</h1>
            </div>
        </section>
    @endif

    <div class="tour-details py-2">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <div class="py-4">
                        <h1 class="tour-header-title">{{ $package->name ?? 'Package Details' }}</h1>
                        <small class="fw-bold color-primary d-block mt-2">
                            Supplied by: Andaleeb Travel Agency

                        </small>
                        <div class="faq-wrapper">
                            <div class="faq-item active">
                                <div class="faq-header">
                                    <span class="faq-question">Overview</span>
                                    <i class='bx bx-chevron-down faq-icon'></i>
                                </div>
                                <div class="faq-body">
                                    <div class="faq-content text-document">
                                        {!! $package->content['overview'] !!}
                                    </div>
                                </div>
                            </div>
                            <div class="faq-item active">
                                <div class="faq-header">
                                    <span class="faq-question">Details</span>
                                    <i class='bx bx-chevron-down faq-icon'></i>
                                </div>
                                <div class="faq-body">
                                    <div class="faq-content text-document">
                                        {!! $package->content['package_details'] !!}
                                    </div>
                                </div>
                            </div>
                            <div class="faq-item active">
                                <div class="faq-header">
                                    <span class="faq-question">Inclusions</span>
                                    <i class='bx bx-chevron-down faq-icon'></i>
                                </div>
                                <div class="faq-body">
                                    <div class="faq-content text-document">
                                        {!! $package->content['inclusion'] !!}
                                    </div>
                                </div>
                            </div>
                            <div class="faq-item active">
                                <div class="faq-header">
                                    <span class="faq-question">Exclusions</span>
                                    <i class='bx bx-chevron-down faq-icon'></i>
                                </div>
                                <div class="faq-body">
                                    <div class="faq-content text-document">
                                        {!! $package->content['exclusion'] !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="py-4">
                        @if (session('notify_success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('notify_success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="booking-widget">
                            <form action="{{ route('frontend.packages.inquiry.submit') }}" method="POST">
                                @csrf
                                <input type="hidden" name="package_id" value="{{ $package->id }}">

                                <!-- 1. Price Header -->
                                <div class="booking-header">
                                    <span class="booking-label">From:</span>
                                    <div class="booking-price"><span class="dirham">D</span> {{ $package->price ?? 'N/A' }}
                                    </div>
                                </div>

                                <!-- 2. Date & Time Selection -->
                                <div class="booking-form">
                                    <!-- Full Name -->
                                    <div class="form-group mb-3">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control custom-input"
                                            value="{{ old('name') }}" required>
                                    </div>

                                    <!-- Contact Number -->
                                    <div class="form-group mb-3">
                                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                        <input type="tel" name="phone" class="form-control custom-input"
                                            value="{{ old('phone') }}" required>
                                    </div>

                                    <!-- Email Address -->
                                    <div class="form-group mb-3">
                                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control custom-input"
                                            value="{{ old('email') }}" required>
                                    </div>

                                    <!-- Tour Date -->
                                    <div class="form-group mb-3">
                                        <label class="form-label">Tour Date</label>
                                        <input type="date" name="tour_date" class="form-control custom-input"
                                            value="{{ old('tour_date') }}">
                                    </div>

                                    <!-- Passengers -->
                                    <div class="form-group mb-3">
                                        <label class="form-label">Pax</label>
                                        <input type="number" name="pax" class="form-control custom-input"
                                            value="{{ old('pax') }}" min="1">
                                    </div>

                                    <!-- Pickup Location -->
                                    <div class="form-group mb-3">
                                        <label class="form-label">Pickup Location</label>
                                        <input type="text" name="pickup_location" class="form-control custom-input"
                                            value="{{ old('pickup_location') }}">
                                    </div>

                                    <!-- Message -->
                                    <div class="form-group mb-3">
                                        <label class="form-label">Message</label>
                                        <textarea name="message" class="form-control custom-input" rows="4">{{ old('message') }}</textarea>
                                    </div>

                                    <div class="form-group">
                                        <div class="g-recaptcha" data-sitekey="{{ env('RE_CAPTCHA_SITE_KEY') }}"> </div>
                                    </div>

                                    <!-- 4. Actions -->
                                    <div class="booking-actions">
                                        <button type="submit" class="btn btn-add-cart mb-2">
                                            Submit Inquiry
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="card border-0 choose-andaleeb-card">
                            <div class="card-body p-4">
                                <!-- Section Header -->
                                <h6 class="choose-card-title">Why choose Andaleeb?</h6>

                                <div class="mt-4">
                                    <!-- Item 1 -->
                                    <div class="choose-item">
                                        <div class="choose-icon icon-gold">
                                            <i class='bx bxs-badge-dollar'></i>
                                        </div>
                                        <div class="choose-text">
                                            <span class="choose-label">Best Price Guarantee</span>
                                            <p class="choose-sub">Always the best deal—book with confidence.</p>
                                        </div>
                                    </div>

                                    <!-- Item 2 -->
                                    <div class="choose-item">
                                        <div class="choose-icon icon-teal">
                                            <i class='bx bxs-lock-alt'></i>
                                        </div>
                                        <div class="choose-text">
                                            <span class="choose-label">Secure Online Transaction</span>
                                            <p class="choose-sub">Protected with advanced encryption.</p>
                                        </div>
                                    </div>

                                    <!-- Item 3 -->
                                    <div class="choose-item">
                                        <div class="choose-icon icon-blue">
                                            <i class='bx bxs-message-rounded-dots'></i>
                                        </div>
                                        <div class="choose-text">
                                            <span class="choose-label">24X7 Live Chat Support</span>
                                            <p class="choose-sub">Real humans, ready to help anytime.</p>
                                        </div>
                                    </div>

                                    <!-- Item 4 -->
                                    <div class="choose-item">
                                        <div class="choose-icon icon-orange">
                                            <i class='bx bxs-smile'></i>
                                        </div>
                                        <div class="choose-text">
                                            <span class="choose-label">Happy Travelers Worldwide</span>
                                            <p class="choose-sub">Trusted by millions of happy travelers.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $tabs = [];
        foreach ($packageCategories as $category) {
            $tabs[] = [
                'id' => 'category-' . $category->id,
                'label' => $category->name,
                'links' => $category->packages
                    ->map(function ($pkg) {
                        return [
                            'label' => $pkg->name,
                            'url' => route('frontend.packages.details', $pkg->slug),
                        ];
                    })
                    ->toArray(),
            ];
        }
    @endphp
    @if ($packageCategories->isNotEmpty())
        <section class="mar-y section-explore">
            <div class="container">

                <div class="section-content mb-4">
                    <h3 class="heading mb-0">Explore more with us</h3>
                </div>

                <!-- Tabs Navigation Wrapper -->
                <div class="position-relative explore-wrapper mb-4">
                    <!-- Left Arrow -->
                    <button class="explore-arrow-btn explore-arrow-left" aria-label="Scroll Left">
                        <i class="bx bx-chevron-left"></i>
                    </button>

                    <!-- Tabs List -->
                    <ul class="d-flex overflow-auto flex-nowrap scroll-smooth no-scrollbar explore-scroller"
                        role="tablist">
                        @foreach ($tabs as $index => $tab)
                            <li role="presentation" class="flex-shrink-0">
                                <button class="explore-tab-btn {{ $index === 0 ? 'active' : '' }}"
                                    id="{{ $tab['id'] }}-tab" data-bs-toggle="tab"
                                    data-bs-target="#{{ $tab['id'] }}-pane" type="button" role="tab"
                                    aria-controls="{{ $tab['id'] }}-pane"
                                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                    {{ $tab['label'] }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Right Arrow -->
                    <button class="explore-arrow-btn explore-arrow-right" aria-label="Scroll Right">
                        <i class="bx bx-chevron-right"></i>
                    </button>
                </div>

                <!-- Tabs Content -->
                <div class="tab-content">
                    @foreach ($tabs as $index => $tab)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="{{ $tab['id'] }}-pane"
                            role="tabpanel" aria-labelledby="{{ $tab['id'] }}-tab" tabindex="0">

                            <!-- Grid Layout for Links -->
                            <div class="explore-link-grid">
                                @foreach ($tab['links'] as $link)
                                    <a href="{{ $link['url'] }}" class="explore-link-item">
                                        <span>{{ $link['label'] }}</span>
                                        <i class="bx bx-right-arrow-alt"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
@push('js')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const recaptcha = grecaptcha.getResponse();

                if (!recaptcha) {
                    e.preventDefault();
                    showMessage("Please complete the reCAPTCHA before submitting.", "error");
                    return false;
                }
            });

        });
    </script>
    @if ($packageCategories->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('.explore-wrapper')?.forEach(wrapper => {
                    const scroller = wrapper.querySelector('.explore-scroller');
                    const arrowLeft = wrapper.querySelector('.explore-arrow-left');
                    const arrowRight = wrapper.querySelector('.explore-arrow-right');

                    if (!scroller) return;

                    const updateArrows = () => {
                        // Tolerance of 1px for high-DPI screens
                        const maxScrollLeft = scroller.scrollWidth - scroller.clientWidth;

                        // Show left arrow if scrolled more than 0
                        arrowLeft.style.display = scroller.scrollLeft > 5 ? 'flex' : 'none';

                        // Show right arrow if not at the end
                        arrowRight.style.display = scroller.scrollLeft >= maxScrollLeft - 5 ? 'none' :
                            'flex';
                    };

                    arrowLeft.addEventListener('click', () => {
                        scroller.scrollBy({
                            left: -200,
                            behavior: 'smooth'
                        });
                    });

                    arrowRight.addEventListener('click', () => {
                        scroller.scrollBy({
                            left: 200,
                            behavior: 'smooth'
                        });
                    });

                    scroller.addEventListener('scroll', updateArrows);
                    window.addEventListener('resize', updateArrows);

                    // Initial check
                    updateArrows();
                });
            });
        </script>
    @endif
@endpush
