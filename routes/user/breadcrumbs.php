<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

Breadcrumbs::for('user.dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', route('user.dashboard'));
});

Breadcrumbs::for('user.profile.changePassword', function (BreadcrumbTrail $trail) {
    $trail->parent('user.dashboard');
    $trail->push('Change Password', route('user.profile.changePassword'));
});