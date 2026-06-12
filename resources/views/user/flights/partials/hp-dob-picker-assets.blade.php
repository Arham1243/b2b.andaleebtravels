@push('css')
    <link rel="stylesheet" href="{{ asset('user/assets/css/daterangepicker.css') }}" />
    <style>
        @include('user.flights.partials.hp-date-picker-styles')
        .hp-date-field .daterangepicker {
            z-index: 1065;
        }
    </style>
@endpush

@push('js')
    <script src="{{ asset('user/assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('user/assets/js/daterangepicker.min.js') }}"></script>
    @include('user.flights.partials.hp-date-picker-scripts')
    @include('user.flights.partials.hp-passenger-dob-scripts')
@endpush
