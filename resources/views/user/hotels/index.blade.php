@extends('user.layouts.main')
@section('content')
    <div class="my-4">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    @include('user.vue.main', [
                        'appId' => 'hotels-search',
                        'appComponent' => 'hotels-search',
                        'appJs' => 'hotels-search',
                    ])

                </div>
                <div class="col-md-4">
                    <div class="hs-sidebar-grid">
                        <div class="hs-sidebar-card hs-sidebar-card--yellow">
                            <div class="hs-sidebar-card__icon"><i class='bx bxs-bell-ring'></i></div>
                            <div class="hs-sidebar-card__title">Notice Board</div>
                        </div>
                        <div class="hs-sidebar-card hs-sidebar-card--teal">
                            <div class="hs-sidebar-card__icon"><i class='bx bx-time-five'></i></div>
                            <div class="hs-sidebar-card__title">Hold Itineraries</div>
                        </div>
                        <div class="hs-sidebar-card hs-sidebar-card--mint">
                            <div class="hs-sidebar-card__icon"><i class='bx bxs-calendar-event'></i></div>
                            <div class="hs-sidebar-card__title">Travel Calendar</div>
                        </div>
                        <div class="hs-sidebar-card hs-sidebar-card--blue">
                            <div class="hs-sidebar-card__icon"><i class='bx bx-support'></i></div>
                            <div class="hs-sidebar-card__title">24/7 Support</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contact Support Strip --}}
            <div class="hs-support-strip">
                <div class="hs-support-strip__left">
                    <i class='bx bx-support'></i>
                    <div>
                        <div class="hs-support-strip__title">Hotel Support</div>
                        <div class="hs-support-strip__sub">International / Domestic Hotel</div>
                    </div>
                </div>
                <div class="hs-support-strip__right">
                    <a href="https://api.whatsapp.com/send?phone=+971525748986&text=I%27m%20interested%20in%20your%20services" class="hs-support-strip__link">
                        <i class='bx bxl-whatsapp'></i> +971 525748986
                    </a>

                    <a href="mailto:info@andaleebtours.com" class="hs-support-strip__link">
                        <i class='bx bxs-envelope'></i> info@andaleebtours.com
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
