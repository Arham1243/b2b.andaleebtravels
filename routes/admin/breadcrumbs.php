<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

Breadcrumbs::for('admin.dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', route('admin.dashboard'));
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
    $trail->push($vendor->display_agency_name ?: $vendor->name, route('admin.vendors.show', $vendor));
});

Breadcrumbs::for('admin.vendors.sub-agents.create', function (BreadcrumbTrail $trail, $vendor) {
    $trail->parent('admin.vendors.show', $vendor);
    $trail->push('Add Sub Agent', route('admin.vendors.sub-agents.create', $vendor));
});

Breadcrumbs::for('admin.vendors.edit', function (BreadcrumbTrail $trail, $vendor) {
    $trail->parent('admin.vendors.show', $vendor);
    $trail->push('Edit Vendor', route('admin.vendors.edit', $vendor));
});

Breadcrumbs::for('admin.vendors.pending.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.vendors.index');
    $trail->push('Signup Requests', route('admin.vendors.pending.index'));
});

Breadcrumbs::for('admin.vendors.pending.show', function (BreadcrumbTrail $trail, $vendor) {
    $trail->parent('admin.vendors.pending.index');
    $trail->push($vendor->display_agency_name ?: $vendor->name, route('admin.vendors.pending.show', $vendor));
});

Breadcrumbs::for('admin.wallet.bank-transfers.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.dashboard');
    $trail->push('Wallet Bank Transfers', route('admin.wallet.bank-transfers.index'));
});

Breadcrumbs::for('admin.inquiries.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.dashboard');
    $trail->push('Inquiries', route('admin.inquiries.index'));
});

Breadcrumbs::for('admin.inquiries.show', function (BreadcrumbTrail $trail, $inquiry) {
    $trail->parent('admin.inquiries.index');
    $trail->push('Inquiry Details', route('admin.inquiries.show', $inquiry));
});

Breadcrumbs::for('admin.hotel-bookings.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.dashboard');
    $trail->push('Hotel Bookings', route('admin.hotel-bookings.index'));
});

Breadcrumbs::for('admin.hotel-bookings.show', function (BreadcrumbTrail $trail, $booking) {
    $trail->parent('admin.hotel-bookings.index');
    $trail->push($booking->booking_number, route('admin.hotel-bookings.show', $booking));
});

Breadcrumbs::for('admin.flight-bookings.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.dashboard');
    $trail->push('Flight Bookings', route('admin.flight-bookings.index'));
});

Breadcrumbs::for('admin.flight-bookings.show', function (BreadcrumbTrail $trail, $booking) {
    $trail->parent('admin.flight-bookings.index');
    $trail->push($booking->booking_number, route('admin.flight-bookings.show', $booking));
});
