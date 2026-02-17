<?php

use Illuminate\Support\Facades\Auth;

$user = Auth::user();

$menu = [
    [
        'title' => 'Dashboard',
        'icon' => 'bx bxs-home',
        'route' => route('user.dashboard'),
    ],
    [
        'title' => 'My Tour Bookings',
        'icon' => 'bx bxs-shopping-bag',
        'route' => route('user.orders.index'),
    ],
    [
        'title' => 'My Insurance',
        'icon' => 'bx bxs-shield',
        'route' => route('user.travel-insurances.index'),
    ],
    [
        'title' => 'My Hotel Bookings',
        'icon'  => 'bx bx-restaurant',
        'route' => route('user.hotels.index'),
    ],
];

if ($user && $user->auth_provider !== 'google') {
    $menu[] = [
        'title' => 'Account Settings',
        'icon' => 'bx bxs-cog',
        'submenu' => [
            [
                'title' => 'Change Password',
                'icon' => 'bx bx-key',
                'route' => route('user.profile.changePassword'),
            ],
        ],
    ];
}

return $menu;
