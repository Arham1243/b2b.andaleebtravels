<?php

namespace App\Support;

use App\Models\B2bVendor;
use App\Models\B2bWalletLedger;
use Illuminate\Support\Collection;

final class VendorWalletCredit
{
    /**
     * @return array{prepaid: float, credit_used: float, net: float}
     */
    public static function poolsFromLedger(B2bVendor $vendor): array
    {
        $entries = B2bWalletLedger::query()
            ->where('b2b_vendor_id', $vendor->id)
            ->active()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return self::replayEntries($vendor, $entries);
    }

    /**
     * @param  Collection<int, B2bWalletLedger>  $entries
     * @return array{prepaid: float, credit_used: float, net: float}
     */
    public static function replayEntries(B2bVendor $vendor, Collection $entries): array
    {
        $creditLimit = $vendor->creditLimitAmount();
        $effectiveCreditLimit = self::effectiveCreditLimit($vendor);
        $prepaid = 0.0;
        $creditUsed = 0.0;

        foreach ($entries as $entry) {
            if (! WalletLedgerBalanceEffect::affectsWalletBalance($entry)) {
                continue;
            }

            $amount = round((float) $entry->amount, 2);

            if ($entry->isCredit()) {
                [$prepaid, $creditUsed] = self::applyCredit($prepaid, $creditUsed, $amount);
                continue;
            }

            [$prepaid, $creditUsed] = self::applyDebit($prepaid, $creditUsed, $amount, $effectiveCreditLimit);
        }

        return [
            'prepaid' => round($prepaid, 2),
            'credit_used' => round($creditUsed, 2),
            'net' => self::totalWalletBalance($prepaid, $creditUsed, $creditLimit),
        ];
    }

    /**
     * Credit cap used when replaying ledger debits (preserves draws after limit is removed).
     */
    public static function effectiveCreditLimit(B2bVendor $vendor): float
    {
        return max($vendor->creditLimitAmount(), (float) ($vendor->credit_used ?? 0));
    }

    /**
     * @return array{0: float, 1: float}
     */
    public static function applyCredit(float $prepaid, float $creditUsed, float $amount): array
    {
        $amount = round($amount, 2);

        if ($creditUsed > 0) {
            $paydown = min($amount, $creditUsed);
            $creditUsed = round($creditUsed - $paydown, 2);
            $amount = round($amount - $paydown, 2);
        }

        $prepaid = round($prepaid + $amount, 2);

        return [$prepaid, $creditUsed];
    }

    /**
     * Debits consume the credit line first, then prepaid wallet.
     *
     * @return array{0: float, 1: float}
     */
    public static function applyDebit(float $prepaid, float $creditUsed, float $amount, float $creditLimit): array
    {
        $amount = round($amount, 2);
        $creditAvailable = max(0, round($creditLimit - $creditUsed, 2));
        $fromCredit = min($creditAvailable, $amount);

        $creditUsed = round($creditUsed + $fromCredit, 2);
        $amount = round($amount - $fromCredit, 2);
        $prepaid = round($prepaid - $amount, 2);

        return [$prepaid, $creditUsed];
    }

    public static function netBalance(float $prepaid, float $creditUsed): float
    {
        return round($prepaid - $creditUsed, 2);
    }

    /** Admin-assigned credit still available (credit_limit minus amount already drawn). */
    public static function creditRemaining(float $creditUsed, float $creditLimit): float
    {
        return round(max(0, $creditLimit - $creditUsed), 2);
    }

    /**
     * Total wallet balance: prepaid + credit_limit - credit_used (may be negative).
     */
    public static function totalWalletBalance(float $prepaid, float $creditUsed, float $creditLimit): float
    {
        return round($prepaid + $creditLimit - $creditUsed, 2);
    }

    /**
     * Net wallet balance shown as "Available Balance" (may be negative).
     */
    public static function availableBalance(float $prepaid, float $creditUsed, float $creditLimit): float
    {
        return self::totalWalletBalance($prepaid, $creditUsed, $creditLimit);
    }

    /**
     * Maximum the vendor can spend on a new debit (never below zero).
     */
    public static function maxSpendable(float $prepaid, float $creditUsed, float $creditLimit): float
    {
        return round(max(0, self::totalWalletBalance($prepaid, $creditUsed, $creditLimit)), 2);
    }

    /** @deprecated Use availableBalance() or maxSpendable() explicitly. */
    public static function totalSpendable(float $prepaid, float $creditUsed, float $creditLimit): float
    {
        return self::maxSpendable($prepaid, $creditUsed, $creditLimit);
    }

    public static function syncVendorPools(B2bVendor $vendor): array
    {
        return self::syncVendorWallet($vendor, false);
    }

    /**
     * Replay ledger pools, rebuild ledger running balances, and sync vendor wallet fields.
     *
     * @return array{prepaid: float, credit_used: float, net: float}
     */
    public static function syncVendorWallet(B2bVendor $vendor, bool $throwOnInvalid = false): array
    {
        $creditLimit = $vendor->creditLimitAmount();
        $effectiveCreditLimit = self::effectiveCreditLimit($vendor);

        $entries = B2bWalletLedger::query()
            ->where('b2b_vendor_id', $vendor->id)
            ->active()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $prepaid = 0.0;
        $creditUsed = 0.0;

        foreach ($entries as $ledgerEntry) {
            $amount = round((float) $ledgerEntry->amount, 2);
            $totalBefore = self::totalWalletBalance($prepaid, $creditUsed, $creditLimit);

            if (WalletLedgerBalanceEffect::affectsWalletBalance($ledgerEntry)) {
                if ($ledgerEntry->isCredit()) {
                    [$prepaid, $creditUsed] = self::applyCredit($prepaid, $creditUsed, $amount);
                } else {
                    [$prepaid, $creditUsed] = self::applyDebit($prepaid, $creditUsed, $amount, $effectiveCreditLimit);
                }
            }

            $totalAfter = self::totalWalletBalance($prepaid, $creditUsed, $creditLimit);

            if (
                (float) $ledgerEntry->balance_before !== $totalBefore
                || (float) $ledgerEntry->balance_after !== $totalAfter
            ) {
                $ledgerEntry->balance_before = $totalBefore;
                $ledgerEntry->balance_after = $totalAfter;
                $ledgerEntry->saveQuietly();
            }
        }

        $net = self::totalWalletBalance($prepaid, $creditUsed, $creditLimit);

        if (
            $throwOnInvalid
            && $net < -0.001
            && $creditUsed < 0.001
            && $creditLimit < 0.001
        ) {
            throw new \InvalidArgumentException(
                'This change would make the wallet balance negative (' . number_format($net, 2) . ' AED).'
            );
        }

        $vendor->update([
            'main_balance' => $net,
            'credit_used' => round($creditUsed, 2),
        ]);

        $vendor->refresh();
        $vendor->creditPoolsCache = null;

        return [
            'prepaid' => round($prepaid, 2),
            'credit_used' => round($creditUsed, 2),
            'net' => $net,
        ];
    }

    public static function currentPrepaid(B2bVendor $vendor): float
    {
        return round((float) $vendor->main_balance + (float) ($vendor->credit_used ?? 0), 2);
    }
}
