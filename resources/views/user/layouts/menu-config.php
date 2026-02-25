<?php
return [
    [
        'title' => 'Dashboard',
        'icon' => 'bx bxs-home',
        'route' => route('user.dashboard'),
    ],
    [
        'title' => 'My Bookings',
        'icon' => 'bx bxs-book-content',
        'route' => route('user.bookings.index'),
    ],
    [
        'title' => 'Account Settings',
        'icon' => 'bx bxs-cog',
        'submenu' => [
            [
                'title' => 'Personal Info',
                'icon' => 'bx bx-user',
                'route' => route('user.profile.personalInfo'),
            ],
            [
                'title' => 'Change Password',
                'icon' => 'bx bx-key',
                'route' => route('user.profile.changePassword'),
            ],
        ],
    ],
];
