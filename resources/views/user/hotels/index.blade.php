@extends('user.layouts.main')
@section('content')
    @include('user.vue.main', [
        'appId' => 'hotels-search',
        'appComponent' => 'hotels-search',
        'appJs' => 'hotels-search',
    ])
@endsection
