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

        if ($entry->isUnpaidCreditSettlement()) {
            if ($type !== 'debit' || $amount !== round((float) $entry->amount, 2)) {
                throw ValidationException::withMessages([
                    'amount' => 'Settlement transactions cannot change type or amount.',
                ]);
            }
        } elseif ($entry->isUnpaidCredit()) {
            if ($type !== 'credit' || $amount !== round((float) $entry->amount, 2)) {
                throw ValidationException::withMessages([
                    'amount' => 'Unpaid credit transactions cannot change type or amount.',
                ]);
            }
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
                'description' => $description,
                'modified_by_b2b_admin_id' => $adminId,
            ];

            if (! $entry->isUnpaidCredit() && ! $entry->isUnpaidCreditSettlement()) {
                $updateData['type'] = $type;
                $updateData['amount'] = $amount;
            }

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

        if ($entry->isUnpaidCredit() && $entry->isSettled()) {
            throw ValidationException::withMessages([
                'ledger' => 'Void the payment received entry first before voiding this unpaid credit.',
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

        try {
            $pools = VendorWalletCredit::syncVendorWallet($vendor, true);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'amount' => $e->getMessage(),
            ]);
        }

        return $pools['net'];
    }

    private function parseTransactionAt(string $date, ?string $time): Carbon
    {
        $time = $time !== null && $time !== '' ? $time : '12:00';

        return Carbon::parse($date . ' ' . $time);
    }
}
