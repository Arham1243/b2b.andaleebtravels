<?php

namespace App\Support;

use App\Models\B2bVendor;
use Illuminate\Validation\ValidationException;

final class VendorRechargeGuard
{
    public static function assertCanRecharge(B2bVendor $vendor, float $amount): void
    {
        $amount = round($amount, 2);

        if ($amount < 100 || $amount > 50000) {
            throw ValidationException::withMessages([
                'amount' => 'Recharge amount must be between 100 and 50,000 AED.',
            ]);
        }

        if ($vendor->canRechargeAmount($amount)) {
            return;
        }

        throw ValidationException::withMessages([
            'amount' => 'This recharge amount is not allowed.',
        ]);
    }
}
