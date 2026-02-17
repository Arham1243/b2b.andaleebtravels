@extends('frontend.layouts.main')
@section('content')
    @if (isset($banner) && $banner)
        <section class="page-header page-header--uae   py-5 d-flex align-items-center"
            style="background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)), url('{{ asset($banner->image) }}'); background-size: cover; background-position: center; height:288px;">
        @else
            <section class="page-header page-header--uae py-5 d-flex align-items-center"
                style="background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)), url('{{ asset('frontend/assets/images/banners/1.jpg') }}'); background-size: cover; background-position: center; height:288px;">
    @endif
    <div class="container">
        <div class="row justify-content-center mt-5 pt-5">
            <div class="col-md-6">
                @include('frontend.vue.main', [
                    'appId' => 'activities-search',
                    'appComponent' => 'activities-search',
                    'appJs' => 'activities-search',
                ])
            </div>
        </div>
    </div>
    </section>


    @if ($categories->isNotEmpty())
        <section class="section-categories bg-light padd-y">
            <div class="container">
                <!-- Header -->
                <div class="section-content mb-4 pb-4">
                    <h3 class="heading mb-0">Explore by Category</h3>
                    <p class="text-muted my-1">Discover the best experiences in the UAE</p>
                </div>

                <!-- Categories Grid -->
                <div class="row g-3 g-xl-4 category-slider2">
                    @foreach ($categories as $category)
                        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                            <a href="{{ route('frontend.tour-category.details', $category->slug) }}" class="cat-card">
                                <div class="cat-bg" style="background-image: url('{{ asset($category->image) }}');">
                                </div>
                                <div class="cat-overlay"></div>
                                <div class="cat-content">
                                    <h5 class="cat-title">{{ $category->name }}</h5>
                                    <div class="cat-action">
                                        <span class="cat-count">{{ $category->tours->count() }}
                                            {{ Str::plural('Activity', $category->tours->count()) }}</span>
                                        <span class="btn-icon"><i class='bx bx-right-arrow-alt'></i></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <div class="expandable-wrapper mar-y" data-collapsed-height="50" data-more-text="Read More" data-less-text="Read Less">
        <div class="container">
            <div class="expandable-card">
                <h3 class="expandable-title">Best Tours and Activities in Dubai</h3>

                <div class="expandable-content">
                    <div class="expandable-content-inner text-document">
                        <p>Dubai is a dream that transformed into a magnificent reality in no time. Not long ago, when it
                            was
                            nothing but a small Bedouin village, it was an impossible thought to portray Dubai as a place
                            where
                            the world comes to shop and have fun.</p>

                        <p>A city once known solely for its oil reserves is now a global tourism benchmark. City sightseeing
                            today is one of the most popular activities.</p>

                        <h4>Why visit?</h4>
                        <ul>
                            <li><a href="#">Burj Khalifa</a> views</li>
                            <li>Desert Safaris and BBQ dinners</li>
                            <li>Luxury Shopping experiences</li>
                        </ul>

                        <p>From the architecture to the cultural heritage, every corner tells a story waiting to be
                            explored.
                        </p>
                    </div>
                </div>

                <button class="expand-btn">Read More</button>
            </div>
        </div>
    </div>

    <section class="activities mar-y" id="tours">
        <div class="container">
            @if ($tours->count() > 0)
                <div class="section-header align-items-end">
                    <div class="section-content">
                        <h3 class="heading mb-0">Best Activities in Dubai</h3>
                        @if (request('search'))
                            <p class="text-muted mt-1 mb-0">
                                Showing results for: <span class="fw-bold">"{{ request('search') }}"</span>

                                <a href="{{ route('frontend.uae-services') }}#tours"
                                    class="fw-medium text-primary ms-2">Reset</a>
                            </p>
                        @endif
                    </div>
                    <div>
                        <label class="form-label">Sort by:</label>
                        @php
                            $sortOptions = [
                                '' => 'Select',
                                'recommended' => 'Recommended',
                                'price_low_to_high' => 'Price Low to High',
                                'price_high_to_low' => 'Price High to Low',
                            ];

                            $sortBy = request('sort_by', '');
                        @endphp

                        <select class="custom-select" name="sort_by" id="sort_by">
                            @foreach ($sortOptions as $value => $label)
                                <option value="{{ $value }}" {{ $sortBy === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row" id="firstTourBlockContainer">
                    @foreach ($tours as $tour)
                        <div class="col-md-3">
                            <x-frontend.tour-card :tour="$tour" style="style1" />
                        </div>
                    @endforeach
                </div>
                @if ($total_tours > 16)
                    <button id="loadMoreFirstBlock" class="themeBtn mx-auto mt-4">Load More</button>
                @endif
            @else
                <div class="empty-results" aria-labelledby="no-results-title">
                    <div class="row justify-content-center">
                        <div class="col-12 text-center">
                            <!-- Visual Cue -->
                            <div class="mb-3">
                                <i class='bx bx-search-alt empty-icon' aria-hidden="true"></i>
                            </div>

                            @if (request('search'))
                                <!-- Content -->
                                <h2 id="no-results-title" class="h4 fw-bold mb-2">
                                    No tours found matching "{{ request('search') }}"
                                </h2>
                            @else
                                <h2 id="no-results-title" class="h4 fw-bold mb-2">
                                    No Tours available at the moment
                                </h2>
                            @endif

                            <p class="text-muted mb-4">
                                Please check back later or explore other categories.
                            </p>

                            <!-- Actions -->
                            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                                @if (request('search'))
                                    <a href="{{ route('frontend.uae-services') }}#tours" type="button" class="themeBtn">
                                        Reset Search
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

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
                    <ul class="d-flex overflow-auto flex-nowrap scroll-smooth no-scrollbar explore-scroller" role="tablist">
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

    <script>
        function initLoadMore({
            button,
            container,
            blockConfig,
            limit = 8,
            colClass = 'col-md-3',
            cardStyle = 'style3',
            searchQuery = ''
        }) {
            const btn = typeof button === 'string' ? document.querySelector(button) : button;
            const containerEl = typeof container === 'string' ? document.querySelector(container) : container;

            // Track current offset
            let offset = limit;

            btn?.addEventListener('click', function() {
                const originalContent = btn.innerHTML;

                // Disable button and show spinner
                btn.disabled = true;
                btn.innerHTML = `<i class='bx bx-loader-alt bx-spin'></i> Loading...`;

                fetch('{{ route('frontend.load.tour.blocks') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            limit: limit,
                            offset: offset,
                            block: blockConfig,
                            col_class: colClass,
                            card_style: cardStyle,
                            search_query: searchQuery,
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.remainingCount === 0) {
                            btn.remove(); // No more tours
                        }

                        // Append new tours
                        containerEl.insertAdjacentHTML('beforeend', data.html);

                        // Update offset
                        offset += limit;

                        // Restore button
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    })
                    .catch(err => {
                        console.error(err);
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                        showMessage('Something went wrong. Try again.');
                    });
            });
        }

        initLoadMore({
            button: '#loadMoreFirstBlock',
            container: '#firstTourBlockContainer',
            blockConfig: @json($tours->pluck('id')),
            limit: 16,
            colClass: 'col-md-3',
            cardStyle: 'style1',
            searchQuery: '{{ request('search') ?? '' }}'
        });

        document.addEventListener("DOMContentLoaded", function() {
            const sortSelect = document.getElementById("sort_by");

            if (sortSelect) {
                sortSelect.addEventListener("change", function() {
                    const url = new URL(window.location.href);
                    const selectedValue = sortSelect.value;

                    if (selectedValue) {
                        url.searchParams.set("sort_by", selectedValue);
                    } else {
                        url.searchParams.delete("sort_by");
                    }

                    // Add the hash to scroll to the tours section
                    url.hash = "tours";

                    window.location.href = url.toString();
                });
            }
        });
    </script>
@endpush
