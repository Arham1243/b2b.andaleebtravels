<?php

namespace App\Observers;

use App\Models\B2bWalletLedger;
use App\Services\WalletLedgerTransactionNotifier;
use Illuminate\Support\Facades\DB;

class B2bWalletLedgerObserver
{
    public function __construct(
        protected WalletLedgerTransactionNotifier $notifier,
    ) {}

    public function created(B2bWalletLedger $entry): void
    {
        DB::afterCommit(function () use ($entry) {
            $this->notifier->notify($entry->fresh());
        });
    }
}
