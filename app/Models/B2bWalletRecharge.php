<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2bWalletRecharge extends Model
{
    protected $fillable = [
        'b2b_vendor_id',
        'transaction_number',
        'amount',
        'currency',
        'payment_method',
        'status',
        'payment_reference',
        'tabby_payment_id',
        'payment_response',
        'failure_reason',
        'ip_address',
        'user_agent',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public static function generateTransactionNumber(): string
    {
        do {
            $number = 'B2BWR' . date('Ymd') . rand(1000, 9999);
        } while (self::where('transaction_number', $number)->exists());

        return $number;
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(B2bVendor::class, 'b2b_vendor_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
