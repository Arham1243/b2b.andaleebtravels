<?php

namespace App\Support;

final class SabreFareAmountPresenter
{
    /**
     * @param  array<string, mixed>  $pricingBlock
     * @return array{base: float, tax: float, total: float}|null
     */
    public static function fromPricingBlock(array $pricingBlock): ?array
    {
        $totalFare = data_get($pricingBlock, 'fare.totalFare');
        if (is_array($totalFare)) {
            $parsed = self::parseFareAmountRow($totalFare);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        $passengerRows = data_get($pricingBlock, 'fare.passengerInfoList', []);
        if (! is_array($passengerRows) || $passengerRows === []) {
            return null;
        }

        $baseSum = 0.0;
        $taxSum = 0.0;
        $totalSum = 0.0;
        $hasBase = false;
        $hasTax = false;
        $hasTotal = false;

        foreach ($passengerRows as $row) {
            $passengerTotalFare = data_get($row, 'passengerInfo.passengerTotalFare', []);
            if (! is_array($passengerTotalFare)) {
                continue;
            }

            $parsed = self::parseFareAmountRow($passengerTotalFare);
            if ($parsed === null) {
                continue;
            }

            if ($parsed['base'] > 0) {
                $baseSum += $parsed['base'];
                $hasBase = true;
            }

            if ($parsed['tax'] > 0) {
                $taxSum += $parsed['tax'];
                $hasTax = true;
            }

            if ($parsed['total'] > 0) {
                $totalSum += $parsed['total'];
                $hasTotal = true;
            }
        }

        if (! $hasTotal && ! $hasBase) {
            return null;
        }

        $base = $hasBase ? round($baseSum, 2) : 0.0;
        $tax = $hasTax ? round($taxSum, 2) : 0.0;
        $total = $hasTotal ? round($totalSum, 2) : round($base + $tax, 2);

        if ($total <= 0) {
            return null;
        }

        if ($base <= 0 && $tax > 0) {
            $base = round(max(0, $total - $tax), 2);
        }

        if ($tax <= 0 && $base > 0 && $total > $base) {
            $tax = round($total - $base, 2);
        }

        if ($base <= 0 || $tax <= 0) {
            return null;
        }

        return [
            'base' => $base,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    /**
     * @param  array<string, mixed>  $fareRow
     * @return array{base: float, tax: float, total: float}|null
     */
    private static function parseFareAmountRow(array $fareRow): ?array
    {
        $base = self::normalizeAmount(data_get($fareRow, 'baseFareAmount'))
            ?? self::normalizeAmount(data_get($fareRow, 'baseFare'))
            ?? self::normalizeAmount(data_get($fareRow, 'equivalentBaseFareAmount'));

        $tax = self::normalizeAmount(data_get($fareRow, 'totalTaxAmount'))
            ?? self::normalizeAmount(data_get($fareRow, 'taxAmount'))
            ?? self::normalizeAmount(data_get($fareRow, 'totalTaxes'));

        $total = self::normalizeAmount(data_get($fareRow, 'totalPrice'))
            ?? self::normalizeAmount(data_get($fareRow, 'equivalentAmount'))
            ?? self::normalizeAmount(data_get($fareRow, 'constructAmount'))
            ?? self::normalizeAmount(data_get($fareRow, 'constructionAmount'));

        if ($total === null && $base !== null && $tax !== null) {
            $total = round($base + $tax, 2);
        }

        if ($total === null || $total <= 0) {
            return null;
        }

        if ($base === null && $tax !== null) {
            $base = round(max(0, $total - $tax), 2);
        }

        if ($tax === null && $base !== null) {
            $tax = round(max(0, $total - $base), 2);
        }

        if ($base === null || $tax === null || $base <= 0 || $tax <= 0) {
            return null;
        }

        if (abs(($base + $tax) - $total) > 0.06) {
            return null;
        }

        return [
            'base' => $base,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    private static function normalizeAmount(mixed $value): ?float
    {
        if (is_array($value)) {
            foreach (['amount', 'Amount', 'value', 'Value'] as $key) {
                if (array_key_exists($key, $value)) {
                    return self::normalizeAmount($value[$key]);
                }
            }

            return null;
        }

        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $amount = round((float) $value, 2);

        return $amount > 0 ? $amount : null;
    }
}
