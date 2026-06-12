<?php

namespace App\Support;

final class FlightPassengerFareLinesPresenter
{
    /** @var list<string> */
    private const TYPE_ORDER = ['adult', 'child', 'infant'];

    /**
     * @param  list<array<string, mixed>>  $pricingInfos
     * @param  array<string, mixed>  $searchData
     * @return list<array{type_key: string, type_code: string, label: string, count: int, base_per_pax: float, tax_per_pax: float}>
     */
    public static function fromTravelportPricingInfos(
        array $pricingInfos,
        array $searchData = [],
        ?float $expectedTotal = null,
    ): array {
        unset($expectedTotal);

        return self::sortLines(self::buildTravelportLinesFromPricingInfos($pricingInfos, $searchData));
    }

    /**
     * @param  array<string, mixed>|null  $bookingResponse
     * @param  array<string, mixed>  $searchData
     * @return list<array{type_key: string, type_code: string, label: string, count: int, base_per_pax: float, tax_per_pax: float}>
     */
    public static function fromTravelportBookingResponse(?array $bookingResponse, array $searchData = []): array
    {
        if (! is_array($bookingResponse) || $bookingResponse === []) {
            return [];
        }

        foreach ([
            'Body.AirCreateReservationRsp.UniversalRecord.AirReservation.AirPricingInfo',
            'UniversalRecord.AirReservation.AirPricingInfo',
            'AirReservation.AirPricingInfo',
            'AirPricingInfo',
        ] as $path) {
            $pricingInfos = self::asList(data_get($bookingResponse, $path));
            if ($pricingInfos === []) {
                continue;
            }

            $lines = self::fromTravelportPricingInfos($pricingInfos, $searchData);
            if ($lines !== []) {
                return $lines;
            }
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $pricingInfos
     * @param  array<string, mixed>  $searchData
     * @return list<array{type_key: string, type_code: string, label: string, count: int, base_per_pax: float, tax_per_pax: float}>
     */
    public static function buildTravelportLinesFromPricingInfos(array $pricingInfos, array $searchData): array
    {
        $searchCounts = self::searchPaxCounts($searchData);
        $byTypeKey = [];

        foreach ($pricingInfos as $pricingInfo) {
            if (! is_array($pricingInfo)) {
                continue;
            }

            $code = self::travelportPaxCode($pricingInfo);
            $typeKey = self::typeKeyFromCode($code);
            $baseTotal = self::parseMoneyAmount(self::travelportAttr($pricingInfo, 'BasePrice')) ?? 0.0;
            $taxTotal = self::parseMoneyAmount(self::travelportAttr($pricingInfo, 'Taxes')) ?? 0.0;

            if ($baseTotal <= 0 && $taxTotal <= 0) {
                continue;
            }

            $paxInInfo = self::travelportPassengerTypeCount($pricingInfo);
            $basePerPax = round($baseTotal / $paxInInfo, 2);
            $taxPerPax = round($taxTotal / $paxInInfo, 2);

            if (! isset($byTypeKey[$typeKey])) {
                $byTypeKey[$typeKey] = [
                    'type_key' => $typeKey,
                    'type_code' => $code,
                    'label' => self::paxLabel($code),
                    'pinfo_pax_count' => 0,
                    'base_per_pax' => $basePerPax,
                    'tax_per_pax' => $taxPerPax,
                ];
            }

            $byTypeKey[$typeKey]['pinfo_pax_count'] += $paxInInfo;
            $byTypeKey[$typeKey]['base_per_pax'] = $basePerPax;
            $byTypeKey[$typeKey]['tax_per_pax'] = $taxPerPax;
            $byTypeKey[$typeKey]['type_code'] = $code;
            $byTypeKey[$typeKey]['label'] = self::paxLabel($code);
        }

        $lines = [];

        foreach ($byTypeKey as $typeKey => $row) {
            $count = $searchCounts[$typeKey] ?? 0;
            if ($count <= 0) {
                continue;
            }

            $lines[] = [
                'type_key' => $typeKey,
                'type_code' => (string) ($row['type_code'] ?? self::codeFromTypeKey($typeKey)),
                'label' => (string) ($row['label'] ?? self::paxLabel(self::codeFromTypeKey($typeKey))),
                'count' => $count,
                'base_per_pax' => round((float) ($row['base_per_pax'] ?? 0), 2),
                'tax_per_pax' => round((float) ($row['tax_per_pax'] ?? 0), 2),
                'pinfo_pax_count' => (int) ($row['pinfo_pax_count'] ?? 0),
            ];
        }

        return $lines;
    }

    /**
     * @param  list<array{type_key: string, type_code: string, label: string, count: int, base_per_pax: float, tax_per_pax: float}>  $lines
     */
    public static function passengerFareWarning(array $lines): ?string
    {
        $adultBase = null;
        $childBase = null;

        foreach ($lines as $line) {
            $typeKey = (string) ($line['type_key'] ?? '');
            $base = (float) ($line['base_per_pax'] ?? 0);

            if ($typeKey === 'adult' && $base > 0) {
                $adultBase = $base;
            }

            if ($typeKey === 'child' && $base > 0) {
                $childBase = $base;
            }
        }

        if ($adultBase !== null && $childBase !== null && $childBase >= $adultBase) {
            return 'child_priced_as_adult';
        }

        foreach ($lines as $line) {
            $count = (int) ($line['count'] ?? 0);
            $pinfoCount = (int) ($line['pinfo_pax_count'] ?? 0);
            if ($count > 0 && $pinfoCount > 0 && $pinfoCount < $count) {
                return 'child_fare_incomplete_reprice_needed';
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $pricingBlock
     * @return list<array{type_key: string, type_code: string, label: string, count: int, base_per_pax: float, tax_per_pax: float}>
     */
    public static function fromSabrePricingBlock(array $pricingBlock): array
    {
        $byTypeKey = [];

        foreach (data_get($pricingBlock, 'fare.passengerInfoList', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $paxInfo = $row['passengerInfo'] ?? [];
            if (! is_array($paxInfo)) {
                continue;
            }

            $code = strtoupper(trim((string) ($paxInfo['passengerType'] ?? 'ADT')));
            $typeKey = self::typeKeyFromCode($code);
            $parsed = self::parseSabrePassengerFare($paxInfo['passengerTotalFare'] ?? []);

            if ($parsed === null) {
                continue;
            }

            if (! isset($byTypeKey[$typeKey])) {
                $byTypeKey[$typeKey] = [
                    'type_key' => $typeKey,
                    'type_code' => $code,
                    'label' => self::paxLabel($code),
                    'count' => 0,
                    'base_per_pax' => $parsed['base'],
                    'tax_per_pax' => $parsed['tax'],
                ];
            }

            $byTypeKey[$typeKey]['count']++;
            $byTypeKey[$typeKey]['base_per_pax'] = $parsed['base'];
            $byTypeKey[$typeKey]['tax_per_pax'] = $parsed['tax'];
        }

        return self::sortLines(array_values($byTypeKey));
    }

    /**
     * @param  list<array{type_key: string, type_code: string, label: string, count: int, base_per_pax: float, tax_per_pax: float}>  $lines
     * @return array{base: float, tax: float}
     */
    /**
     * Align itinerary supplier/sell base & tax with stored per-passenger lines.
     *
     * @param  array<string, mixed>  $itinerary
     * @return array<string, mixed>
     */
    public static function syncItineraryFareTotals(array $itinerary): array
    {
        $lines = $itinerary['passenger_fare_lines'] ?? [];
        if (! is_array($lines) || $lines === []) {
            return $itinerary;
        }

        $totals = self::aggregateTotals($lines);

        if (($totals['base'] ?? 0) <= 0) {
            return $itinerary;
        }

        $oldSupplierBase = (float) ($itinerary['supplierBasePrice'] ?? $itinerary['basePrice'] ?? 0);
        $sellBase = (float) ($itinerary['basePrice'] ?? 0);
        $vendorAdjustmentRatio = ($oldSupplierBase > 0.001 && $sellBase > 0.001 && abs($sellBase - $oldSupplierBase) > 0.01)
            ? ($sellBase / $oldSupplierBase)
            : 1.0;

        $apiBase = (float) ($itinerary['supplierBasePrice'] ?? $itinerary['basePrice'] ?? 0);
        $apiTax = (float) ($itinerary['supplierTaxes'] ?? $itinerary['taxes'] ?? 0);
        $lineBase = (float) ($totals['base'] ?? 0);
        $lineTax = (float) ($totals['tax'] ?? 0);
        $lineSum = round($lineBase + $lineTax, 2);
        $apiSum = round($apiBase + $apiTax, 2);

        if ($apiSum > 0 && $lineSum > 0 && abs($lineSum - $apiSum) > 0.05) {
            return $itinerary;
        }

        $itinerary['supplierBasePrice'] = $totals['base'];
        $itinerary['supplierTaxes'] = $totals['tax'];
        $itinerary['basePrice'] = round($totals['base'] * $vendorAdjustmentRatio, 2);
        $itinerary['taxes'] = $totals['tax'];

        return $itinerary;
    }

    public static function aggregateTotals(array $lines): array
    {
        $base = 0.0;
        $tax = 0.0;

        foreach ($lines as $line) {
            $count = max(0, (int) ($line['count'] ?? 0));
            $base += round((float) ($line['base_per_pax'] ?? 0) * $count, 2);
            $tax += round((float) ($line['tax_per_pax'] ?? 0) * $count, 2);
        }

        return [
            'base' => round($base, 2),
            'tax' => round($tax, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $itinerary
     * @return array{
     *     lines: list<array{type_key: string, type_code: string, label: string, count: int, base_per_pax: float, tax_per_pax: float}>,
     *     base_lines: list<array{label: string, count: int, unit: float, total: float}>,
     *     tax_lines: list<array{label: string, count: int, unit: float, total: float}>,
     *     has_pax_lines: bool
     * }
     */
    public static function forBreakdown(
        array $itinerary,
        int $adults,
        int $children,
        int $infants,
        float $displayBase,
        float $displayTax,
        float $supplierBase,
    ): array {
        $stored = $itinerary['passenger_fare_lines'] ?? null;
        $hasStoredLines = is_array($stored) && $stored !== [];
        $lines = $hasStoredLines
            ? self::normalizeStoredLines($stored, $adults, $children, $infants)
            : self::buildFallbackLines($adults, $children, $infants, $displayBase, $displayTax, $supplierBase);

        $supplierFromLines = self::aggregateTotals($lines);
        $supplierBaseForRatio = $hasStoredLines && ($supplierFromLines['base'] ?? 0) > 0
            ? (float) $supplierFromLines['base']
            : $supplierBase;
        $ratio = $supplierBaseForRatio > 0.001 ? ($displayBase / $supplierBaseForRatio) : 1.0;
        $baseLines = [];
        $taxLines = [];

        foreach ($lines as $line) {
            $count = max(0, (int) ($line['count'] ?? 0));
            if ($count <= 0) {
                continue;
            }

            $unitBase = round((float) ($line['base_per_pax'] ?? 0) * $ratio, 2);
            $unitTax = round((float) ($line['tax_per_pax'] ?? 0), 2);

            $baseLines[] = [
                'label' => (string) ($line['label'] ?? 'Passenger'),
                'count' => $count,
                'unit' => $unitBase,
                'total' => round($unitBase * $count, 2),
            ];

            $taxLines[] = [
                'label' => (string) ($line['label'] ?? 'Passenger'),
                'count' => $count,
                'unit' => $unitTax,
                'total' => round($unitTax * $count, 2),
            ];
        }

        $baseFromLines = round(array_sum(array_column($baseLines, 'total')), 2);
        $taxFromLines = round(array_sum(array_column($taxLines, 'total')), 2);

        return [
            'lines' => $lines,
            'base_lines' => $baseLines,
            'tax_lines' => $taxLines,
            'has_pax_lines' => $baseLines !== [],
            'has_stored_lines' => $hasStoredLines,
            'base_from_lines' => $baseFromLines,
            'tax_from_lines' => $taxFromLines,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $stored
     * @return list<array{type_key: string, type_code: string, label: string, count: int, base_per_pax: float, tax_per_pax: float}>
     */
    private static function normalizeStoredLines(array $stored, int $adults, int $children, int $infants): array
    {
        $searchCounts = [
            'adult' => max(0, $adults),
            'child' => max(0, $children),
            'infant' => max(0, $infants),
        ];

        $normalized = [];

        foreach ($stored as $row) {
            if (! is_array($row)) {
                continue;
            }

            $typeKey = (string) ($row['type_key'] ?? self::typeKeyFromCode((string) ($row['type_code'] ?? 'ADT')));
            $count = (int) ($row['count'] ?? ($searchCounts[$typeKey] ?? 0));
            if ($count <= 0) {
                $count = $searchCounts[$typeKey] ?? 0;
            }
            if ($count <= 0) {
                continue;
            }

            $normalized[] = [
                'type_key' => $typeKey,
                'type_code' => (string) ($row['type_code'] ?? self::codeFromTypeKey($typeKey)),
                'label' => (string) ($row['label'] ?? self::paxLabel(self::codeFromTypeKey($typeKey))),
                'count' => $count,
                'base_per_pax' => round((float) ($row['base_per_pax'] ?? 0), 2),
                'tax_per_pax' => round((float) ($row['tax_per_pax'] ?? 0), 2),
            ];
        }

        return self::sortLines($normalized);
    }

    /**
     * @return list<array{type_key: string, type_code: string, label: string, count: int, base_per_pax: float, tax_per_pax: float}>
     */
    private static function buildFallbackLines(
        int $adults,
        int $children,
        int $infants,
        float $displayBase,
        float $displayTax,
        float $supplierBase,
    ): array {
        $lines = [];
        $allPax = max(1, $adults + $children + $infants);

        foreach ([
            ['adult', 'ADT', 'Adult', $adults, 1.0],
            ['child', 'CNN', 'Child', $children, 0.75],
            ['infant', 'INF', 'Infant', $infants, 0.10],
        ] as [$typeKey, $code, $label, $count, $weight]) {
            if ($count <= 0) {
                continue;
            }

            $weightedTotal = max(0.001, ($adults * 1.0) + ($children * 0.75) + ($infants * 0.10));
            $basePerPax = round(($displayBase / $weightedTotal) * $weight, 2);
            $taxPerPax = round($displayTax / $allPax, 2);

            $lines[] = [
                'type_key' => $typeKey,
                'type_code' => $code,
                'label' => $label,
                'count' => $count,
                'base_per_pax' => $basePerPax,
                'tax_per_pax' => $taxPerPax,
            ];
        }

        return self::sortLines($lines);
    }

    /**
     * @param  array<string, mixed>  $searchData
     * @return array{adult: int, child: int, infant: int}
     */
    private static function searchPaxCounts(array $searchData): array
    {
        return [
            'adult' => max(0, (int) ($searchData['adults'] ?? 1)),
            'child' => max(0, (int) ($searchData['children'] ?? 0)),
            'infant' => max(0, (int) ($searchData['infants'] ?? 0)),
        ];
    }

    /**
     * @param  array<string, mixed>  $pricingInfo
     */
    private static function travelportPaxCode(array $pricingInfo): string
    {
        foreach (self::asList($pricingInfo['PassengerType'] ?? null) as $passengerType) {
            if (! is_array($passengerType)) {
                continue;
            }

            $code = strtoupper(trim((string) self::travelportAttr($passengerType, 'Code', '')));
            if ($code !== '') {
                return $code;
            }
        }

        foreach (self::asList($pricingInfo['FareInfo'] ?? null) as $fareInfo) {
            if (! is_array($fareInfo)) {
                continue;
            }

            $code = strtoupper(trim((string) self::travelportAttr($fareInfo, 'PassengerTypeCode', '')));
            if ($code !== '') {
                return $code;
            }
        }

        return 'ADT';
    }

    /**
     * @param  array<string, mixed>|null  $node
     */
    private static function travelportAttr(?array $node, string $key, ?string $default = null): ?string
    {
        if ($node === null) {
            return $default;
        }

        if (array_key_exists($key, $node) && $node[$key] !== null && $node[$key] !== '') {
            return is_scalar($node[$key]) ? (string) $node[$key] : $default;
        }

        $attrs = $node['@attributes'] ?? null;
        if (is_array($attrs) && array_key_exists($key, $attrs) && $attrs[$key] !== null && $attrs[$key] !== '') {
            return is_scalar($attrs[$key]) ? (string) $attrs[$key] : $default;
        }

        return $default;
    }

    private static function parseMoneyAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $amount = round((float) $value, 2);

            return $amount > 0 ? $amount : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $text = trim($value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/[\d,.]+/', $text, $matches) !== 1) {
            return null;
        }

        $amount = round((float) str_replace(',', '', $matches[0]), 2);

        return $amount > 0 ? $amount : null;
    }

    /**
     * @param  array<string, mixed>  $fareRow
     * @return array{base: float, tax: float}|null
     */
    private static function parseSabrePassengerFare(array $fareRow): ?array
    {
        $base = self::normalizeAmount(data_get($fareRow, 'baseFareAmount'))
            ?? self::normalizeAmount(data_get($fareRow, 'baseFare'))
            ?? self::normalizeAmount(data_get($fareRow, 'equivalentBaseFareAmount'));

        $tax = self::normalizeAmount(data_get($fareRow, 'totalTaxAmount'))
            ?? self::normalizeAmount(data_get($fareRow, 'taxAmount'))
            ?? self::normalizeAmount(data_get($fareRow, 'totalTaxes'));

        if ($base === null && $tax === null) {
            return null;
        }

        return [
            'base' => $base ?? 0.0,
            'tax' => $tax ?? 0.0,
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

    private static function typeKeyFromCode(string $code): string
    {
        $normalized = strtoupper(trim($code));

        if (preg_match('/^CNN\d{2}$/', $normalized) === 1) {
            return 'child';
        }

        return match ($normalized) {
            'CNN', 'C06', 'CHD' => 'child',
            'INF' => 'infant',
            default => 'adult',
        };
    }

    private static function codeFromTypeKey(string $typeKey): string
    {
        return match ($typeKey) {
            'child' => 'CNN',
            'infant' => 'INF',
            default => 'ADT',
        };
    }

    private static function paxLabel(string $code): string
    {
        $normalized = strtoupper(trim($code));

        if (preg_match('/^CNN\d{2}$/', $normalized) === 1) {
            return 'Child';
        }

        return match ($normalized) {
            'ADT' => 'Adult',
            'CNN', 'C06', 'CHD' => 'Child',
            'INF' => 'Infant',
            default => $normalized !== '' ? $normalized : 'Passenger',
        };
    }

    /**
     * @param  array<string, mixed>  $pricingInfo
     */
    private static function travelportPassengerTypeCount(array $pricingInfo): int
    {
        $passengerTypes = self::asList($pricingInfo['PassengerType'] ?? null);
        $count = 0;

        foreach ($passengerTypes as $passengerType) {
            if (is_array($passengerType)) {
                $count++;
            }
        }

        return max(1, $count);
    }

    /**
     * @param  list<array{type_key: string, type_code: string, label: string, count: int, base_per_pax: float, tax_per_pax: float}>  $lines
     * @return list<array{type_key: string, type_code: string, label: string, count: int, base_per_pax: float, tax_per_pax: float}>
     */
    private static function sortLines(array $lines): array
    {
        usort($lines, static function (array $a, array $b): int {
            $order = array_flip(self::TYPE_ORDER);
            $aIndex = $order[$a['type_key'] ?? ''] ?? 99;
            $bIndex = $order[$b['type_key'] ?? ''] ?? 99;

            return $aIndex <=> $bIndex;
        });

        return array_values($lines);
    }

    /**
     * @return list<mixed>
     */
    private static function asList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            return [];
        }

        if ($value === [] || array_is_list($value)) {
            return $value;
        }

        return [$value];
    }
}
