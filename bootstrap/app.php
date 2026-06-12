<?php

if (function_exists('ini_set')) {
    @ini_set('memory_limit', '512M');
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\TravelportPnrSsrsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'admin.staff_gate' => \App\Http\Middleware\B2bAdminPortalPermissionGate::class,
            'admin.super' => \App\Http\Middleware\EnsureB2bSuperAdmin::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'user_guest' => \App\Http\Middleware\RedirectUserIfAuthenticated::class,
            'check_user_status' => \App\Http\Middleware\CheckUserStatus::class,
            'agency_owner' => \App\Http\Middleware\EnsureAgencyOwner::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
