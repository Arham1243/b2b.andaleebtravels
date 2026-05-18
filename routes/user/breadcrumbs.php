<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

Breadcrumbs::for('user.dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', route('user.dashboard'));
});

Breadcrumbs::for('user.profile.personalInfo', function (BreadcrumbTrail $trail) {
    $trail->parent('user.dashboard');
    $trail->push('Personal Information', route('user.profile.personalInfo'));
});

Breadcrumbs::for('user.profile.changePassword', function (BreadcrumbTrail $trail) {
    $trail->parent('user.dashboard');
    $trail->push('Change Password', route('user.profile.changePassword'));
});

Breadcrumbs::for('user.wallet.recharge.card', function (BreadcrumbTrail $trail) {
    $trail->parent('user.dashboard');
    $trail->push('Recharge - Card', route('user.wallet.recharge.card'));
});

Breadcrumbs::for('user.wallet.recharge.tabby', function (BreadcrumbTrail $trail) {
    $trail->parent('user.dashboard');
    $trail->push('Recharge - Tabby', route('user.wallet.recharge.tabby'));
});

Breadcrumbs::for('user.wallet.recharge.bank-transfer', function (BreadcrumbTrail $trail) {
    $trail->parent('user.dashboard');
    $trail->push('Recharge - Bank Transfer', route('user.wallet.recharge.bank-transfer'));
});

Breadcrumbs::for('user.sub-agents.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user.dashboard');
    $trail->push('Sub Agents', route('user.sub-agents.index'));
});

Breadcrumbs::for('user.sub-agents.create', function (BreadcrumbTrail $trail) {
    $trail->parent('user.sub-agents.index');
    $trail->push('Add Sub Agent', route('user.sub-agents.create'));
});
