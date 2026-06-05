<?php

namespace App\Models;

use App\Support\WalletLedgerBalanceEffect;
use App\Support\WalletLedgerDescription;
use App\Support\VendorWalletCredit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class B2bWalletLedger extends Model
{
    public const KIND_UNPAID_CREDIT = 'unpaid_credit';

    public const KIND_UNPAID_CREDIT_SETTLEMENT = 'unpaid_credit_settlement';

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
        'manual_kind',
        'settles_ledger_id',
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

    public function settlesLedger(): BelongsTo
    {
        return $this->belongsTo(self::class, 'settles_ledger_id');
    }

    public function settlementEntries(): HasMany
    {
        return $this->hasMany(self::class, 'settles_ledger_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('voided_at');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function isUnpaidCredit(): bool
    {
        return $this->manual_kind === self::KIND_UNPAID_CREDIT;
    }

    public function isUnpaidCreditSettlement(): bool
    {
        return $this->manual_kind === self::KIND_UNPAID_CREDIT_SETTLEMENT;
    }

    public function isSettled(): bool
    {
        if (! $this->isUnpaidCredit()) {
            return false;
        }

        if ($this->relationLoaded('settlementEntries')) {
            return $this->settlementEntries->contains(fn (self $entry) => $entry->voided_at === null);
        }

        return $this->settlementEntries()->active()->exists();
    }

    public function affectsWalletBalance(): bool
    {
        return WalletLedgerBalanceEffect::affectsWalletBalance($this);
    }

    public static function recordManual(
        int $vendorId,
        string $type,
        float $amount,
        string $description,
        Carbon $transactionAt,
        int $adminId,
        ?string $attachmentPath = null,
        ?string $manualKind = null,
        ?int $settlesLedgerId = null,
    ): self {
        return DB::transaction(function () use ($vendorId, $type, $amount, $description, $transactionAt, $adminId, $attachmentPath, $manualKind, $settlesLedgerId) {
            B2bVendor::query()->whereKey($vendorId)->lockForUpdate()->firstOrFail();
            $isSettlement = $manualKind === self::KIND_UNPAID_CREDIT_SETTLEMENT;
            $netBefore = 0.0;
            $netAfter = 0.0;

            $entry = self::create([
                'b2b_vendor_id' => $vendorId,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $netBefore,
                'balance_after' => $netAfter,
                'description' => WalletLedgerDescription::manualAdjustment($description),
                'attachment_path' => $attachmentPath,
                'is_manual' => true,
                'manual_kind' => $manualKind,
                'settles_ledger_id' => $settlesLedgerId,
                'recorded_by_b2b_admin_id' => $adminId,
            ]);

            $entry->created_at = $transactionAt;
            $entry->updated_at = $transactionAt;
            $entry->saveQuietly();

            if (! $isSettlement) {
                $vendor = B2bVendor::query()->whereKey($vendorId)->firstOrFail();
                VendorWalletCredit::syncVendorWallet($vendor, true);
            }

            return $entry->fresh();
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
            B2bVendor::query()->whereKey($vendorId)->lockForUpdate()->firstOrFail();

            $entry = self::create([
                'b2b_vendor_id' => $vendorId,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => 0,
                'balance_after' => 0,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            $vendor = B2bVendor::query()->whereKey($vendorId)->firstOrFail();
            VendorWalletCredit::syncVendorWallet($vendor, true);

            return $entry->fresh();
        });
    }

    public static function recordDebit(int $vendorId, float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null): self
    {
        return DB::transaction(function () use ($vendorId, $amount, $description, $referenceType, $referenceId) {
            B2bVendor::query()->whereKey($vendorId)->lockForUpdate()->firstOrFail();

            $entry = self::create([
                'b2b_vendor_id' => $vendorId,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => 0,
                'balance_after' => 0,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            $vendor = B2bVendor::query()->whereKey($vendorId)->firstOrFail();
            VendorWalletCredit::syncVendorWallet($vendor, true);

            return $entry->fresh();
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
