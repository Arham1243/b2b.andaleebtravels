<?php

namespace App\Support;

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
     *     components: list<array{
     *         route: string,
     *         fare_basis: ?string,
     *         fare_rule: ?string,
     *         cabin: ?string,
     *         brand: ?string,
     *         valid_from: ?string,
     *         valid_to: ?string
     *     }>,
     *     notes: list<string>
     * }
     */
    public static function fromPricingBlock(array $pricingBlock, array $grouped = []): array
    {
        $passengerFare = data_get($pricingBlock, 'fare.passengerInfoList.0.passengerInfo', []);
        $nonRefundable = (bool) ($passengerFare['nonRefundable'] ?? false);
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

            $components[] = [
                'route' => $route,
                'fare_basis' => self::stringOrNull($desc['fareBasisCode'] ?? null),
                'fare_rule' => self::stringOrNull($desc['fareRule'] ?? null),
                'cabin' => self::stringOrNull($desc['cabinCode'] ?? null),
                'brand' => self::componentBrand(is_array($desc) ? $desc : null),
                'valid_from' => self::stringOrNull($desc['notValidBefore'] ?? null),
                'valid_to' => self::stringOrNull($desc['notValidAfter'] ?? null),
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

        return [
            'refundable' => !$nonRefundable,
            'refund_label' => $nonRefundable ? 'Non-Refundable' : 'Refundable',
            'fare_brand' => $brand,
            'validating_carrier' => strtoupper(trim((string) data_get($pricingBlock, 'fare.validatingCarrierCode', ''))),
            'passenger_type' => strtoupper(trim((string) ($passengerFare['passengerType'] ?? 'ADT'))),
            'e_ticketable' => data_get($pricingBlock, 'fare.eTicketable'),
            'last_ticket_date' => $lastTicketDate,
            'last_ticket_time' => $lastTicketTime,
            'components' => $components,
            'notes' => self::buildNotes($nonRefundable, $lastTicketDate, $lastTicketTime),
        ];
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
     * @return list<string>
     */
    private static function buildNotes(bool $nonRefundable, ?string $lastTicketDate, ?string $lastTicketTime): array
    {
        $notes = [];

        if ($nonRefundable) {
            $notes[] = 'This fare is marked non-refundable. Airline change and cancellation penalties may apply.';
        } else {
            $notes[] = 'This fare is marked refundable. Airline penalties and fare difference may still apply on changes or cancellations.';
        }

        if ($lastTicketDate !== null) {
            $deadline = $lastTicketDate;

            if ($lastTicketTime !== null) {
                $deadline .= ' ' . $lastTicketTime;
            }

            $notes[] = 'Ticket must be issued by ' . $deadline . ' (local agency time).';
        }

        $notes[] = 'Full fare rule details are governed by the validating carrier and may change before ticketing.';

        return $notes;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
