<?php

namespace App\Models;

use App\Support\WalletLedgerDescription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class B2bWalletLedger extends Model
{
    protected $table = 'b2b_wallet_ledger';

    protected $fillable = [
        'b2b_vendor_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'is_manual',
        'recorded_by_b2b_admin_id',
        'voided_at',
        'voided_by_b2b_admin_id',
        'modified_by_b2b_admin_id',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'is_manual' => 'boolean',
        'voided_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(B2bVendor::class, 'b2b_vendor_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function recordedByAdmin(): BelongsTo
    {
        return $this->belongsTo(B2bAdmin::class, 'recorded_by_b2b_admin_id');
    }

    public function voidedByAdmin(): BelongsTo
    {
        return $this->belongsTo(B2bAdmin::class, 'voided_by_b2b_admin_id');
    }

    public function modifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(B2bAdmin::class, 'modified_by_b2b_admin_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('voided_at');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public static function recordManual(
        int $vendorId,
        string $type,
        float $amount,
        string $description,
        Carbon $transactionAt,
        int $adminId,
    ): self {
        return DB::transaction(function () use ($vendorId, $type, $amount, $description, $transactionAt, $adminId) {
            $vendor = B2bVendor::query()->whereKey($vendorId)->lockForUpdate()->firstOrFail();
            $balanceBefore = (float) $vendor->main_balance;
            $balanceAfter = $type === 'credit'
                ? $balanceBefore + $amount
                : $balanceBefore - $amount;

            $vendor->update(['main_balance' => round($balanceAfter, 2)]);

            $entry = self::create([
                'b2b_vendor_id' => $vendorId,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => round($balanceBefore, 2),
                'balance_after' => round($balanceAfter, 2),
                'description' => WalletLedgerDescription::manualAdjustment($description),
                'is_manual' => true,
                'recorded_by_b2b_admin_id' => $adminId,
            ]);

            $entry->created_at = $transactionAt;
            $entry->updated_at = $transactionAt;
            $entry->saveQuietly();

            return $entry;
        });
    }

    public static function refundCreditExists(string $referenceType, int $referenceId): bool
    {
        return self::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('type', 'credit')
            ->where(function ($query) {
                $query->where('description', 'like', 'Refund — %')
                    ->orWhere('description', 'like', 'Refund - %');
            })
            ->exists();
    }

    public function adminReasonLabel(): string
    {
        return WalletLedgerDescription::adminReasonLabel($this);
    }

    public function adminReasonClass(): string
    {
        return WalletLedgerDescription::adminReasonClass($this);
    }

    /** @return array{label: string, url: string|null} */
    public function adminReferenceLink(): array
    {
        return WalletLedgerDescription::adminReferenceLink($this);
    }

    public static function recordCredit(int $vendorId, float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null): self
    {
        $vendor = B2bVendor::findOrFail($vendorId);
        $balanceBefore = (float) $vendor->main_balance;
        $balanceAfter = $balanceBefore + $amount;

        $vendor->update(['main_balance' => $balanceAfter]);

        return self::create([
            'b2b_vendor_id' => $vendorId,
            'type' => 'credit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    public static function recordDebit(int $vendorId, float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null): self
    {
        $vendor = B2bVendor::findOrFail($vendorId);
        $balanceBefore = (float) $vendor->main_balance;
        $balanceAfter = $balanceBefore - $amount;

        $vendor->update(['main_balance' => $balanceAfter]);

        return self::create([
            'b2b_vendor_id' => $vendorId,
            'type' => 'debit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }
}
