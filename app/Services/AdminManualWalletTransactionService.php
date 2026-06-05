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
        $submittedType = (string) $data['type'];

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Amount must be greater than zero.',
            ]);
        }

        [$ledgerType, $manualKind, $settlesLedgerId] = $this->resolveManualTransactionShape($submittedType);

        if ($ledgerType === 'debit' && $manualKind === null && ! $vendor->canDebitAmount($amount)) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient wallet balance. Available to spend: ' . number_format($vendor->availableBalanceAmount(), 2) . ' AED (max spendable: ' . number_format($vendor->totalSpendableBalance(), 2) . ' AED).',
            ]);
        }

        $transactionAt = $this->parseTransactionAt(
            (string) $data['transaction_date'],
            $data['transaction_time'] ?? null
        );

        $attachmentPath = $data['attachment_path'] ?? null;

        return B2bWalletLedger::recordManual(
            $vendor->id,
            $ledgerType,
            $amount,
            (string) $data['description'],
            $transactionAt,
            $adminId,
            $attachmentPath,
            $manualKind,
            $settlesLedgerId
        );
    }

    /**
     * @return array{0: string, 1: string|null, 2: int|null}
     */
    private function resolveManualTransactionShape(string $submittedType): array
    {
        return match ($submittedType) {
            'credit' => ['credit', null, null],
            'debit' => ['debit', null, null],
            default => throw ValidationException::withMessages([
                'type' => 'Invalid transaction type selected.',
            ]),
        };
    }

    private function parseTransactionAt(string $date, ?string $time): Carbon
    {
        $time = $time !== null && $time !== '' ? $time : '12:00';

        return Carbon::parse($date . ' ' . $time);
    }
}
