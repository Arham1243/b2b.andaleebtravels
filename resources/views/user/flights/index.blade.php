@extends('user.layouts.main')
@section('content')
    <div class="flight-page-shell my-4 @if(empty($flightPromosEnabled)) flight-page-shell--no-promos @endif">
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
                    @include('partials.support-strip-links')
                </div>
            </div>
        </div>
    </div>
@endsection
