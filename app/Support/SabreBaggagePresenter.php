<?php

namespace App\Support;

final class SabreBaggagePresenter
{
    /**
     * Raw Sabre baggage payload for debugging (PC / cabin / checked).
     *
     * @param  array<string, mixed>  $grouped
     * @param  array<string, mixed>  $pricingBlock
     * @return array<string, mixed>
     */
    public static function debugExport(array $grouped, array $pricingBlock): array
    {
        $allowanceById = collect($grouped['baggageAllowanceDescs'] ?? [])->keyBy('id');
        $passengerRows = data_get($pricingBlock, 'fare.passengerInfoList', []);
        $usedAllowanceIds = [];

        $paxBaggage = [];

        foreach ($passengerRows as $paxRow) {
            if (! is_array($paxRow)) {
                continue;
            }

            $paxInfo = $paxRow['passengerInfo'] ?? [];
            if (! is_array($paxInfo)) {
                continue;
            }

            $entries = [];

            foreach ($paxInfo['baggageInformation'] ?? [] as $bagRow) {
                if (! is_array($bagRow)) {
                    continue;
                }

                $allowanceRef = data_get($bagRow, 'allowance.ref');
                if ($allowanceRef !== null) {
                    $usedAllowanceIds[] = $allowanceRef;
                }

                $desc = self::resolveAllowanceDesc($bagRow, $allowanceById);
                $provisionType = strtoupper(trim((string) ($bagRow['provisionType'] ?? 'A')));
                $isCabin = self::isCabinProvision($provisionType);

                $entries[] = [
                    'provision_type' => $provisionType,
                    'provision_label' => $isCabin ? 'Cabin / carry-on (B)' : 'Checked baggage (A)',
                    'airline_code' => $bagRow['airlineCode'] ?? null,
                    'segments' => $bagRow['segments'] ?? [],
                    'allowance_ref' => $allowanceRef,
                    'allowance_desc_raw' => $desc,
                    'friendly_parsed' => self::parseAllowanceFriendly($desc, $isCabin),
                ];
            }

            $paxBaggage[] = [
                'passenger_type' => $paxInfo['passengerType'] ?? null,
                'baggage_information' => $entries,
            ];
        }

        $usedAllowanceIds = array_values(array_unique($usedAllowanceIds));
        $allowanceDescs = [];

        foreach ($usedAllowanceIds as $id) {
            $desc = $allowanceById->get($id);
            if (is_array($desc)) {
                $allowanceDescs[] = array_merge(['id' => $id], $desc);
            }
        }

        return [
            'provision_type_legend' => [
                'A' => 'Checked baggage allowance',
                'B' => 'Cabin / carry-on baggage allowance',
            ],
            'baggage_allowance_descs_used' => $allowanceDescs,
            'baggage_allowance_descs_all' => array_values($allowanceById->all()),
            'passenger_baggage' => $paxBaggage,
        ];
    }

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
        $segmentRoutes = self::segmentRoutes($fareComponents, $grouped);

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
            $airline = strtoupper(trim((string) ($row['airlineCode'] ?? '')));
            $isCabin = self::isCabinProvision($provisionType);
            $desc = self::resolveAllowanceDesc($row, $allowanceById);
            $friendly = self::parseAllowanceFriendly($desc, $isCabin);
            $targetRoutes = self::resolveBaggageTargetRoutes($row, $segmentRoutes);

            foreach ($targetRoutes as $route) {
                $entry = [
                    'route' => $route,
                    'airline' => $airline,
                    'allowance' => $friendly['display'],
                    'friendly' => $friendly,
                    'provision_type' => $provisionType,
                ];

                if ($isCabin) {
                    $cabin[] = $entry;
                } else {
                    $checked[] = $entry;
                }
            }
        }

        $checked = self::fillMissingSegmentAllowances($checked, $segmentRoutes, false);
        $cabin = self::fillMissingSegmentAllowances($cabin, $segmentRoutes, true);
        $paxTable = self::paxAllowanceTable($pricingBlock, $grouped);
        $summaryItems = self::buildSummaryItems($checked, $cabin);

        return [
            'summary' => $summaryItems !== [] ? implode(' · ', $summaryItems) : null,
            'summary_items' => $summaryItems,
            'checked' => $checked,
            'cabin' => $cabin,
            'pax_table' => $paxTable,
            'cabin_notes' => self::collectCabinNotes($cabin, $paxTable),
        ];
    }

    /**
     * Align per-segment baggage rows with built flight legs and propagate itinerary-level allowance.
     *
     * @param  array<string, mixed>  $baggageDetails
     * @param  list<array<string, mixed>>  $legs
     * @return array<string, mixed>
     */
    public static function alignWithFlightLegs(array $baggageDetails, array $legs): array
    {
        $segmentRoutes = self::mergeSegmentRoutes(
            self::routesFromLegs($legs),
            self::routesFromBaggageRows(
                array_merge($baggageDetails['checked'] ?? [], $baggageDetails['cabin'] ?? []),
            ),
        );

        if ($segmentRoutes === []) {
            return $baggageDetails;
        }

        $baggageDetails['checked'] = self::fillMissingSegmentAllowances(
            $baggageDetails['checked'] ?? [],
            $segmentRoutes,
            false,
            $baggageDetails,
        );
        $baggageDetails['cabin'] = self::fillMissingSegmentAllowances(
            $baggageDetails['cabin'] ?? [],
            $segmentRoutes,
            true,
            $baggageDetails,
        );
        $summaryItems = self::buildSummaryItems(
            $baggageDetails['checked'] ?? [],
            $baggageDetails['cabin'] ?? [],
        );
        $baggageDetails['summary_items'] = $summaryItems;
        $baggageDetails['summary'] = $summaryItems !== [] ? implode(' · ', $summaryItems) : null;

        return $baggageDetails;
    }

    /**
     * @param  array<string, mixed>  $bagRow
     * @param  array<int, string>  $segmentRoutes
     * @return list<string>
     */
    private static function resolveBaggageTargetRoutes(array $bagRow, array $segmentRoutes): array
    {
        $segmentRefs = is_array($bagRow['segments'] ?? null) ? $bagRow['segments'] : [];
        $routes = [];

        if ($segmentRefs === []) {
            return array_values(array_unique(array_filter($segmentRoutes)));
        }

        foreach ($segmentRefs as $segmentRef) {
            if (! is_array($segmentRef)) {
                continue;
            }

            $segmentId = (int) ($segmentRef['id'] ?? -1);
            $route = $segmentRoutes[$segmentId] ?? null;

            if ($route !== null && $route !== '') {
                $routes[] = $route;
            }
        }

        $routes = array_values(array_unique(array_filter($routes)));

        return $routes !== [] ? $routes : array_values(array_unique(array_filter($segmentRoutes)));
    }

    /**
     * @param  array<int, string>  $primaryRoutes
     * @param  list<string>  $secondaryRoutes
     * @return array<int, string>
     */
    private static function mergeSegmentRoutes(array $primaryRoutes, array $secondaryRoutes): array
    {
        $merged = [];
        $ordinal = 0;

        foreach (array_values(array_unique(array_filter(array_merge(
            array_values($primaryRoutes),
            $secondaryRoutes,
        )))) as $route) {
            $merged[$ordinal++] = $route;
        }

        return $merged;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<string>
     */
    private static function routesFromBaggageRows(array $rows): array
    {
        $routes = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $route = trim((string) ($row['route'] ?? ''));
            if ($route === '' || strcasecmp($route, 'All segments') === 0) {
                continue;
            }

            if (! in_array($route, $routes, true)) {
                $routes[] = $route;
            }
        }

        return $routes;
    }

    /**
     * @param  list<array<string, mixed>>  $legs
     * @return array<int, string>
     */
    public static function routesFromLegs(array $legs): array
    {
        $routes = [];
        $ordinal = 0;

        foreach ($legs as $leg) {
            if (! is_array($leg)) {
                continue;
            }

            foreach ($leg['segments'] ?? [] as $segment) {
                if (! is_array($segment)) {
                    continue;
                }

                $route = self::routeFromAirports(
                    (string) ($segment['from'] ?? ''),
                    (string) ($segment['to'] ?? ''),
                );

                if ($route !== null) {
                    $routes[$ordinal] = $route;
                }

                $ordinal++;
            }
        }

        return $routes;
    }

    /**
     * Keep every baggage row returned by Sabre and only fill routes that are missing allowance.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<int, string>  $segmentRoutes
     * @param  array<string, mixed>|null  $fullBaggage
     * @return list<array<string, mixed>>
     */
    private static function fillMissingSegmentAllowances(
        array $rows,
        array $segmentRoutes,
        bool $isCabin,
        ?array $fullBaggage = null,
    ): array {
        $filled = array_values($rows);
        $routeList = array_values(array_unique(array_filter($segmentRoutes)));

        if ($routeList === []) {
            return $filled;
        }

        $fallback = self::fallbackAllowanceForType($rows, $isCabin, $fullBaggage);
        $routeBuckets = [];

        foreach ($filled as $index => $row) {
            $route = trim((string) ($row['route'] ?? ''));

            if ($route === '' || strcasecmp($route, 'All segments') === 0) {
                continue;
            }

            $routeBuckets[$route][] = $index;
        }

        foreach ($routeList as $route) {
            $indices = $routeBuckets[$route] ?? [];
            $hasValid = false;

            foreach ($indices as $index) {
                if (! self::rowIsNotIncluded($filled[$index])) {
                    $hasValid = true;
                    break;
                }
            }

            if ($hasValid) {
                continue;
            }

            if ($indices !== [] && $fallback !== null) {
                foreach ($indices as $index) {
                    if (self::rowIsNotIncluded($filled[$index])) {
                        $filled[$index] = self::applyAllowanceToRow($filled[$index], $fallback);
                    }
                }

                continue;
            }

            if ($fallback !== null) {
                $filled[] = self::makeAllowanceRow($route, $fallback);
            }
        }

        return $filled;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function rowIsNotIncluded(array $row): bool
    {
        $amount = trim((string) data_get($row, 'friendly.amount', ''));
        $allowance = trim((string) ($row['allowance'] ?? ''));

        if ($amount !== '' && strcasecmp($amount, 'Not included') !== 0) {
            return false;
        }

        return $allowance === '' || strcasecmp($allowance, 'Not included') === 0;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>|null  $fullBaggage
     * @return array{display: string, friendly: array<string, mixed>, airline: string, provision_type: string}|null
     */
    private static function fallbackAllowanceForType(array $rows, bool $isCabin, ?array $fullBaggage): ?array
    {
        foreach ($rows as $row) {
            if (! self::rowIsNotIncluded($row)) {
                return [
                    'display' => (string) ($row['allowance'] ?? data_get($row, 'friendly.display', 'Not included')),
                    'friendly' => is_array($row['friendly'] ?? null) ? $row['friendly'] : self::parseAllowanceFriendly(null, $isCabin),
                    'airline' => (string) ($row['airline'] ?? ''),
                    'provision_type' => (string) ($row['provision_type'] ?? ($isCabin ? 'B' : 'A')),
                ];
            }
        }

        if ($fullBaggage === null) {
            return null;
        }

        $friendlyKey = $isCabin ? 'cabin_friendly' : 'checked_friendly';
        $textKey = $isCabin ? 'cabin' : 'checked';

        foreach ($fullBaggage['pax_table'] ?? [] as $paxRow) {
            if (! is_array($paxRow)) {
                continue;
            }

            $friendly = $paxRow[$friendlyKey] ?? null;
            $text = trim((string) ($paxRow[$textKey] ?? ''));

            if (is_array($friendly) && trim((string) ($friendly['amount'] ?? '')) !== '' && strcasecmp((string) $friendly['amount'], 'Not included') !== 0) {
                return [
                    'display' => (string) ($friendly['display'] ?? $friendly['amount']),
                    'friendly' => $friendly,
                    'airline' => '',
                    'provision_type' => $isCabin ? 'B' : 'A',
                ];
            }

            if ($text !== '' && strcasecmp($text, 'Not included') !== 0) {
                return [
                    'display' => $text,
                    'friendly' => self::parseAllowanceFriendly(null, $isCabin),
                    'airline' => '',
                    'provision_type' => $isCabin ? 'B' : 'A',
                ];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{display: string, friendly: array<string, mixed>, airline: string, provision_type: string}  $fallback
     * @return array<string, mixed>
     */
    private static function applyAllowanceToRow(array $row, array $fallback): array
    {
        $row['allowance'] = $fallback['display'];
        $row['friendly'] = $fallback['friendly'];
        if (($row['airline'] ?? '') === '' && $fallback['airline'] !== '') {
            $row['airline'] = $fallback['airline'];
        }
        $row['inferred_allowance'] = true;

        return $row;
    }

    /**
     * @param  array{display: string, friendly: array<string, mixed>, airline: string, provision_type: string}  $fallback
     * @return array<string, mixed>
     */
    private static function makeAllowanceRow(string $route, array $fallback): array
    {
        return [
            'route' => $route,
            'airline' => $fallback['airline'],
            'allowance' => $fallback['display'],
            'friendly' => $fallback['friendly'],
            'provision_type' => $fallback['provision_type'],
            'inferred_allowance' => true,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private static function uniqueRowsPreferAllowance(array $rows): array
    {
        $unique = [];

        foreach ($rows as $row) {
            $route = strtolower(trim((string) ($row['route'] ?? '')));
            $key = $route . '|' . strtolower((string) ($row['provision_type'] ?? ''));

            if (! isset($unique[$key])) {
                $unique[$key] = $row;
                continue;
            }

            if (self::rowIsNotIncluded($unique[$key]) && ! self::rowIsNotIncluded($row)) {
                $unique[$key] = $row;
            }
        }

        return array_values($unique);
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

            $checkedFriendly = [];
            $cabinFriendly = [];

            foreach ($paxInfo['baggageInformation'] ?? [] as $bagRow) {
                if (!is_array($bagRow)) {
                    continue;
                }

                $provisionType = strtoupper(trim((string) ($bagRow['provisionType'] ?? 'A')));
                $isCabin = self::isCabinProvision($provisionType);
                $friendly = self::parseAllowanceFriendly(self::resolveAllowanceDesc($bagRow, $allowanceById), $isCabin);

                if ($isCabin) {
                    $cabinFriendly[] = $friendly;
                } else {
                    $checkedFriendly[] = $friendly;
                }
            }

            $checkedSummary = self::summarizeFriendlyList($checkedFriendly);
            $cabinSummary = self::summarizeFriendlyList($cabinFriendly);

            $rows[] = [
                'pax_type' => self::paxLabel((string) ($paxInfo['passengerType'] ?? 'ADT')),
                'checked' => $checkedSummary['display'],
                'cabin' => $cabinSummary['display'],
                'checked_friendly' => $checkedSummary,
                'cabin_friendly' => $cabinSummary,
                'checked_items' => $checkedFriendly,
                'cabin_items' => $cabinFriendly,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $fareComponents
     * @param  array<string, mixed>  $grouped
     * @return array<int, string>
     */
    private static function segmentRoutes(array $fareComponents, array $grouped = []): array
    {
        $scheduleById = collect($grouped['scheduleDescs'] ?? [])->keyBy('id');
        $routes = [];
        $ordinal = 0;

        foreach ($fareComponents as $component) {
            if (! is_array($component)) {
                continue;
            }

            $segments = is_array($component['segments'] ?? null) ? $component['segments'] : [];

            if ($segments !== []) {
                foreach ($segments as $segWrap) {
                    if (! is_array($segWrap)) {
                        continue;
                    }

                    $segment = is_array($segWrap['segment'] ?? null) ? $segWrap['segment'] : [];
                    $segmentId = array_key_exists('id', $segment) ? (int) $segment['id'] : $ordinal;
                    $route = self::routeForFareSegment($segment, $scheduleById, $component);

                    if ($route !== null) {
                        $routes[$segmentId] = $route;
                        $routes[$ordinal] = $route;
                    }

                    $ordinal++;
                }

                continue;
            }

            $route = self::routeFromAirports(
                (string) ($component['beginAirport'] ?? ''),
                (string) ($component['endAirport'] ?? ''),
            );

            if ($route !== null) {
                $routes[$ordinal] = $route;
            }

            $ordinal++;
        }

        return $routes;
    }

    /**
     * @param  array<string, mixed>  $segment
     * @param  \Illuminate\Support\Collection<int|string, mixed>  $scheduleById
     * @param  array<string, mixed>  $component
     */
    private static function routeForFareSegment(array $segment, $scheduleById, array $component): ?string
    {
        $route = self::routeFromAirports(
            (string) (data_get($segment, 'origin')
                ?? data_get($segment, 'departure.airport')
                ?? data_get($segment, 'departureAirport')
                ?? ''),
            (string) (data_get($segment, 'destination')
                ?? data_get($segment, 'arrival.airport')
                ?? data_get($segment, 'arrivalAirport')
                ?? ''),
        );

        if ($route !== null) {
            return $route;
        }

        foreach (['scheduleRef', 'schedule', 'ref'] as $scheduleKey) {
            $scheduleRef = data_get($segment, $scheduleKey);
            $schedule = is_array($scheduleRef)
                ? $scheduleRef
                : $scheduleById->get($scheduleRef);

            if (! is_array($schedule)) {
                continue;
            }

            $route = self::routeFromAirports(
                (string) data_get($schedule, 'departure.airport'),
                (string) data_get($schedule, 'arrival.airport'),
            );

            if ($route !== null) {
                return $route;
            }
        }

        $componentSegments = is_array($component['segments'] ?? null) ? $component['segments'] : [];
        if (count($componentSegments) === 1) {
            return self::routeFromAirports(
                (string) ($component['beginAirport'] ?? ''),
                (string) ($component['endAirport'] ?? ''),
            );
        }

        return null;
    }

    private static function routeFromAirports(string $from, string $to): ?string
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        if ($from === '' || $to === '') {
            return null;
        }

        return $from . ' → ' . $to;
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
     * User-facing baggage line: amount, type label, optional piece→kg note.
     *
     * @param  array<string, mixed>|null  $desc
     * @return array{amount: string, label: string, note: ?string, display: string}
     */
    private static function parseAllowanceFriendly(?array $desc, bool $cabinBaggage): array
    {
        $typeLabel = $cabinBaggage ? 'Cabin Baggage' : 'Check-in Baggage';

        if ($desc === null) {
            return [
                'amount' => 'Not included',
                'label' => $typeLabel,
                'note' => null,
                'display' => 'Not included',
            ];
        }

        $descriptionText = self::collectDescriptionText($desc);
        $pieces = self::extractPieceCount($desc, $descriptionText);
        $kg = self::extractKilogramsFromText($descriptionText);

        if ($kg === null) {
            $weight = (int) ($desc['weight'] ?? 0);
            $unit = self::normalizeUnit((string) ($desc['unit'] ?? ''));

            if ($weight > 0 && $unit === 'kg') {
                $kg = $weight;
            }
        }

        if ($cabinBaggage) {
            if ($pieces > 0) {
                $amount = $pieces . ' PC';
                $note = ($kg !== null && $kg > 0)
                    ? self::pieceEquivalentNote($pieces, $kg)
                    : null;

                return self::friendlyResult($amount, $typeLabel, $note);
            }

            if ($kg !== null && $kg > 0) {
                return self::friendlyResult($kg . ' kg', $typeLabel, null);
            }

            $fallback = self::formatAllowance($desc, true);

            return self::friendlyResult(
                $fallback === 'Not included' ? 'Not included' : $fallback,
                $typeLabel,
                null,
            );
        }

        if ($kg !== null && $kg > 0) {
            return self::friendlyResult($kg . ' kg', $typeLabel, null);
        }

        if ($pieces > 0) {
            return self::friendlyResult($pieces . ' PC', $typeLabel, null);
        }

        $fallback = self::formatAllowance($desc, false);

        return self::friendlyResult(
            $fallback === 'Not included' ? 'Not included' : $fallback,
            $typeLabel,
            null,
        );
    }

    /**
     * @return array{amount: string, label: string, note: ?string, display: string}
     */
    private static function friendlyResult(string $amount, string $label, ?string $note): array
    {
        $amount = trim($amount);

        if ($amount === '' || strcasecmp($amount, 'Not included') === 0) {
            return [
                'amount' => 'Not included',
                'label' => $label,
                'note' => null,
                'display' => 'Not included',
            ];
        }

        return [
            'amount' => $amount,
            'label' => $label,
            'note' => $note,
            'display' => $amount . ' (' . $label . ')',
        ];
    }

    private static function pieceEquivalentNote(int $pieces, int $kg): string
    {
        if ($pieces === 1) {
            return '1 piece cabin baggage is equivalent to ' . $kg . ' kg';
        }

        return $pieces . ' pieces cabin baggage are equivalent to ' . $kg . ' kg each';
    }

    /**
     * @param  array<string, mixed>  $desc
     */
    private static function extractPieceCount(array $desc, string $descriptionText): int
    {
        $pieces = (int) ($desc['pieceCount'] ?? 0);

        if ($pieces <= 0 && preg_match('/(\d+)\s*PC\b/i', $descriptionText, $matches)) {
            $pieces = (int) $matches[1];
        }

        return max(0, $pieces);
    }

    /**
     * @param  list<array{amount: string, label: string, note: ?string, display: string}>  $friendlyRows
     * @return array{amount: string, label: string, note: ?string, display: string}
     */
    private static function summarizeFriendlyList(array $friendlyRows): array
    {
        $friendlyRows = array_values(array_filter($friendlyRows, static fn (array $row): bool => ($row['amount'] ?? '') !== 'Not included'));

        if ($friendlyRows === []) {
            return self::friendlyResult('Not included', '', null);
        }

        if (count($friendlyRows) === 1) {
            return $friendlyRows[0];
        }

        $amounts = array_map(static fn (array $row): string => (string) ($row['amount'] ?? ''), $friendlyRows);
        $note = null;

        foreach ($friendlyRows as $row) {
            $rowNote = trim((string) ($row['note'] ?? ''));

            if ($rowNote !== '') {
                $note = $rowNote;
                break;
            }
        }

        return [
            'amount' => implode(' · ', $amounts),
            'label' => (string) ($friendlyRows[0]['label'] ?? ''),
            'note' => $note,
            'display' => implode(' · ', $amounts),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $cabinRows
     * @param  list<array<string, mixed>>  $paxTable
     * @return list<string>
     */
    private static function collectCabinNotes(array $cabinRows, array $paxTable): array
    {
        $notes = [];

        foreach ($cabinRows as $row) {
            $note = trim((string) data_get($row, 'friendly.note', ''));

            if ($note !== '' && ! in_array($note, $notes, true)) {
                $notes[] = $note;
            }
        }

        foreach ($paxTable as $paxRow) {
            $note = trim((string) data_get($paxRow, 'cabin_friendly.note', ''));

            if ($note !== '' && ! in_array($note, $notes, true)) {
                $notes[] = $note;
            }
        }

        return $notes;
    }

    /**
     * @param array<string, mixed>|null $desc
     */
    private static function formatAllowance(?array $desc, bool $cabinBaggage = false): string
    {
        if ($desc === null) {
            return 'Not included';
        }

        $descriptionText = self::collectDescriptionText($desc);

        if ($cabinBaggage) {
            $cabinLabel = self::formatCabinAllowanceLabel($desc, $descriptionText);
            if ($cabinLabel !== null) {
                return $cabinLabel;
            }
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

    /**
     * @param  array<string, mixed>  $desc
     */
    private static function collectDescriptionText(array $desc): string
    {
        $chunks = [];

        foreach (['description1', 'description2', 'description', 'Description'] as $key) {
            $text = trim((string) ($desc[$key] ?? ''));
            if ($text !== '') {
                $chunks[] = $text;
            }
        }

        return implode(' ', $chunks);
    }

    /**
     * Cabin/carry-on — pieces from API plus kg only (no lb or dimension text).
     *
     * @param  array<string, mixed>  $desc
     */
    private static function formatCabinAllowanceLabel(array $desc, string $descriptionText): ?string
    {
        $pieces = (int) ($desc['pieceCount'] ?? 0);

        if ($pieces <= 0 && preg_match('/(\d+)\s*PC\b/i', $descriptionText, $matches)) {
            $pieces = (int) $matches[1];
        }

        $kg = self::extractKilogramsFromText($descriptionText);

        if ($kg === null) {
            $weight = (int) ($desc['weight'] ?? 0);
            $unit = self::normalizeUnit((string) ($desc['unit'] ?? ''));

            if ($weight > 0 && $unit === 'kg') {
                $kg = $weight;
            }
        }

        $parts = [];

        if ($pieces > 0) {
            $parts[] = $pieces . ' pc';
        }

        if ($kg !== null && $kg > 0) {
            $parts[] = $kg . ' kg';
        }

        return $parts !== [] ? implode(' · ', $parts) : null;
    }

    private static function extractKilogramsFromText(string $text): ?int
    {
        $upper = strtoupper(trim(preg_replace('/\s+/', ' ', $text) ?? ''));

        if ($upper === '') {
            return null;
        }

        if (preg_match('/\d+\s*(?:LB|LBS|POUND|POUNDS)\s*\/\s*(\d+)\s*(?:KG|KGS|KILO|KILOS|KILOGRAM|KILOGRAMS)\b/', $upper, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/\d+\s*LB\s*\/\s*(\d+)\s*KG\b/', $upper, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/(?:UP\s*TO|UPTO)\s*(\d+)\s*(?:KG|KGS|KILO|KILOS|KILOGRAM|KILOGRAMS)\b/', $upper, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/(\d+)\s*(?:KG|KGS|KILO|KILOS|KILOGRAM|KILOGRAMS)\b/', $upper, $matches)) {
            return (int) $matches[1];
        }

        return null;
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
        return self::uniqueRowsPreferAllowance($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $checked
     * @param  list<array<string, mixed>>  $cabin
     * @return list<string>
     */
    private static function buildSummaryItems(array $checked, array $cabin): array
    {
        $items = [];

        foreach ($checked as $row) {
            $label = self::summaryLabelForRow($row, false);

            if ($label !== null) {
                $items[] = $label;
            }
        }

        foreach ($cabin as $row) {
            $label = self::summaryLabelForRow($row, true);

            if ($label !== null) {
                $items[] = $label;
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function summaryLabelForRow(array $row, bool $isCabin): ?string
    {
        if (self::rowIsNotIncluded($row)) {
            return null;
        }

        $amount = trim((string) data_get($row, 'friendly.amount', ''));

        if ($amount === '' || strcasecmp($amount, 'Not included') === 0) {
            $amount = trim((string) ($row['allowance'] ?? ''));
        }

        if ($amount === '' || strcasecmp($amount, 'Not included') === 0) {
            return null;
        }

        if (preg_match('/\bchecked\b/i', $amount) || preg_match('/\bcabin\b/i', $amount)) {
            return $amount;
        }

        return $amount . ($isCabin ? ' cabin' : ' checked');
    }

    /**
     * @param list<array{route: string, airline: string, allowance: string, provision_type: string}> $checked
     * @param list<array{route: string, airline: string, allowance: string, provision_type: string}> $cabin
     */
    private static function buildSummary(array $checked, array $cabin): ?string
    {
        $items = self::buildSummaryItems($checked, $cabin);

        return $items !== [] ? implode(' · ', $items) : null;
    }

    /**
     * @return array{summary: null, checked: list<empty>, cabin: list<empty>, pax_table: list<empty>}
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
