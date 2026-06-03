<?php

namespace App\Services;

use App\Models\B2bVendor;
use App\Models\B2bWalletLedger;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AdminManualWalletTransactionService
{
    /**
     * @param  array{type: string, amount: float|string, description: string, transaction_date: string, transaction_time?: string|null, settles_ledger_id?: int|null}  $data
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

        [$ledgerType, $manualKind, $settlesLedgerId] = $this->resolveManualTransactionShape($submittedType, $data, $vendor, $amount);

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
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string|null, 2: int|null}
     */
    private function resolveManualTransactionShape(string $submittedType, array $data, B2bVendor $vendor, float $amount): array
    {
        return match ($submittedType) {
            'unpaid_credit' => ['credit', B2bWalletLedger::KIND_UNPAID_CREDIT, null],
            'unpaid_credit_settlement' => $this->resolveSettlementShape($data, $vendor, $amount),
            'credit' => ['credit', null, null],
            'debit' => ['debit', null, null],
            default => throw ValidationException::withMessages([
                'type' => 'Invalid transaction type selected.',
            ]),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string, 2: int}
     */
    private function resolveSettlementShape(array $data, B2bVendor $vendor, float $amount): array
    {
        $settlesLedgerId = (int) ($data['settles_ledger_id'] ?? 0);

        if ($settlesLedgerId <= 0) {
            throw ValidationException::withMessages([
                'settles_ledger_id' => 'Select which unpaid credit this payment settles.',
            ]);
        }

        $target = B2bWalletLedger::query()
            ->whereKey($settlesLedgerId)
            ->where('b2b_vendor_id', $vendor->id)
            ->active()
            ->first();

        if ($target === null || ! $target->isUnpaidCredit()) {
            throw ValidationException::withMessages([
                'settles_ledger_id' => 'The selected unpaid credit entry is invalid or no longer available.',
            ]);
        }

        if ($target->isSettled()) {
            throw ValidationException::withMessages([
                'settles_ledger_id' => 'That unpaid credit has already been settled.',
            ]);
        }

        if (round((float) $target->amount, 2) !== $amount) {
            throw ValidationException::withMessages([
                'amount' => 'Amount must exactly match the selected unpaid credit (' . number_format((float) $target->amount, 2) . ' AED).',
            ]);
        }

        return ['debit', B2bWalletLedger::KIND_UNPAID_CREDIT_SETTLEMENT, $settlesLedgerId];
    }

    private function parseTransactionAt(string $date, ?string $time): Carbon
    {
        $time = $time !== null && $time !== '' ? $time : '12:00';

        return Carbon::parse($date . ' ' . $time);
    }
}
