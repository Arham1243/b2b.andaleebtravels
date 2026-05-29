<?php

namespace App\Providers;

use App\Http\View\Composers\B2bAdminEditViewComposer;
use App\Models\B2bWalletLedger;
use App\Observers\B2bWalletLedgerObserver;
use Illuminate\Events\Dispatcher;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Dispatcher $events): void
    {
        Paginator::useBootstrapFive();
        B2bWalletLedger::observe(B2bWalletLedgerObserver::class);
        View::composer('admin.*', B2bAdminEditViewComposer::class);
    }
}
