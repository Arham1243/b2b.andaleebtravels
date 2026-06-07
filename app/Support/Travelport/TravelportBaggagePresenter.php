<?php

namespace App\Support\Travelport;

use App\Support\SabreBaggagePresenter;

final class TravelportBaggagePresenter
{
    /**
     * Build Sabre-compatible baggage_details from Travelport LowFareSearch payload.
     *
     * @param  array<string, mixed>|null  $fareInfo
     * @param  array<string, mixed>|null  $pricingInfo
     * @param  list<array<string, mixed>>  $legs
     * @return array<string, mixed>
     */
    public static function fromFareInfo(?array $fareInfo, ?array $pricingInfo, array $legs, ?string $validatingCarrier): array
    {
        if ($fareInfo === null) {
            return self::emptyResult();
        }

        $allowances = self::extractAllowances($fareInfo);
        $checkedFriendly = $allowances['checked'] ?? self::notIncludedFriendly(false);
        $cabinFriendly = $allowances['cabin'] ?? self::notIncludedFriendly(true);

        $segmentRoutes = self::segmentRoutesFromLegs($legs);
        $airline = strtoupper(trim((string) ($validatingCarrier ?? '')));

        $checked = self::rowsForAllowance($segmentRoutes, $airline, $checkedFriendly, false);
        $cabin = self::rowsForAllowance($segmentRoutes, $airline, $cabinFriendly, true);

        if (strcasecmp((string) ($cabinFriendly['amount'] ?? ''), 'Not included') === 0) {
            $cabin = [];
        }

        $checked = SabreBaggagePresenter::alignWithFlightLegs(
            ['checked' => $checked, 'cabin' => [], 'summary_items' => []],
            $legs,
        )['checked'] ?? $checked;

        if ($cabin !== []) {
            $cabin = SabreBaggagePresenter::alignWithFlightLegs(
                ['checked' => [], 'cabin' => $cabin, 'summary_items' => []],
                $legs,
            )['cabin'] ?? $cabin;
        }

        $paxTable = [self::buildPaxRow($pricingInfo, $checkedFriendly, $cabinFriendly)];
        $summaryItems = self::buildSummaryItems($checked, $cabin, $checkedFriendly, $cabinFriendly);

        return [
            'summary' => $summaryItems !== [] ? implode(' · ', $summaryItems) : null,
            'summary_items' => $summaryItems,
            'checked' => $checked,
            'cabin' => $cabin,
            'pax_table' => $paxTable,
            'cabin_notes' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $fareInfo
     * @return array{checked: ?array{display: string, amount: string, label: string, note: ?string}, cabin: ?array{display: string, amount: string, label: string, note: ?string}}
     */
    private static function extractAllowances(array $fareInfo): array
    {
        $checked = null;
        $cabin = null;

        foreach (self::asList(data_get($fareInfo, 'BaggageAllowance')) as $node) {
            if (! is_array($node)) {
                continue;
            }

            $type = strtolower(trim((string) self::attr($node, 'Type', '')));
            $isCabin = in_array($type, ['carryon', 'carry-on', 'cabin', 'hand', 'handbaggage'], true);
            $parsed = self::parseAllowanceNode($node, $isCabin);
            if ($parsed === null) {
                continue;
            }

            if ($isCabin) {
                $cabin = $parsed;
            } elseif ($checked === null) {
                $checked = $parsed;
            }
        }

        return [
            'checked' => $checked,
            'cabin' => $cabin,
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array{display: string, amount: string, label: string, note: ?string}|null
     */
    private static function parseAllowanceNode(array $node, bool $isCabin = false): ?array
    {
        $typeLabel = $isCabin ? 'Cabin Baggage' : 'Check-in Baggage';
        $maxWeight = data_get($node, 'MaxWeight');
        if (is_array($maxWeight)) {
            $value = trim((string) self::attr($maxWeight, 'Value', ''));
            $unit = strtolower(trim((string) self::attr($maxWeight, 'Unit', 'Kilograms')));

            if ($value !== '' && is_numeric($value)) {
                $amount = ($unit === 'kilograms' || $unit === 'kg')
                    ? $value . ' kg'
                    : trim($value . ' ' . $unit);

                return self::friendlyResult($amount, $typeLabel, null);
            }
        }

        $pieces = self::attr($node, 'NumberOfPieces');
        if ($pieces !== null && $pieces !== '' && is_numeric($pieces)) {
            $count = (int) $pieces;
            $amount = $count === 1 ? '1 PC' : $count . ' PC';

            return self::friendlyResult($amount, $typeLabel, null);
        }

        return null;
    }

    /**
     * @param  list<string>  $segmentRoutes
     * @param  array{display: string, amount: string, label: string, note: ?string}  $friendly
     * @return list<array<string, mixed>>
     */
    private static function rowsForAllowance(array $segmentRoutes, string $airline, array $friendly, bool $isCabin): array
    {
        if (strcasecmp((string) ($friendly['amount'] ?? ''), 'Not included') === 0) {
            return [];
        }

        $rows = [];
        foreach ($segmentRoutes as $route) {
            $rows[] = [
                'route' => $route,
                'airline' => $airline,
                'allowance' => $friendly['display'],
                'friendly' => $friendly,
                'provision_type' => $isCabin ? 'B' : 'A',
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>|null  $pricingInfo
     * @param  array{display: string, amount: string, label: string, note: ?string}  $checkedFriendly
     * @param  array{display: string, amount: string, label: string, note: ?string}  $cabinFriendly
     * @return array<string, mixed>
     */
    private static function buildPaxRow(?array $pricingInfo, array $checkedFriendly, array $cabinFriendly): array
    {
        $paxCode = 'ADT';
        $passengerType = data_get($pricingInfo, 'PassengerType');
        if (is_array($passengerType)) {
            $paxCode = (string) (self::attr($passengerType, 'Code', 'ADT') ?? 'ADT');
        }

        $checkedItems = strcasecmp((string) ($checkedFriendly['amount'] ?? ''), 'Not included') !== 0
            ? [$checkedFriendly]
            : [];
        $cabinItems = strcasecmp((string) ($cabinFriendly['amount'] ?? ''), 'Not included') !== 0
            ? [$cabinFriendly]
            : [];

        return [
            'pax_type' => self::paxLabel($paxCode),
            'checked' => $checkedFriendly['display'],
            'cabin' => $cabinFriendly['display'],
            'checked_friendly' => [
                'amount' => $checkedFriendly['amount'],
                'display' => $checkedFriendly['display'],
                'label' => $checkedFriendly['label'],
                'note' => $checkedFriendly['note'],
            ],
            'cabin_friendly' => [
                'amount' => $cabinFriendly['amount'],
                'display' => $cabinFriendly['display'],
                'label' => $cabinFriendly['label'],
                'note' => $cabinFriendly['note'],
            ],
            'checked_items' => $checkedItems,
            'cabin_items' => $cabinItems,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $checked
     * @param  list<array<string, mixed>>  $cabin
     * @param  array{display: string, amount: string, label: string, note: ?string}  $checkedFriendly
     * @param  array{display: string, amount: string, label: string, note: ?string}  $cabinFriendly
     * @return list<string>
     */
    private static function buildSummaryItems(array $checked, array $cabin, array $checkedFriendly, array $cabinFriendly): array
    {
        $items = [];

        if (strcasecmp((string) ($checkedFriendly['amount'] ?? ''), 'Not included') !== 0) {
            $items[] = trim((string) ($checkedFriendly['display'] ?? '')) . ' checked';
        }

        if (strcasecmp((string) ($cabinFriendly['amount'] ?? ''), 'Not included') !== 0) {
            $items[] = trim((string) ($cabinFriendly['display'] ?? '')) . ' cabin';
        }

        if ($items !== []) {
            return array_values(array_filter(array_unique($items)));
        }

        foreach ($checked as $row) {
            $pill = trim((string) ($row['allowance'] ?? ''));
            if ($pill !== '' && ! in_array($pill, $items, true)) {
                $items[] = $pill;
            }
        }

        foreach ($cabin as $row) {
            $pill = trim((string) ($row['allowance'] ?? ''));
            if ($pill !== '' && ! in_array($pill, $items, true)) {
                $items[] = $pill;
            }
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $legs
     * @return list<string>
     */
    private static function segmentRoutesFromLegs(array $legs): array
    {
        $routes = [];

        foreach ($legs as $leg) {
            foreach ($leg['segments'] ?? [] as $segment) {
                if (! is_array($segment)) {
                    continue;
                }

                $from = strtoupper(trim((string) ($segment['from'] ?? '')));
                $to = strtoupper(trim((string) ($segment['to'] ?? '')));

                if ($from === '' || $to === '') {
                    continue;
                }

                $route = $from . ' → ' . $to;
                if (! in_array($route, $routes, true)) {
                    $routes[] = $route;
                }
            }
        }

        return $routes;
    }

    /**
     * @return array{display: string, amount: string, label: string, note: ?string}
     */
    private static function notIncludedFriendly(bool $isCabin): array
    {
        $label = $isCabin ? 'Cabin Baggage' : 'Check-in Baggage';

        return [
            'amount' => 'Not included',
            'label' => $label,
            'note' => null,
            'display' => 'Not included',
        ];
    }

    /**
     * @return array{display: string, amount: string, label: string, note: ?string}
     */
    private static function friendlyResult(string $amount, string $label, ?string $note): array
    {
        return [
            'amount' => $amount,
            'label' => $label,
            'note' => $note,
            'display' => $note !== null && $note !== '' ? $amount . ' · ' . $note : $amount,
        ];
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

    /**
     * @return list<mixed>
     */
    private static function asList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            return [$value];
        }

        if ($value === []) {
            return [];
        }

        return array_is_list($value) ? $value : [$value];
    }

    private static function attr(mixed $node, string $name, mixed $default = null): mixed
    {
        if (! is_array($node)) {
            return $default;
        }

        if (isset($node['@attributes'][$name])) {
            return $node['@attributes'][$name];
        }

        if (array_key_exists($name, $node)) {
            return $node[$name];
        }

        return $default;
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyResult(): array
    {
        return [
            'summary' => null,
            'summary_items' => [],
            'checked' => [],
            'cabin' => [],
            'pax_table' => [],
            'cabin_notes' => [],
        ];
    }
}
