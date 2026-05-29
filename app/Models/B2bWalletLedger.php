<?php

namespace App\Models;

use App\Support\WalletLedgerDescription;
use App\Support\VendorWalletCredit;
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
        'attachment_path',
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
        ?string $attachmentPath = null,
    ): self {
        return DB::transaction(function () use ($vendorId, $type, $amount, $description, $transactionAt, $adminId, $attachmentPath) {
            $vendor = B2bVendor::query()->whereKey($vendorId)->lockForUpdate()->firstOrFail();
            $creditLimit = $vendor->creditLimitAmount();
            $prepaid = VendorWalletCredit::currentPrepaid($vendor);
            $creditUsed = (float) ($vendor->credit_used ?? 0);
            $netBefore = round((float) $vendor->main_balance, 2);

            if ($type === 'credit') {
                if ($creditLimit > 0) {
                    [$prepaid, $creditUsed] = VendorWalletCredit::applyCredit($prepaid, $creditUsed, $amount);
                } else {
                    $netBefore = round($prepaid, 2);
                    $prepaid = round($prepaid + $amount, 2);
                }
            } else {
                if ($creditLimit > 0) {
                    [$prepaid, $creditUsed] = VendorWalletCredit::applyDebit($prepaid, $creditUsed, $amount, $creditLimit);
                } else {
                    $netBefore = round($prepaid, 2);
                    $prepaid = round($prepaid - $amount, 2);
                }
            }

            $netAfter = $creditLimit > 0
                ? VendorWalletCredit::netBalance($prepaid, $creditUsed)
                : round($prepaid, 2);

            $vendor->update([
                'main_balance' => round($netAfter, 2),
                'credit_used' => round($creditUsed, 2),
            ]);

            $entry = self::create([
                'b2b_vendor_id' => $vendorId,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => round($netBefore, 2),
                'balance_after' => round($netAfter, 2),
                'description' => WalletLedgerDescription::manualAdjustment($description),
                'attachment_path' => $attachmentPath,
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

    public function adminReasonLabel(bool $ignoreVoid = false): string
    {
        return WalletLedgerDescription::adminReasonLabel($this, $ignoreVoid);
    }

    public function adminReasonClass(bool $ignoreVoid = false): string
    {
        return WalletLedgerDescription::adminReasonClass($this, $ignoreVoid);
    }

    /** @return array{label: string, url: string|null} */
    public function adminReferenceLink(): array
    {
        return WalletLedgerDescription::adminReferenceLink($this);
    }

    /** @return array{label: string, url: string|null} */
    public function userReferenceLink(): array
    {
        return WalletLedgerDescription::userReferenceLink($this);
    }

    public static function recordCredit(int $vendorId, float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null): self
    {
        return DB::transaction(function () use ($vendorId, $amount, $description, $referenceType, $referenceId) {
            $vendor = B2bVendor::query()->whereKey($vendorId)->lockForUpdate()->firstOrFail();
            $creditLimit = $vendor->creditLimitAmount();
            $prepaid = VendorWalletCredit::currentPrepaid($vendor);
            $creditUsed = (float) ($vendor->credit_used ?? 0);
            $netBefore = round((float) $vendor->main_balance, 2);

            if ($creditLimit > 0) {
                [$prepaid, $creditUsed] = VendorWalletCredit::applyCredit($prepaid, $creditUsed, $amount);
            } else {
                $prepaid = round($prepaid + $amount, 2);
            }

            $netAfter = $creditLimit > 0
                ? VendorWalletCredit::netBalance($prepaid, $creditUsed)
                : round($prepaid, 2);

            $vendor->update([
                'main_balance' => round($netAfter, 2),
                'credit_used' => round($creditUsed, 2),
            ]);

            return self::create([
                'b2b_vendor_id' => $vendorId,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => round($netBefore, 2),
                'balance_after' => round($netAfter, 2),
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        });
    }

    public static function recordDebit(int $vendorId, float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null): self
    {
        return DB::transaction(function () use ($vendorId, $amount, $description, $referenceType, $referenceId) {
            $vendor = B2bVendor::query()->whereKey($vendorId)->lockForUpdate()->firstOrFail();
            $creditLimit = $vendor->creditLimitAmount();
            $prepaid = VendorWalletCredit::currentPrepaid($vendor);
            $creditUsed = (float) ($vendor->credit_used ?? 0);
            $netBefore = round((float) $vendor->main_balance, 2);

            if ($creditLimit > 0) {
                [$prepaid, $creditUsed] = VendorWalletCredit::applyDebit($prepaid, $creditUsed, $amount, $creditLimit);
            } else {
                $prepaid = round($prepaid - $amount, 2);
            }

            $netAfter = $creditLimit > 0
                ? VendorWalletCredit::netBalance($prepaid, $creditUsed)
                : round($prepaid, 2);

            $vendor->update([
                'main_balance' => round($netAfter, 2),
                'credit_used' => round($creditUsed, 2),
            ]);

            return self::create([
                'b2b_vendor_id' => $vendorId,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => round($netBefore, 2),
                'balance_after' => round($netAfter, 2),
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        });
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }

    public function attachmentUrl(): ?string
    {
        if (empty($this->attachment_path)) {
            return null;
        }

        return asset($this->attachment_path);
    }

    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_path);
    }
}
