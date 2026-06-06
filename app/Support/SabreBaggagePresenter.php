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
            $isCabin = self::isCabinProvision($provisionType);
            $desc = self::resolveAllowanceDesc($row, $allowanceById);
            $friendly = self::parseAllowanceFriendly($desc, $isCabin);

            $entry = [
                'route' => $route,
                'airline' => $airline,
                'allowance' => $friendly['display'],
                'friendly' => $friendly,
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
        $paxTable = self::paxAllowanceTable($pricingBlock, $grouped);

        return [
            'summary' => self::buildSummary($checked, $cabin),
            'checked' => $checked,
            'cabin' => $cabin,
            'pax_table' => $paxTable,
            'cabin_notes' => self::collectCabinNotes($cabin, $paxTable),
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

        $amounts = array_values(array_unique(array_map(static fn (array $row): string => (string) ($row['amount'] ?? ''), $friendlyRows)));

        return [
            'amount' => implode(' / ', $amounts),
            'label' => (string) ($friendlyRows[0]['label'] ?? ''),
            'note' => $friendlyRows[0]['note'] ?? null,
            'display' => implode(' / ', array_map(static fn (array $row): string => (string) ($row['display'] ?? ''), $friendlyRows)),
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

        if (preg_match('/\d+\s*LB\s*\/\s*(\d+)\s*KG\b/', $upper, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/(?:UPTO|UP TO)\s*(\d+)\s*KG\b/', $upper, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/(\d+)\s*KG\b/', $upper, $matches)) {
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
            'cabin_notes' => [],
        ];
    }
}
