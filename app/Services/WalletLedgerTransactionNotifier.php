<?php

namespace App\Services;

use App\Mail\WalletLedgerTransactionMail;
use App\Models\B2bWalletLedger;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WalletLedgerTransactionNotifier
{
    public function notify(B2bWalletLedger $entry): void
    {
        $entry->loadMissing('vendor');

        $vendor = $entry->vendor;
        $email = filter_var($vendor?->email ?? '', FILTER_VALIDATE_EMAIL);

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new WalletLedgerTransactionMail($entry));
        } catch (Exception $e) {
            Log::error('Wallet ledger transaction email failed', [
                'ledger_id' => $entry->id,
                'vendor_id' => $entry->b2b_vendor_id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
