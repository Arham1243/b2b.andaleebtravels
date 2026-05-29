@extends('user.layouts.main')

@section('css')
    @include('user.profile-settings._styles')
    @include('partials.wallet-ledger-styles')
@endsection

@section('content')
<div class="ps">
    <div class="container">

        <div class="ps-page-head">
            <div class="ps-page-head__icon">
                <i class="bx bx-wallet"></i>
            </div>
            <div>
                <h1 class="ps-page-head__title">Account Settings</h1>
                <p class="ps-page-head__sub">Wallet ledger - all your transactions</p>
            </div>
        </div>

        <div class="ps-shell">

            @include('user.profile-settings._sidebar')

            <main class="ps-main">
                <div class="ps-card">
                    <div class="ps-card__head">
                        <h2 class="ps-card__title">
                            <i class="bx bx-wallet"></i> Wallet Ledger
                        </h2>
                        <span class="ps-field__hint" style="margin:0;">
                            {{ $ledgerTotalCount }} {{ $ledgerTotalCount === 1 ? 'transaction' : 'transactions' }}
                        </span>
                    </div>
                    <div class="ps-card__body">
                        @include('partials.wallet-ledger-panel', [
                            'vendor' => $user,
                            'walletLedger' => $walletLedger,
                            'ledgerFilters' => $ledgerFilters,
                            'ledgerTotalCount' => $ledgerTotalCount,
                            'filterFormAction' => route('user.profile.walletLedger'),
                            'clearFiltersUrl' => route('user.profile.walletLedger'),
                            'readOnly' => true,
                            'ledgerContext' => 'user',
                            'filterApplyClass' => 'ps-btn-save',
                        ])
                    </div>
                </div>
            </main>

        </div>
    </div>
</div>
@endsection
