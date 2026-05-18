<?php

namespace App\Support;

use Carbon\Carbon;

final class HotelRefundPresentation
{
    /**
     * Short listing/detail line from Yalago board (cheapest board on listing).
     *
     * @param  array<string, mixed>|null  $board
     */
    public static function yalagoBoardSummary(?array $board): ?string
    {
        if (!$board) {
            return null;
        }

        $charges = $board['CancellationPolicy']['CancellationCharges'] ?? [];
        if (is_array($charges)) {
            foreach ($charges as $policy) {
                if (!is_array($policy)) {
                    continue;
                }
                $amount = (float) ($policy['Charge']['Amount'] ?? 0);
                $expiry = $policy['ExpiryDateUTC'] ?? null;
                if (!$expiry) {
                    continue;
                }
                try {
                    $d = Carbon::parse($expiry)->format('d M Y');
                } catch (\Throwable) {
                    continue;
                }
                if ($amount <= 0) {
                    return "Free cancellation until {$d}";
                }

                return "Cancellation charges may apply from {$d}";
            }
        }

        if (!empty($board['NonRefundable'])) {
            return null;
        }

        return 'Refund rules apply — see room options';
    }

    public static function tboSummary(?bool $isRefundable): ?string
    {
        if ($isRefundable === null) {
            return null;
        }

        return $isRefundable
            ? 'Refundable rate — supplier cancellation rules apply.'
            : 'Non-refundable rate.';
    }
}
