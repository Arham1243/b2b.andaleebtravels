@extends('user.layouts.main')
@section('content')
    <div class="flight-page-shell my-4">
        <div class="container px-2 px-lg-3">
            @include('user.vue.main', [
                'appId' => 'flights-search',
                'appComponent' => 'flights-search',
                'appJs' => 'flights-search',
            ])

            {{-- Contact Support Strip --}}
            <div class="hs-support-strip mt-4">
                <div class="hs-support-strip__left">
                    <i class='bx bx-support'></i>
                    <div>
                        <div class="hs-support-strip__title">Flight Support</div>
                        <div class="hs-support-strip__sub">International / Domestic Flights</div>
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
