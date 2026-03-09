<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(B2bVendor::class, 'b2b_vendor_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
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
