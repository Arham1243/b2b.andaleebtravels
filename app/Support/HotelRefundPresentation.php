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

        return 'Refund rules apply - see room options';
    }

    public static function tboSummary(?bool $isRefundable): ?string
    {
        if ($isRefundable === null) {
            return null;
        }

        return $isRefundable
            ? 'Refundable rate - supplier cancellation rules apply.'
            : 'Non-refundable rate.';
    }

    /**
     * @param  array<string, mixed>|null  $response
     * @return array{is_refundable: bool|null, summary: string|null}
     */
    public static function tboRefundMetaFromBookingResponse(?array $response): array
    {
        if (!$response) {
            return ['is_refundable' => null, 'summary' => null];
        }

        $isRefundable = self::normalizeTboRefundable(self::tboExtractRefundableFlag($response));

        return [
            'is_refundable' => $isRefundable,
            'summary' => self::tboSummary($isRefundable),
        ];
    }

    /**
     * Collect refund-related keys from a TBO payload for logging/audit.
     *
     * @param  array<string, mixed>|null  $response
     * @return list<array{path: string, value: mixed}>
     */
    public static function tboRefundFlagAudit(?array $response): array
    {
        if (!$response) {
            return [];
        }

        $hits = [];
        self::tboCollectRefundableHits($response, '', $hits, 0);

        return $hits;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function tboExtractRefundableFlag(array $data): mixed
    {
        foreach (['IsRefundable', 'is_refundable', 'Refundable'] as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        foreach (['Rooms', 'RoomDetails', 'HotelRooms', 'HotelResult', 'BookResult'] as $listKey) {
            if (empty($data[$listKey]) || !is_array($data[$listKey])) {
                continue;
            }

            $items = array_is_list($data[$listKey]) ? $data[$listKey] : [$data[$listKey]];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                foreach (['IsRefundable', 'is_refundable', 'Refundable'] as $key) {
                    if (array_key_exists($key, $item)) {
                        return $item[$key];
                    }
                }
            }
        }

        return self::tboDeepFindRefundable($data, 0);
    }

    private static function normalizeTboRefundable(mixed $raw): ?bool
    {
        if ($raw === null) {
            return null;
        }

        if (is_bool($raw)) {
            return $raw;
        }

        if (is_numeric($raw)) {
            return (bool) $raw;
        }

        if (is_string($raw)) {
            $value = strtolower(trim($raw));

            if (in_array($value, ['true', '1', 'yes', 'refundable'], true)) {
                return true;
            }

            if (in_array($value, ['false', '0', 'no', 'non-refundable', 'nonrefundable'], true)) {
                return false;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function tboDeepFindRefundable(array $data, int $depth): mixed
    {
        if ($depth > 8) {
            return null;
        }

        foreach (['IsRefundable', 'is_refundable', 'Refundable'] as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }

            $found = self::tboDeepFindRefundable($value, $depth + 1);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{path: string, value: mixed}>  $hits
     */
    private static function tboCollectRefundableHits(array $data, string $prefix, array &$hits, int $depth): void
    {
        if ($depth > 8) {
            return;
        }

        foreach (['IsRefundable', 'is_refundable', 'Refundable', 'NonRefundable'] as $key) {
            if (array_key_exists($key, $data)) {
                $hits[] = [
                    'path' => $prefix === '' ? $key : "{$prefix}.{$key}",
                    'value' => $data[$key],
                ];
            }
        }

        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            self::tboCollectRefundableHits($value, $path, $hits, $depth + 1);
        }
    }
}
