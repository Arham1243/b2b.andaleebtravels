<?php

return [
    [
        'title' => 'Dashboard',
        'icon' => 'bx bxs-home',
        'route' => route('admin.dashboard'),
    ],
    [
        'title' => 'Users',
        'icon' => 'bx bxs-group',
        'route' => route('admin.users.index'),
    ],
    [
        'title' => 'Vendors',
        'icon' => 'bx bxs-briefcase',
        'route' => route('admin.vendors.index'),
    ],
    [
        'title' => 'Inquiries',
        'icon' => 'bx bxs-message-dots',
        'route' => route('admin.inquiries.index'),
    ],
    [
        'title' => 'Layout',
        'icon' => 'bx bxs-cog',
        'submenu' => [
            [
                'title' => 'Logo Management',
                'icon' => 'bx bx-image',
                'route' => route('admin.settings.logo'),
            ],
            [
                'title' => 'Configuration',
                'icon' => 'bx bxs-contact',
                'route' => route('admin.settings.details'),
            ],
        ],
    ],
];
