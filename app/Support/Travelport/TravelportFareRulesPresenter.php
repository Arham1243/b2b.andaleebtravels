<?php

namespace App\Support\Travelport;

use Carbon\Carbon;

final class TravelportFareRulesPresenter
{
    /**
     * @param  array<string, mixed>|null  $pricingInfo
     * @param  array<string, mixed>|null  $fareInfo
     * @param  list<array<string, mixed>>  $legs
     * @return array<string, mixed>
     */
    public static function fromPricing(?array $pricingInfo, ?array $fareInfo, array $legs, ?string $fareBrand, ?string $cabinClass): array
    {
        $refundable = strtolower((string) self::attr($pricingInfo, 'Refundable', 'true')) !== 'false';
        $validatingCarrier = strtoupper(trim((string) self::attr($pricingInfo, 'PlatingCarrier', '')));
        $passengerType = self::passengerTypeCode($pricingInfo);
        $lastTicketDisplay = self::formatDisplayDateTime(self::attr($pricingInfo, 'LatestTicketingTime'));
        $penalties = self::extractPenalties($pricingInfo);
        $components = self::buildComponents($fareInfo, $legs, $fareBrand, $cabinClass);
        $brand = trim((string) ($fareBrand ?? ''));
        $nonRefundable = ! $refundable;

        return [
            'refundable' => $refundable,
            'refund_label' => $refundable ? 'Refundable' : 'Non-Refundable',
            'fare_brand' => $brand !== '' ? $brand : null,
            'validating_carrier' => $validatingCarrier,
            'passenger_type' => $passengerType,
            'e_ticketable' => strtolower((string) self::attr($pricingInfo, 'ETicketability', '')) === 'yes',
            'last_ticket_date' => null,
            'last_ticket_time' => null,
            'last_ticket_display' => $lastTicketDisplay,
            'components' => $components,
            'penalties' => $penalties,
            'policy_sections' => self::buildPolicySections($penalties, $nonRefundable, $lastTicketDisplay),
            'notes' => self::buildNotes($nonRefundable, $lastTicketDisplay, $penalties),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $fareInfo
     * @return array{fare_info_ref: string, fare_rule_key: string, provider_code: string, fare_basis: string, origin: string, destination: string, route_label: string}|null
     */
    public static function fareRuleRequest(?array $fareInfo, ?array $pricingInfo, array $legs): ?array
    {
        if ($fareInfo === null) {
            return null;
        }

        $fareInfoRef = (string) self::attr($fareInfo, 'Key', '');
        $fareRuleKey = self::extractFareRuleKey($fareInfo);
        $providerCode = (string) (self::attr($pricingInfo, 'ProviderCode', '1G') ?? '1G');

        if ($fareInfoRef === '' || $fareRuleKey === '') {
            return null;
        }

        $origin = strtoupper(trim((string) self::attr($fareInfo, 'Origin', '')));
        $destination = strtoupper(trim((string) self::attr($fareInfo, 'Destination', '')));
        $routeLabel = self::primaryRouteLabel($legs, $origin, $destination);

        return [
            'fare_info_ref' => $fareInfoRef,
            'fare_rule_key' => $fareRuleKey,
            'provider_code' => $providerCode,
            'fare_basis' => (string) self::attr($fareInfo, 'FareBasis', ''),
            'origin' => $origin,
            'destination' => $destination,
            'route_label' => $routeLabel,
        ];
    }

    /**
     * @param  array<string, mixed>  $fareInfo
     */
    public static function extractFareRuleKey(array $fareInfo): string
    {
        $node = data_get($fareInfo, 'FareRuleKey');

        if (is_string($node)) {
            return trim($node);
        }

        if (is_array($node)) {
            foreach (['#text', '@value', 0] as $key) {
                if (isset($node[$key]) && is_string($node[$key]) && trim($node[$key]) !== '') {
                    return trim($node[$key]);
                }
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>|null  $pricingInfo
     * @return list<array<string, mixed>>
     */
    private static function extractPenalties(?array $pricingInfo): array
    {
        if ($pricingInfo === null) {
            return [];
        }

        $penalties = [];

        foreach ([
            ['node' => 'ChangePenalty', 'type' => 'EXCHANGE', 'label' => 'Changes'],
            ['node' => 'CancelPenalty', 'type' => 'CANCEL', 'label' => 'Cancellation'],
        ] as $definition) {
            $node = data_get($pricingInfo, $definition['node']);
            if (! is_array($node)) {
                continue;
            }

            $money = self::parseMoneyValue(data_get($node, 'Amount') ?? self::attr($node, 'Amount'));
            $applicability = self::formatApplicability((string) self::attr($node, 'PenaltyApplies', 'Anytime'));
            $amount = $money['amount'];
            $currency = $money['currency'];
            $summary = self::formatPenaltySummary(
                $definition['type'],
                $applicability,
                $amount,
                $currency,
            );

            $penalties[] = [
                'type' => $definition['type'],
                'type_label' => $definition['label'],
                'applicability' => $applicability,
                'amount' => $amount,
                'currency' => $currency,
                'refundable' => $definition['type'] === 'CANCEL' ? null : null,
                'changeable' => $definition['type'] === 'EXCHANGE' ? true : null,
                'summary' => $summary,
            ];
        }

        return $penalties;
    }

    /**
     * @param  array<string, mixed>|null  $fareInfo
     * @param  list<array<string, mixed>>  $legs
     * @return list<array<string, mixed>>
     */
    private static function buildComponents(?array $fareInfo, array $legs, ?string $fareBrand, ?string $cabinClass): array
    {
        $components = [];
        $fareBasis = (string) self::attr($fareInfo, 'FareBasis', '');
        $validFrom = self::stringOrNull(self::attr($fareInfo, 'NotValidBefore'));
        $validTo = self::stringOrNull(self::attr($fareInfo, 'NotValidAfter'));
        $brand = trim((string) ($fareBrand ?? ''));
        $cabin = trim((string) ($cabinClass ?? ''));

        foreach ($legs as $leg) {
            $segments = $leg['segments'] ?? [];
            if ($segments === []) {
                continue;
            }

            $first = $segments[0];
            $last = $segments[array_key_last($segments)];
            $from = strtoupper(trim((string) ($first['from'] ?? '')));
            $to = strtoupper(trim((string) ($last['to'] ?? '')));
            $route = ($from !== '' && $to !== '') ? $from . ' → ' . $to : 'Segment';

            $components[] = [
                'route' => $route,
                'fare_basis' => $fareBasis !== '' ? $fareBasis : null,
                'fare_rule' => null,
                'cabin' => $cabin !== '' ? $cabin : self::stringOrNull($first['cabin_code'] ?? null),
                'brand' => $brand !== '' ? $brand : null,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'valid_from_display' => self::formatDisplayDate($validFrom),
                'valid_to_display' => self::formatDisplayDate($validTo),
            ];
        }

        if ($components === [] && $fareInfo !== null) {
            $from = strtoupper(trim((string) self::attr($fareInfo, 'Origin', '')));
            $to = strtoupper(trim((string) self::attr($fareInfo, 'Destination', '')));
            $components[] = [
                'route' => ($from !== '' && $to !== '') ? $from . ' → ' . $to : 'Segment',
                'fare_basis' => $fareBasis !== '' ? $fareBasis : null,
                'fare_rule' => null,
                'cabin' => $cabin !== '' ? $cabin : null,
                'brand' => $brand !== '' ? $brand : null,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'valid_from_display' => self::formatDisplayDate($validFrom),
                'valid_to_display' => self::formatDisplayDate($validTo),
            ];
        }

        return self::uniqueComponents($components);
    }

    /**
     * @param  list<array<string, mixed>>  $legs
     */
    private static function primaryRouteLabel(array $legs, string $origin, string $destination): string
    {
        foreach ($legs as $leg) {
            $segments = $leg['segments'] ?? [];
            if ($segments === []) {
                continue;
            }

            $first = $segments[0];
            $last = $segments[array_key_last($segments)];
            $from = strtoupper(trim((string) ($first['from'] ?? '')));
            $to = strtoupper(trim((string) ($last['to'] ?? '')));

            if ($from !== '' && $to !== '') {
                return $from . ' → ' . $to;
            }
        }

        return ($origin !== '' && $destination !== '') ? $origin . ' → ' . $destination : 'Fare Rules';
    }

    /**
     * @param  list<array<string, mixed>>  $penalties
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
        } elseif ($sections === [] && ! $nonRefundable) {
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
     * @param  list<array<string, mixed>>  $penalties
     * @return list<string>
     */
    private static function buildNotes(bool $nonRefundable, ?string $lastTicketDisplay, array $penalties): array
    {
        $notes = [];

        if ($lastTicketDisplay !== null) {
            $notes[] = 'Latest ticketing time from Travelport: ' . $lastTicketDisplay . '.';
        }

        if ($penalties === [] && $nonRefundable) {
            $notes[] = 'Fare marked non-refundable by the airline.';
        }

        return $notes;
    }

    private static function formatPenaltySummary(
        string $type,
        string $applicability,
        ?float $amount,
        ?string $currency,
    ): string {
        $label = $type === 'EXCHANGE' ? 'Change' : 'Cancel';
        $parts = [$label];

        if ($applicability !== '') {
            $parts[] = strtolower($applicability);
        }

        if ($amount !== null && $amount > 0) {
            $parts[] = 'fee ' . trim(($currency ?? '') . ' ' . number_format($amount, 2));
        }

        return implode(' ', array_filter($parts));
    }

    private static function formatApplicability(string $value): string
    {
        $value = trim($value);

        return $value !== '' ? $value : 'Anytime';
    }

    /**
     * @return array{amount: ?float, currency: ?string}
     */
    private static function parseMoneyValue(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return ['amount' => null, 'currency' => null];
        }

        $text = trim((string) $raw);
        if (preg_match('/^([A-Z]{3})([\d.,]+)$/i', $text, $matches)) {
            return [
                'amount' => round((float) str_replace(',', '', $matches[2]), 2),
                'currency' => strtoupper($matches[1]),
            ];
        }

        if (preg_match('/^([\d.,]+)\s*([A-Z]{3})$/i', $text, $matches)) {
            return [
                'amount' => round((float) str_replace(',', '', $matches[1]), 2),
                'currency' => strtoupper($matches[2]),
            ];
        }

        if (preg_match('/[\d.]/', $text)) {
            $amount = (float) preg_replace('/[^0-9.]/', '', $text);

            return ['amount' => $amount > 0 ? round($amount, 2) : null, 'currency' => null];
        }

        return ['amount' => null, 'currency' => null];
    }

    private static function passengerTypeCode(?array $pricingInfo): string
    {
        $passengerType = data_get($pricingInfo, 'PassengerType');
        if (is_array($passengerType)) {
            return strtoupper(trim((string) self::attr($passengerType, 'Code', 'ADT')));
        }

        return 'ADT';
    }

    private static function formatDisplayDate(?string $value): ?string
    {
        $value = self::stringOrNull($value);
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('j M Y');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    private static function formatDisplayDateTime(mixed $value): ?string
    {
        $value = self::stringOrNull($value);
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('j M Y, H:i');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $components
     * @return list<array<string, mixed>>
     */
    private static function uniqueComponents(array $components): array
    {
        $seen = [];
        $unique = [];

        foreach ($components as $component) {
            $key = implode('|', [
                (string) ($component['route'] ?? ''),
                (string) ($component['fare_basis'] ?? ''),
            ]);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $component;
        }

        return $unique;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
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
}
