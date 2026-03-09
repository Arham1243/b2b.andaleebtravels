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

Breadcrumbs::for('admin.vendors.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.dashboard');
    $trail->push('Manage Vendors', route('admin.vendors.index'));
});

Breadcrumbs::for('admin.vendors.create', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.vendors.index');
    $trail->push('Add Vendor', route('admin.vendors.create'));
});

Breadcrumbs::for('admin.vendors.show', function (BreadcrumbTrail $trail, $vendor) {
    $trail->parent('admin.vendors.index');
    $trail->push($vendor->name, route('admin.vendors.show', $vendor));
});

Breadcrumbs::for('admin.inquiries.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.dashboard');
    $trail->push('Inquiries', route('admin.inquiries.index'));
});

Breadcrumbs::for('admin.inquiries.show', function (BreadcrumbTrail $trail, $inquiry) {
    $trail->parent('admin.inquiries.index');
    $trail->push('Inquiry Details', route('admin.inquiries.show', $inquiry));
});
