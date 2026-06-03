<?php

namespace App\Support;

use App\Models\B2bWalletLedger;

final class WalletLedgerBalanceEffect
{
    public static function affectsWalletBalance(B2bWalletLedger $entry): bool
    {
        return $entry->manual_kind !== B2bWalletLedger::KIND_UNPAID_CREDIT_SETTLEMENT;
    }

    public static function delta(B2bWalletLedger $entry): float
    {
        if (! self::affectsWalletBalance($entry)) {
            return 0.0;
        }

        $amount = round((float) $entry->amount, 2);

        return $entry->isCredit() ? $amount : -$amount;
    }

    public static function apply(B2bWalletLedger $entry, float $runningNet): float
    {
        return round($runningNet + self::delta($entry), 2);
    }
}
