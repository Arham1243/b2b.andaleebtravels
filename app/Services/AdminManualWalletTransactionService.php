<?php

namespace App\Services;

use App\Models\B2bVendor;
use App\Models\B2bWalletLedger;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AdminManualWalletTransactionService
{
    /**
     * @param  array{type: string, amount: float|string, description: string, transaction_date: string, transaction_time?: string|null}  $data
     */
    public function store(B2bVendor $vendor, array $data, int $adminId): B2bWalletLedger
    {
        $amount = round((float) $data['amount'], 2);
        $type = (string) $data['type'];

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Amount must be greater than zero.',
            ]);
        }

        if ($type === 'debit' && (float) $vendor->main_balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient wallet balance. Available: ' . number_format((float) $vendor->main_balance, 2) . ' AED.',
            ]);
        }

        $transactionAt = $this->parseTransactionAt(
            (string) $data['transaction_date'],
            $data['transaction_time'] ?? null
        );

        $attachmentPath = $type === 'credit' ? ($data['attachment_path'] ?? null) : null;

        return B2bWalletLedger::recordManual(
            $vendor->id,
            $type,
            $amount,
            (string) $data['description'],
            $transactionAt,
            $adminId,
            $attachmentPath
        );
    }

    private function parseTransactionAt(string $date, ?string $time): Carbon
    {
        $time = $time !== null && $time !== '' ? $time : '12:00';

        return Carbon::parse($date . ' ' . $time);
    }
}
