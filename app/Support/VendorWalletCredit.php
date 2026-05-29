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

        if ($creditLimit <= 0) {
            $net = 0.0;

            foreach ($entries as $entry) {
                $amount = round((float) $entry->amount, 2);
                $net = $entry->isCredit()
                    ? round($net + $amount, 2)
                    : round($net - $amount, 2);
            }

            return [
                'prepaid' => round(max(0, $net), 2),
                'credit_used' => round(max(0, -$net), 2),
                'net' => round($net, 2),
            ];
        }

        $prepaid = 0.0;
        $creditUsed = 0.0;

        foreach ($entries as $entry) {
            $amount = round((float) $entry->amount, 2);

            if ($entry->isCredit()) {
                [$prepaid, $creditUsed] = self::applyCredit($prepaid, $creditUsed, $amount);
                continue;
            }

            [$prepaid, $creditUsed] = self::applyDebit($prepaid, $creditUsed, $amount, $creditLimit);
        }

        return [
            'prepaid' => round($prepaid, 2),
            'credit_used' => round($creditUsed, 2),
            'net' => round($prepaid - $creditUsed, 2),
        ];
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

    /**
     * Net wallet balance shown as "Available Balance" (main ledger position).
     */
    public static function availableBalance(float $prepaid, float $creditUsed, float $creditLimit): float
    {
        if ($creditLimit <= 0) {
            return round(max(0, $prepaid), 2);
        }

        return round(max(0, $prepaid - $creditUsed), 2);
    }

    /**
     * Maximum the vendor can spend on a new debit (includes unused credit line when applicable).
     */
    public static function maxSpendable(float $prepaid, float $creditUsed, float $creditLimit): float
    {
        if ($creditLimit <= 0) {
            return round(max(0, $prepaid), 2);
        }

        $prepaid = max(0, round($prepaid, 2));
        $creditUsed = max(0, round($creditUsed, 2));
        $net = round($prepaid - $creditUsed, 2);
        $creditAvailable = max(0, round($creditLimit - $creditUsed, 2));

        if ($creditUsed > 0) {
            return round(max(0, $net) + $creditAvailable, 2);
        }

        if ($prepaid >= $creditLimit) {
            return round($prepaid, 2);
        }

        return round($creditLimit, 2);
    }

    /** @deprecated Use availableBalance() or maxSpendable() explicitly. */
    public static function totalSpendable(float $prepaid, float $creditUsed, float $creditLimit): float
    {
        return self::maxSpendable($prepaid, $creditUsed, $creditLimit);
    }

    public static function syncVendorPools(B2bVendor $vendor): array
    {
        $pools = self::poolsFromLedger($vendor);

        $vendor->update([
            'main_balance' => $pools['net'],
            'credit_used' => $pools['credit_used'],
        ]);

        return $pools;
    }

    public static function currentPrepaid(B2bVendor $vendor): float
    {
        return round((float) $vendor->main_balance + (float) ($vendor->credit_used ?? 0), 2);
    }
}
