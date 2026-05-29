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

        if (! $vendor->hasCreditLimit()) {
            throw ValidationException::withMessages([
                'amount' => 'This recharge amount is not allowed.',
            ]);
        }

        $max = $vendor->maxRechargeAmount();

        if ($max < 100) {
            throw ValidationException::withMessages([
                'amount' => $vendor->rechargeBlockedMessage()
                    ?? 'You have reached your wallet recharge limit. To add more funds, please contact system administrator.',
            ]);
        }

        throw ValidationException::withMessages([
            'amount' => $vendor->rechargeBlockedMessage()
                ?? 'You have reached your wallet recharge limit. To add more funds, please contact your system administrator.',
        ]);
    }
}
