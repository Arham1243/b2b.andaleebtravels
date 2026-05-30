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
            $allowanceRef = data_get($row, 'allowance.ref');
            $allowanceDesc = $allowanceRef !== null ? $allowanceById->get($allowanceRef) : null;
            $allowanceText = self::formatAllowance(is_array($allowanceDesc) ? $allowanceDesc : null);

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
                $allowanceRef = data_get($bagRow, 'allowance.ref');
                $allowanceDesc = $allowanceRef !== null ? $allowanceById->get($allowanceRef) : null;
                $text = self::formatAllowance(is_array($allowanceDesc) ? $allowanceDesc : null);

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
     * @param array<string, mixed>|null $desc
     */
    private static function formatAllowance(?array $desc): string
    {
        if ($desc === null) {
            return 'Not included';
        }

        foreach (['description1', 'description2'] as $key) {
            $description = trim((string) ($desc[$key] ?? ''));
            if ($description !== '') {
                return self::normalizeDescription($description);
            }
        }

        $pieces = (int) ($desc['pieceCount'] ?? 0);
        $weight = (int) ($desc['weight'] ?? 0);
        $unit = strtolower(trim((string) ($desc['unit'] ?? 'kg')));

        if ($pieces > 0 && $weight > 0) {
            return $pieces . ' pc up to ' . $weight . ' ' . $unit;
        }

        if ($pieces > 0) {
            return $pieces . ' pc';
        }

        if ($weight > 0) {
            return $weight . ' ' . $unit;
        }

        return '0 kg';
    }

    private static function normalizeDescription(string $description): string
    {
        $description = trim(preg_replace('/\s+/', ' ', $description) ?? '');

        if ($description === '') {
            return 'Not included';
        }

        if (strtoupper($description) === $description) {
            return ucwords(strtolower($description));
        }

        return $description;
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
