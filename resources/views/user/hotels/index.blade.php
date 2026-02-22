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
                        <div class="hs-sidebar-card">
                            <div class="hs-sidebar-card__icon"><i class='bx bx-notepad'></i></div>
                            <div class="hs-sidebar-card__title">Notice Board</div>
                        </div>
                        <div class="hs-sidebar-card">
                            <div class="hs-sidebar-card__icon"><i class='bx bx-briefcase'></i></div>
                            <div class="hs-sidebar-card__title">Hold Itineraries</div>
                        </div>
                        <div class="hs-sidebar-card">
                            <div class="hs-sidebar-card__icon"><i class='bx bx-calendar-event'></i></div>
                            <div class="hs-sidebar-card__title">Travel Calendar</div>
                        </div>
                        <div class="hs-sidebar-card">
                            <div class="hs-sidebar-card__icon"><i class='bx bx-support'></i></div>
                            <div class="hs-sidebar-card__title">24/7 Support</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
