<?php

namespace App\Services;

use App\Models\B2bVendor;
use App\Models\B2bWalletLedger;
use App\Support\VendorWalletCredit;
use App\Support\WalletLedgerDescription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminWalletLedgerAdjustmentService
{
    /**
     * @param  array{type: string, amount: float|string, description: string, transaction_date: string, transaction_time?: string|null}  $data
     */
    public function update(B2bWalletLedger $entry, array $data, int $adminId): B2bWalletLedger
    {
        if ($entry->isVoided()) {
            throw ValidationException::withMessages([
                'ledger' => 'Voided transactions cannot be edited.',
            ]);
        }

        $amount = round((float) $data['amount'], 2);
        $type = (string) $data['type'];

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Amount must be greater than zero.',
            ]);
        }

        $transactionAt = $this->parseTransactionAt(
            (string) $data['transaction_date'],
            $data['transaction_time'] ?? null
        );

        return DB::transaction(function () use ($entry, $type, $amount, $data, $adminId, $transactionAt) {
            B2bVendor::query()->whereKey($entry->b2b_vendor_id)->lockForUpdate()->firstOrFail();

            $description = $entry->is_manual
                ? WalletLedgerDescription::manualAdjustment((string) $data['description'])
                : trim((string) $data['description']);

            $updateData = [
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'modified_by_b2b_admin_id' => $adminId,
            ];

            if (array_key_exists('attachment_path', $data)) {
                $updateData['attachment_path'] = $data['attachment_path'];
            }

            $entry->update($updateData);

            $entry->created_at = $transactionAt;
            $entry->updated_at = $transactionAt;
            $entry->saveQuietly();

            $this->syncVendorBalanceFromLedger((int) $entry->b2b_vendor_id);

            return $entry->fresh();
        });
    }

    public function void(B2bWalletLedger $entry, int $adminId): B2bWalletLedger
    {
        if ($entry->isVoided()) {
            throw ValidationException::withMessages([
                'ledger' => 'This transaction is already voided.',
            ]);
        }

        return DB::transaction(function () use ($entry, $adminId) {
            B2bVendor::query()->whereKey($entry->b2b_vendor_id)->lockForUpdate()->firstOrFail();

            $entry->update([
                'voided_at' => now(),
                'voided_by_b2b_admin_id' => $adminId,
            ]);

            $this->syncVendorBalanceFromLedger((int) $entry->b2b_vendor_id);

            return $entry->fresh();
        });
    }

    /**
     * Rebuild balance_before / balance_after on all active ledger rows and sync vendor pools.
     */
    public function syncVendorBalanceFromLedger(int $vendorId): float
    {
        $vendor = B2bVendor::query()->whereKey($vendorId)->lockForUpdate()->firstOrFail();
        $creditLimit = $vendor->creditLimitAmount();

        $entries = B2bWalletLedger::query()
            ->where('b2b_vendor_id', $vendorId)
            ->active()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $prepaid = 0.0;
        $creditUsed = 0.0;
        $runningNet = 0.0;

        foreach ($entries as $ledgerEntry) {
            $amount = round((float) $ledgerEntry->amount, 2);
            $netBefore = $creditLimit > 0
                ? VendorWalletCredit::netBalance($prepaid, $creditUsed)
                : round($runningNet, 2);

            if ($ledgerEntry->isCredit()) {
                if ($creditLimit > 0) {
                    [$prepaid, $creditUsed] = VendorWalletCredit::applyCredit($prepaid, $creditUsed, $amount);
                } else {
                    $runningNet = round($runningNet + $amount, 2);
                }
            } else {
                if ($creditLimit > 0) {
                    [$prepaid, $creditUsed] = VendorWalletCredit::applyDebit($prepaid, $creditUsed, $amount, $creditLimit);
                } else {
                    $runningNet = round($runningNet - $amount, 2);
                }
            }

            $netAfter = $creditLimit > 0
                ? VendorWalletCredit::netBalance($prepaid, $creditUsed)
                : round($runningNet, 2);

            if (
                (float) $ledgerEntry->balance_before !== $netBefore
                || (float) $ledgerEntry->balance_after !== $netAfter
            ) {
                $ledgerEntry->balance_before = $netBefore;
                $ledgerEntry->balance_after = $netAfter;
                $ledgerEntry->saveQuietly();
            }
        }

        if ($creditLimit > 0) {
            if ($creditUsed > $creditLimit + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'This change would exceed the credit limit. Credit used would be ' . number_format($creditUsed, 2) . ' AED.',
                ]);
            }

            if ($prepaid < -0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'This change would overdraw the wallet beyond the available credit line.',
                ]);
            }

            $net = VendorWalletCredit::netBalance($prepaid, $creditUsed);

            $vendor->update([
                'main_balance' => round($net, 2),
                'credit_used' => round($creditUsed, 2),
            ]);

            return round($net, 2);
        }

        $finalBalance = round($runningNet, 2);

        if ($finalBalance < 0) {
            throw ValidationException::withMessages([
                'amount' => 'This change would make the wallet balance negative (' . number_format($finalBalance, 2) . ' AED). Adjust the transaction or void other entries first.',
            ]);
        }

        $vendor->update([
            'main_balance' => $finalBalance,
            'credit_used' => 0,
        ]);

        return $finalBalance;
    }

    private function parseTransactionAt(string $date, ?string $time): Carbon
    {
        $time = $time !== null && $time !== '' ? $time : '12:00';

        return Carbon::parse($date . ' ' . $time);
    }
}
