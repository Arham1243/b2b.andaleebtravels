<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

Breadcrumbs::for('admin.dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', route('admin.dashboard'));
});

Breadcrumbs::for('admin.users.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.dashboard');
    $trail->push('Manage Users', route('admin.users.index'));
});

Breadcrumbs::for('admin.package-inquiries.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.dashboard');
    $trail->push('Package Inquiries', route('admin.package-inquiries.index'));
});

Breadcrumbs::for('admin.package-inquiries.show', function (BreadcrumbTrail $trail, $packageInquiry) {
    $trail->parent('admin.package-inquiries.index');
    $trail->push('Inquiry Details', route('admin.package-inquiries.show', $packageInquiry));
});
