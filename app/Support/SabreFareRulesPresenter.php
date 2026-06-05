<?php

namespace App\Support;

use Carbon\Carbon;

final class SabreFareRulesPresenter
{
    /**
     * @param array<string, mixed> $pricingBlock
     * @param array<string, mixed> $grouped
     *
     * @return array{
     *     refundable: bool,
     *     refund_label: string,
     *     fare_brand: ?string,
     *     validating_carrier: string,
     *     passenger_type: string,
     *     e_ticketable: ?bool,
     *     last_ticket_date: ?string,
     *     last_ticket_time: ?string,
     *     last_ticket_display: ?string,
     *     components: list<array{
     *         route: string,
     *         fare_basis: ?string,
     *         fare_rule: ?string,
     *         cabin: ?string,
     *         brand: ?string,
     *         valid_from: ?string,
     *         valid_to: ?string,
     *         valid_from_display: ?string,
     *         valid_to_display: ?string
     *     }>,
     *     penalties: list<array{
     *         type: string,
     *         type_label: string,
     *         applicability: string,
     *         amount: ?float,
     *         currency: ?string,
     *         refundable: ?bool,
     *         changeable: ?bool,
     *         summary: string
     *     }>,
     *     policy_sections: list<array{title: string, items: list<string>}>,
     *     notes: list<string>
     * }
     */
    public static function fromPricingBlock(array $pricingBlock, array $grouped = []): array
    {
        $passengerFare = data_get($pricingBlock, 'fare.passengerInfoList.0.passengerInfo', []);
        $fareComponentById = collect($grouped['fareComponentDescs'] ?? [])->keyBy('id');
        $components = [];

        foreach (($passengerFare['fareComponents'] ?? []) as $component) {
            if (!is_array($component)) {
                continue;
            }

            $desc = $fareComponentById->get($component['ref'] ?? null);
            $from = strtoupper(trim((string) ($component['beginAirport'] ?? '')));
            $to = strtoupper(trim((string) ($component['endAirport'] ?? '')));
            $route = ($from !== '' && $to !== '') ? $from . ' → ' . $to : 'Segment';
            $validFrom = self::stringOrNull($desc['notValidBefore'] ?? null);
            $validTo = self::stringOrNull($desc['notValidAfter'] ?? null);

            $components[] = [
                'route' => $route,
                'fare_basis' => self::stringOrNull($desc['fareBasisCode'] ?? null),
                'fare_rule' => self::stringOrNull($desc['fareRule'] ?? null),
                'cabin' => self::resolveComponentCabin($component, is_array($desc) ? $desc : null),
                'brand' => self::componentBrand(is_array($desc) ? $desc : null),
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'valid_from_display' => self::formatDisplayDate($validFrom),
                'valid_to_display' => self::formatDisplayDate($validTo),
            ];
        }

        $components = self::uniqueComponents($components);
        $brand = SabreFareBrandPresenter::fromPricingBlock($pricingBlock, $grouped);

        foreach ($components as $index => $component) {
            if (($component['brand'] ?? null) === null && $brand !== null && count($components) === 1) {
                $components[$index]['brand'] = $brand;
            }
        }

        $lastTicketDate = self::stringOrNull(data_get($pricingBlock, 'fare.lastTicketDate'));
        $lastTicketTime = self::stringOrNull(data_get($pricingBlock, 'fare.lastTicketTime'));
        $lastTicketDisplay = self::formatDisplayDateTime($lastTicketDate, $lastTicketTime);
        $penalties = self::extractPenalties($passengerFare, $grouped);
        $refundable = self::resolveRefundable(
            $passengerFare,
            $penalties,
            $fareComponentById,
        );
        $nonRefundable = ! $refundable;

        return [
            'refundable' => $refundable,
            'refund_label' => $refundable ? 'Refundable' : 'Non-Refundable',
            'fare_brand' => $brand,
            'validating_carrier' => strtoupper(trim((string) data_get($pricingBlock, 'fare.validatingCarrierCode', ''))),
            'passenger_type' => strtoupper(trim((string) ($passengerFare['passengerType'] ?? 'ADT'))),
            'e_ticketable' => data_get($pricingBlock, 'fare.eTicketable'),
            'last_ticket_date' => $lastTicketDate,
            'last_ticket_time' => $lastTicketTime,
            'last_ticket_display' => $lastTicketDisplay,
            'components' => $components,
            'penalties' => $penalties,
            'policy_sections' => self::buildPolicySections($penalties, $nonRefundable, $lastTicketDisplay),
            'notes' => self::buildNotes($nonRefundable, $lastTicketDisplay, $penalties),
        ];
    }

    /**
     * @param array<string, mixed> $passengerFare
     * @param array<string, mixed> $grouped
     *
     * @return list<array{
     *     type: string,
     *     type_label: string,
     *     applicability: string,
     *     amount: ?float,
     *     currency: ?string,
     *     refundable: ?bool,
     *     changeable: ?bool,
     *     summary: string
     * }>
     */
    private static function extractPenalties(array $passengerFare, array $grouped): array
    {
        $penaltyById = collect($grouped['penaltyDescs'] ?? [])->keyBy('id');
        $rawPenalties = $passengerFare['penalties']
            ?? data_get($passengerFare, 'penaltiesInformation.penalties', [])
            ?? data_get($passengerFare, 'penaltyInformation.penalties', [])
            ?? data_get($passengerFare, 'penalty.penalties', []);

        if (!is_array($rawPenalties)) {
            return [];
        }

        $penalties = [];

        foreach ($rawPenalties as $row) {
            if (!is_array($row)) {
                continue;
            }

            $desc = null;
            $ref = $row['ref'] ?? null;

            if ($ref !== null) {
                $desc = $penaltyById->get($ref);
            }

            $merged = is_array($desc) ? array_merge($desc, $row) : $row;
            $type = strtoupper(trim((string) ($merged['type'] ?? '')));
            $applicability = self::formatApplicability((string) ($merged['applicability'] ?? ''));
            $amount = self::normalizeAmount($merged['amount'] ?? null);
            $currency = self::stringOrNull($merged['currency'] ?? ($merged['currencyCode'] ?? null));
            $refundable = array_key_exists('refundable', $merged) ? (bool) $merged['refundable'] : null;
            $changeable = array_key_exists('changeable', $merged) ? (bool) $merged['changeable'] : null;

            $penalties[] = [
                'type' => $type,
                'type_label' => self::penaltyTypeLabel($type),
                'applicability' => $applicability,
                'amount' => $amount,
                'currency' => $currency,
                'refundable' => $refundable,
                'changeable' => $changeable,
                'summary' => self::formatPenaltySummary($type, $applicability, $amount, $currency, $refundable, $changeable),
            ];
        }

        return self::uniquePenalties($penalties);
    }

    /**
     * @param  list<array<string, mixed>>  $penalties
     * @param  \Illuminate\Support\Collection<int|string, mixed>  $fareComponentById
     */
    private static function resolveRefundable(
        array $passengerFare,
        array $penalties,
        $fareComponentById,
    ): bool {
        $nonRefundableFlag = (bool) ($passengerFare['nonRefundable'] ?? false);
        $refundPermitted = self::refundPermittedFromPenalties($penalties);

        if ($refundPermitted === true) {
            return true;
        }

        if ($refundPermitted === false) {
            return false;
        }

        if (! $nonRefundableFlag) {
            return true;
        }

        // Sabre often sets nonRefundable on premium "Saver/Basic" brands even when the
        // provider portal still treats them as refundable with airline penalties.
        return self::isPremiumCabinFare($passengerFare, $fareComponentById);
    }

    /**
     * @param  list<array<string, mixed>>  $penalties
     */
    private static function refundPermittedFromPenalties(array $penalties): ?bool
    {
        $refundPenalties = array_values(array_filter($penalties, static function (array $penalty): bool {
            $type = strtoupper((string) ($penalty['type'] ?? ''));

            return in_array($type, ['REFUND', 'REF', 'CANCEL', 'CANCELLATION'], true);
        }));

        if ($refundPenalties === []) {
            return null;
        }

        $allowed = false;
        $denied = false;

        foreach ($refundPenalties as $penalty) {
            if (($penalty['refundable'] ?? null) === true) {
                $allowed = true;
            }

            if (($penalty['refundable'] ?? null) === false) {
                $denied = true;
            }

            if (($penalty['amount'] ?? null) !== null && ($penalty['refundable'] ?? null) !== false) {
                $allowed = true;
            }
        }

        if ($allowed) {
            return true;
        }

        if ($denied) {
            return false;
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, mixed>  $fareComponentById
     */
    private static function isPremiumCabinFare(array $passengerFare, $fareComponentById): bool
    {
        foreach (($passengerFare['fareComponents'] ?? []) as $component) {
            if (! is_array($component)) {
                continue;
            }

            $desc = $fareComponentById->get($component['ref'] ?? null);
            if (! is_array($desc)) {
                continue;
            }

            $family = FlightCabinPreference::familyFromSabreCode($desc['cabinCode'] ?? null);

            if (in_array($family, ['Business', 'First', 'Premium Economy'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array<string, mixed>> $penalties
     *
     * @return list<array{title: string, items: list<string>}>
     */
    private static function buildPolicySections(array $penalties, bool $nonRefundable, ?string $lastTicketDisplay): array
    {
        $sections = [];
        $grouped = [];

        foreach ($penalties as $penalty) {
            $label = (string) ($penalty['type_label'] ?? 'Policy');
            $grouped[$label][] = (string) ($penalty['summary'] ?? '');
        }

        foreach ($grouped as $title => $items) {
            $items = array_values(array_filter(array_unique($items)));
            if ($items !== []) {
                $sections[] = [
                    'title' => $title,
                    'items' => $items,
                ];
            }
        }

        if ($sections === [] && $nonRefundable) {
            $sections[] = [
                'title' => 'Refund Policy',
                'items' => ['This fare is non-refundable.'],
            ];
        } elseif ($sections === [] && !$nonRefundable) {
            $sections[] = [
                'title' => 'Refund Policy',
                'items' => ['This fare is refundable. Airline penalties may apply.'],
            ];
        }

        if ($lastTicketDisplay !== null) {
            $sections[] = [
                'title' => 'Ticketing',
                'items' => ['Ticket must be issued by ' . $lastTicketDisplay . '.'],
            ];
        }

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $component
     * @param  array<string, mixed>|null  $desc
     */
    private static function resolveComponentCabin(array $component, ?array $desc): ?string
    {
        $cabin = self::stringOrNull(is_array($desc) ? ($desc['cabinCode'] ?? null) : null);

        if ($cabin !== null) {
            return $cabin;
        }

        foreach (($component['segments'] ?? []) as $segmentWrap) {
            $segmentCabin = self::stringOrNull(data_get($segmentWrap, 'segment.cabinCode'));

            if ($segmentCabin !== null) {
                return $segmentCabin;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $desc
     */
    private static function componentBrand(?array $desc): ?string
    {
        if (!is_array($desc)) {
            return null;
        }

        $brand = trim((string) data_get($desc, 'brand.brandName', ''));

        return $brand !== '' ? $brand : null;
    }

    /**
     * @param list<array<string, mixed>> $components
     *
     * @return list<array<string, mixed>>
     */
    private static function uniqueComponents(array $components): array
    {
        $unique = [];

        foreach ($components as $component) {
            $key = strtolower(json_encode($component) ?: '');

            if (!isset($unique[$key])) {
                $unique[$key] = $component;
            }
        }

        return array_values($unique);
    }

    /**
     * @param list<array<string, mixed>> $penalties
     *
     * @return list<array<string, mixed>>
     */
    private static function uniquePenalties(array $penalties): array
    {
        $unique = [];

        foreach ($penalties as $penalty) {
            $key = strtolower(json_encode($penalty) ?: '');

            if (!isset($unique[$key])) {
                $unique[$key] = $penalty;
            }
        }

        return array_values($unique);
    }

    /**
     * @param list<array<string, mixed>> $penalties
     *
     * @return list<string>
     */
    private static function buildNotes(bool $nonRefundable, ?string $lastTicketDisplay, array $penalties): array
    {
        if ($penalties !== [] || $lastTicketDisplay !== null) {
            return [];
        }

        return [
            $nonRefundable
                ? 'This fare is marked non-refundable.'
                : 'This fare is marked refundable.',
        ];
    }

    private static function penaltyTypeLabel(string $type): string
    {
        return match ($type) {
            'REFUND', 'REF', 'CANCEL', 'CANCELLATION' => 'Cancellations',
            'EXCHANGE', 'REISSUE', 'REVALIDATION', 'CHANGE' => 'Changes',
            'NOSHOW', 'NO-SHOW', 'NO SHOW' => 'No-show',
            default => $type !== '' ? ucwords(strtolower(str_replace('_', ' ', $type))) : 'Policy',
        };
    }

    private static function formatApplicability(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'Any time';
        }

        return match (strtoupper($value)) {
            'BEFORE' => 'Before departure',
            'AFTER' => 'After departure',
            default => ucwords(strtolower($value)),
        };
    }

    private static function formatPenaltySummary(
        string $type,
        string $applicability,
        ?float $amount,
        ?string $currency,
        ?bool $refundable,
        ?bool $changeable,
    ): string {
        if ($refundable === false || $changeable === false) {
            $label = self::penaltyTypeLabel($type);

            return $applicability . ' — ' . $label . ' not permitted.';
        }

        if ($amount !== null && $amount > 0 && $currency !== null) {
            return $applicability . ' — ' . strtoupper($currency) . ' ' . number_format($amount, 2) . ' per ticket.';
        }

        if ($amount === 0.0) {
            return $applicability . ' — No penalty.';
        }

        if ($refundable === true || $changeable === true) {
            return $applicability . ' — Permitted.';
        }

        return $applicability . ' — See airline fare rules.';
    }

    private static function normalizeAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private static function formatDisplayDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('D, M j, Y');
        } catch (\Throwable) {
            return $date;
        }
    }

    private static function formatDisplayDateTime(?string $date, ?string $time): ?string
    {
        if ($date === null) {
            return null;
        }

        try {
            $value = trim($date . ($time !== null && $time !== '' ? ' ' . $time : ''));
            $parsed = Carbon::parse($value);

            if ($time !== null && $time !== '') {
                return $parsed->format('D, M j, Y') . ' · ' . $parsed->format('g:i A');
            }

            return $parsed->format('D, M j, Y');
        } catch (\Throwable) {
            return trim($date . ($time !== null && $time !== '' ? ' ' . $time : ''));
        }
    }

    private static function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
