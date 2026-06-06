<?php

namespace App\Support;

final class SabreBaggagePresenter
{
    /**
     * @param array<string, mixed> $pricingBlock
     * @param array<string, mixed> $grouped
     *
     * @return array{
     *     summary: ?string,
     *     checked: list<array{route: string, airline: string, allowance: string, provision_type: string}>,
     *     cabin: list<array{route: string, airline: string, allowance: string, provision_type: string}>,
     *     pax_table: list<array{pax_type: string, checked: string, cabin: string}>
     * }
     */
    public static function fromPricingBlock(array $pricingBlock, array $grouped = []): array
    {
        $allowanceById = collect($grouped['baggageAllowanceDescs'] ?? [])->keyBy('id');
        $baggageInformation = data_get($pricingBlock, 'fare.passengerInfoList.0.passengerInfo.baggageInformation', []);
        $fareComponents = data_get($pricingBlock, 'fare.passengerInfoList.0.passengerInfo.fareComponents', []);
        $segmentRoutes = self::segmentRoutes($fareComponents);

        $checked = [];
        $cabin = [];

        if (!is_array($baggageInformation)) {
            return self::emptyResult();
        }

        foreach ($baggageInformation as $row) {
            if (!is_array($row)) {
                continue;
            }

            $provisionType = strtoupper(trim((string) ($row['provisionType'] ?? 'A')));
            $segmentId = (int) data_get($row, 'segments.0.id', -1);
            $route = $segmentRoutes[$segmentId] ?? 'All segments';
            $airline = strtoupper(trim((string) ($row['airlineCode'] ?? '')));
            $allowanceText = self::formatAllowance(self::resolveAllowanceDesc($row, $allowanceById));

            $entry = [
                'route' => $route,
                'airline' => $airline,
                'allowance' => $allowanceText,
                'provision_type' => $provisionType,
            ];

            if (self::isCabinProvision($provisionType)) {
                $cabin[] = $entry;
            } else {
                $checked[] = $entry;
            }
        }

        $checked = self::uniqueRows($checked);
        $cabin = self::uniqueRows($cabin);

        return [
            'summary' => self::buildSummary($checked, $cabin),
            'checked' => $checked,
            'cabin' => $cabin,
            'pax_table' => self::paxAllowanceTable($pricingBlock, $grouped),
        ];
    }

    /**
     * @param array<string, mixed> $pricingBlock
     * @param array<string, mixed> $grouped
     *
     * @return list<array{pax_type: string, checked: string, cabin: string}>
     */
    public static function paxAllowanceTable(array $pricingBlock, array $grouped = []): array
    {
        $allowanceById = collect($grouped['baggageAllowanceDescs'] ?? [])->keyBy('id');
        $rows = [];

        foreach (data_get($pricingBlock, 'fare.passengerInfoList', []) as $paxRow) {
            if (!is_array($paxRow)) {
                continue;
            }

            $paxInfo = $paxRow['passengerInfo'] ?? [];
            if (!is_array($paxInfo)) {
                continue;
            }

            $checkedAllowances = [];
            $cabinAllowances = [];

            foreach ($paxInfo['baggageInformation'] ?? [] as $bagRow) {
                if (!is_array($bagRow)) {
                    continue;
                }

                $provisionType = strtoupper(trim((string) ($bagRow['provisionType'] ?? 'A')));
                $text = self::formatAllowance(self::resolveAllowanceDesc($bagRow, $allowanceById));

                if (self::isCabinProvision($provisionType)) {
                    $cabinAllowances[] = $text;
                } else {
                    $checkedAllowances[] = $text;
                }
            }

            $rows[] = [
                'pax_type' => self::paxLabel((string) ($paxInfo['passengerType'] ?? 'ADT')),
                'checked' => self::summarizeAllowanceList($checkedAllowances),
                'cabin' => self::summarizeAllowanceList($cabinAllowances),
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $fareComponents
     *
     * @return array<int, string>
     */
    private static function segmentRoutes(array $fareComponents): array
    {
        $routes = [];

        foreach ($fareComponents as $index => $component) {
            if (!is_array($component)) {
                continue;
            }

            $from = strtoupper(trim((string) ($component['beginAirport'] ?? '')));
            $to = strtoupper(trim((string) ($component['endAirport'] ?? '')));

            if ($from !== '' && $to !== '') {
                $routes[(int) $index] = $from . ' → ' . $to;
            }
        }

        return $routes;
    }

    /**
     * @param  array<string, mixed>  $bagRow
     * @param  \Illuminate\Support\Collection<int|string, mixed>  $allowanceById
     * @return array<string, mixed>|null
     */
    private static function resolveAllowanceDesc(array $bagRow, $allowanceById): ?array
    {
        $allowanceRef = data_get($bagRow, 'allowance.ref');

        if ($allowanceRef !== null) {
            $desc = $allowanceById->get($allowanceRef);

            if (is_array($desc)) {
                return $desc;
            }
        }

        $inline = $bagRow['allowance'] ?? null;

        if (is_array($inline) && (array_key_exists('pieceCount', $inline)
            || array_key_exists('weight', $inline)
            || array_key_exists('description1', $inline)
            || array_key_exists('description2', $inline))) {
            return $inline;
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $desc
     */
    private static function formatAllowance(?array $desc): string
    {
        if ($desc === null) {
            return 'Not included';
        }

        $pieces = (int) ($desc['pieceCount'] ?? 0);
        $weight = (int) ($desc['weight'] ?? 0);
        $unit = self::normalizeUnit((string) ($desc['unit'] ?? ''));

        $structured = self::formatStructuredAllowance($pieces, $weight, $unit);
        if ($structured !== null) {
            return $structured;
        }

        foreach (['description', 'Description'] as $key) {
            $description = trim((string) ($desc[$key] ?? ''));
            if ($description === '') {
                continue;
            }

            $parsed = self::parseDescriptionAllowance($description);
            if ($parsed !== null) {
                return $parsed;
            }

            return self::compactDescription($description);
        }

        foreach (['description1', 'description2'] as $key) {
            $description = trim((string) ($desc[$key] ?? ''));
            if ($description === '') {
                continue;
            }

            $parsed = self::parseDescriptionAllowance($description);
            if ($parsed !== null) {
                return $parsed;
            }

            return self::compactDescription($description);
        }

        return '0 kg';
    }

    private static function formatStructuredAllowance(int $pieces, int $weight, string $unit): ?string
    {
        if ($pieces > 0 && $weight > 0) {
            return $pieces . ' pc · ' . $weight . ' ' . $unit;
        }

        if ($weight > 0) {
            return $weight . ' ' . $unit;
        }

        if ($pieces > 0) {
            return $pieces . ' pc';
        }

        return null;
    }

    private static function parseDescriptionAllowance(string $description): ?string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $description) ?? '');

        if (preg_match('/up\s*to\s*\d+\s*(?:pounds?|lbs?)\s*\/\s*(\d+)\s*(?:kilograms?|kgs?)/i', $normalized, $matches)) {
            return $matches[1] . ' kg';
        }

        if (preg_match('/up\s*to\s*(\d+)\s*(?:kilograms?|kgs?)/i', $normalized, $matches)) {
            return $matches[1] . ' kg';
        }

        if (preg_match('/(\d+)\s*(?:kilograms?|kgs?)/i', $normalized, $matches)) {
            return $matches[1] . ' kg';
        }

        if (preg_match('/up\s*to\s*(\d+)\s*(?:pounds?|lbs?)/i', $normalized, $matches)) {
            return $matches[1] . ' lb';
        }

        if (preg_match('/(\d+)\s*(?:pounds?|lbs?)/i', $normalized, $matches)) {
            return $matches[1] . ' lb';
        }

        if (preg_match('/(\d+)\s*(?:pieces?|pcs?)/i', $normalized, $matches)) {
            return $matches[1] . ' pc';
        }

        return null;
    }

    private static function compactDescription(string $description): string
    {
        $description = trim(preg_replace('/\s+/', ' ', $description) ?? '');

        if ($description === '') {
            return 'Not included';
        }

        $parsed = self::parseDescriptionAllowance($description);
        if ($parsed !== null) {
            return $parsed;
        }

        $description = preg_replace('/\bkilograms?\b/i', 'kg', $description) ?? $description;
        $description = preg_replace('/\bpounds?\b/i', 'lb', $description) ?? $description;
        $description = preg_replace('/\bup to\b/i', 'Up to', $description) ?? $description;

        if (strtoupper($description) === $description) {
            return ucwords(strtolower($description));
        }

        return $description;
    }

    private static function normalizeUnit(string $unit): string
    {
        $unit = strtolower(trim($unit));

        return match ($unit) {
            'kg', 'kgs', 'kilogram', 'kilograms' => 'kg',
            'lb', 'lbs', 'pound', 'pounds' => 'lb',
            default => $unit !== '' ? $unit : 'kg',
        };
    }

    private static function normalizeDescription(string $description): string
    {
        return self::compactDescription($description);
    }

    /**
     * @param list<string> $allowances
     */
    private static function summarizeAllowanceList(array $allowances): string
    {
        $allowances = array_values(array_unique(array_filter(array_map(
            static fn (string $value) => trim($value),
            $allowances,
        ))));

        if ($allowances === []) {
            return 'Not included';
        }

        return count($allowances) === 1 ? $allowances[0] : implode(' / ', $allowances);
    }

    private static function paxLabel(string $code): string
    {
        return match (strtoupper(trim($code))) {
            'ADT' => 'Adult',
            'CNN', 'C06', 'CHD' => 'Child',
            'INF' => 'Infant',
            default => strtoupper(trim($code)) !== '' ? strtoupper(trim($code)) : 'Passenger',
        };
    }

    private static function isCabinProvision(string $provisionType): bool
    {
        return in_array($provisionType, ['B', 'CARRYON', 'CARRY-ON', 'C'], true);
    }

    /**
     * @param list<array{route: string, airline: string, allowance: string, provision_type: string}> $rows
     *
     * @return list<array{route: string, airline: string, allowance: string, provision_type: string}>
     */
    private static function uniqueRows(array $rows): array
    {
        $unique = [];

        foreach ($rows as $row) {
            $key = strtolower($row['route'] . '|' . $row['allowance'] . '|' . $row['provision_type']);

            if (!isset($unique[$key])) {
                $unique[$key] = $row;
            }
        }

        return array_values($unique);
    }

    /**
     * @param list<array{route: string, airline: string, allowance: string, provision_type: string}> $checked
     * @param list<array{route: string, airline: string, allowance: string, provision_type: string}> $cabin
     */
    private static function buildSummary(array $checked, array $cabin): ?string
    {
        $allowances = array_values(array_unique(array_filter(array_map(
            fn (array $row) => trim($row['allowance']),
            $checked,
        ))));

        if ($allowances !== []) {
            return count($allowances) === 1 ? $allowances[0] . ' checked' : $allowances[0] . ' checked';
        }

        $cabinAllowances = array_values(array_unique(array_filter(array_map(
            fn (array $row) => trim($row['allowance']),
            $cabin,
        ))));

        if ($cabinAllowances !== []) {
            return $cabinAllowances[0] . ' cabin';
        }

        return null;
    }

    /**
     * @return array{summary: null, checked: list<empty>, cabin: list<empty>, pax_table: list<empty>}
     */
    private static function emptyResult(): array
    {
        return [
            'summary' => null,
            'checked' => [],
            'cabin' => [],
            'pax_table' => [],
        ];
    }
}
