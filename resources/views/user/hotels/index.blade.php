@extends('user.layouts.main')
@section('content')
    <div class="flight-page-shell my-4">
        <div class="container px-2 px-lg-3">
            @include('user.vue.main', [
                'appId' => 'hotels-search',
                'appComponent' => 'hotels-search',
                'appJs' => 'hotels-search',
            ])

            {{-- Contact Support Strip --}}
            <div class="hs-support-strip mt-4">
                <div class="hs-support-strip__left">
                    <i class='bx bx-support'></i>
                    <div>
                        <div class="hs-support-strip__title">Hotel Support</div>
                        <div class="hs-support-strip__sub">International / Domestic Hotel</div>
                    </div>
                </div>
                <div class="hs-support-strip__right">
                    @include('partials.support-strip-links')
                </div>
            </div>
        </div>
    </div>
@endsection
