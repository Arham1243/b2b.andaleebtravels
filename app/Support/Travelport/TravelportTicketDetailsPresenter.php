<?php

namespace App\Support\Travelport;

use App\Models\B2bFlightBooking;
use Carbon\Carbon;

final class TravelportTicketDetailsPresenter
{
    /**
     * @param  array<string, mixed>|null  $parsed
     * @return list<array<string, mixed>>
     */
    public static function fromRetrieveDocument(?array $parsed, B2bFlightBooking $booking): array
    {
        $etrNodes = self::extractEtrNodes($parsed);

        return self::mapEtrs($etrNodes, $booking);
    }

    /**
     * @param  array<string, mixed>|null  $ticketingResponse
     * @return list<array<string, mixed>>
     */
    public static function fromTicketingResponse(?array $ticketingResponse, B2bFlightBooking $booking): array
    {
        if (! is_array($ticketingResponse) || $ticketingResponse === []) {
            return [];
        }

        $etrNodes = self::asList($ticketingResponse['ETR'] ?? null);
        if ($etrNodes === []) {
            $etrNodes = self::extractEtrNodes($ticketingResponse);
        }

        return self::mapEtrs($etrNodes, $booking);
    }

    /**
     * @param  array<string, mixed>|null  $parsed
     * @return list<array<string, mixed>>
     */
    private static function extractEtrNodes(?array $parsed): array
    {
        if (! is_array($parsed)) {
            return [];
        }

        foreach ([
            'Body.AirRetrieveDocumentRsp.ETR',
            'Body.AirTicketingRsp.ETR',
            'AirRetrieveDocumentRsp.ETR',
            'AirTicketingRsp.ETR',
            'ETR',
        ] as $path) {
            $nodes = self::asList(data_get($parsed, $path));
            if ($nodes !== []) {
                return $nodes;
            }
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $etrNodes
     * @return list<array<string, mixed>>
     */
    private static function mapEtrs(array $etrNodes, B2bFlightBooking $booking): array
    {
        $tickets = [];

        foreach ($etrNodes as $etr) {
            if (! is_array($etr)) {
                continue;
            }

            foreach (self::buildTicketsFromEtr($etr, $booking) as $ticket) {
                $tickets[] = $ticket;
            }
        }

        return self::dedupeTickets($tickets);
    }

    /**
     * @param  array<string, mixed>  $etr
     * @return list<array<string, mixed>>
     */
    private static function buildTicketsFromEtr(array $etr, B2bFlightBooking $booking): array
    {
        $passengerName = self::passengerNameFromEtr($etr);
        $passengerType = self::passengerTypeFromEtr($etr);
        $pricing = self::asList($etr['AirPricingInfo'] ?? null)[0] ?? null;
        $pricingAttrs = is_array($pricing) ? ($pricing['@attributes'] ?? $pricing) : [];

        $totalPrice = self::moneyLabel(
            self::attr($pricing, 'TotalPrice') ?: self::attr($etr, 'TotalPrice'),
        );
        $basePrice = self::moneyLabel(self::attr($pricing, 'BasePrice'));
        $taxes = self::moneyLabel(self::attr($pricing, 'Taxes') ?: self::attr($etr, 'Taxes'));

        $shared = [
            'passenger_name' => $passengerName,
            'passenger_type' => $passengerType['label'],
            'passenger_type_code' => $passengerType['code'],
            'pnr' => self::attr($etr, 'ProviderLocatorCode') ?: ($booking->sabre_record_locator ?? ''),
            'air_reservation_locator' => self::nodeText($etr, 'AirReservationLocatorCode'),
            'plating_carrier' => strtoupper(self::attr($etr, 'PlatingCarrier', '')),
            'issued_date' => self::formatDateTime(self::attr($etr, 'IssuedDate')),
            'refundable' => self::boolLabel(self::attr($etr, 'Refundable')),
            'total_price' => $totalPrice,
            'base_price' => $basePrice,
            'taxes' => $taxes,
            'fare_basis' => self::attr(self::asList($pricing['FareInfo'] ?? null)[0] ?? [], 'FareBasis'),
        ];

        $ticketNodes = self::asList($etr['Ticket'] ?? null);
        if ($ticketNodes === []) {
            $number = self::attr($etr, 'TicketNumber');
            if ($number === '') {
                return [];
            }

            return [[
                ...$shared,
                'ticket_number' => preg_replace('/\D+/', '', $number) ?: $number,
                'ticket_status' => self::ticketStatusLabel(self::attr($etr, 'TicketStatus')),
                'coupons' => [],
            ]];
        }

        $grouped = [];
        foreach ($ticketNodes as $ticketNode) {
            if (! is_array($ticketNode)) {
                continue;
            }

            $number = self::attr($ticketNode, 'TicketNumber');
            if ($number === '') {
                continue;
            }

            $normalized = preg_replace('/\D+/', '', $number) ?: $number;
            if (! isset($grouped[$normalized])) {
                $grouped[$normalized] = [
                    ...$shared,
                    'ticket_number' => $normalized,
                    'ticket_status' => self::ticketStatusLabel(self::attr($ticketNode, 'TicketStatus')),
                    'coupons' => [],
                ];
            }

            foreach (self::asList($ticketNode['Coupon'] ?? null) as $coupon) {
                if (! is_array($coupon)) {
                    continue;
                }

                $grouped[$normalized]['coupons'][] = self::mapCoupon($coupon);
            }
        }

        return array_values($grouped);
    }

    /**
     * @param  array<string, mixed>  $coupon
     * @return array<string, mixed>
     */
    private static function mapCoupon(array $coupon): array
    {
        $carrier = strtoupper(self::attr($coupon, 'MarketingCarrier', ''));
        $flightNumber = trim(self::attr($coupon, 'MarketingFlightNumber', ''));
        $origin = strtoupper(self::attr($coupon, 'Origin', ''));
        $destination = strtoupper(self::attr($coupon, 'Destination', ''));

        return [
            'coupon_number' => self::attr($coupon, 'CouponNumber'),
            'flight' => $carrier . ($flightNumber !== '' ? ' ' . $flightNumber : ''),
            'route' => $origin !== '' && $destination !== '' ? "{$origin} → {$destination}" : '',
            'departure' => self::formatDateTime(self::attr($coupon, 'DepartureTime')),
            'booking_class' => strtoupper(self::attr($coupon, 'BookingClass', '')),
            'fare_basis' => self::attr($coupon, 'FareBasis'),
            'status' => self::couponStatusLabel(self::attr($coupon, 'Status')),
        ];
    }

    /**
     * @param  array<string, mixed>  $etr
     */
    private static function passengerNameFromEtr(array $etr): string
    {
        foreach (self::asList($etr['BookingTraveler'] ?? null) as $traveler) {
            if (! is_array($traveler)) {
                continue;
            }

            $nameNode = $traveler['BookingTravelerName'] ?? null;
            if (! is_array($nameNode)) {
                continue;
            }

            $attrs = $nameNode['@attributes'] ?? $nameNode;
            $first = trim((string) ($attrs['First'] ?? ''));
            $last = trim((string) ($attrs['Last'] ?? ''));
            $full = trim("{$first} {$last}");

            if ($full !== '') {
                return $full;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $etr
     * @return array{code: string, label: string}
     */
    private static function passengerTypeFromEtr(array $etr): array
    {
        $code = '';

        foreach (self::asList($etr['BookingTraveler'] ?? null) as $traveler) {
            if (! is_array($traveler)) {
                continue;
            }

            $code = self::attr($traveler, 'TravelerType');
            if ($code !== '') {
                break;
            }
        }

        $pricing = self::asList($etr['AirPricingInfo'] ?? null)[0] ?? null;
        if ($code === '' && is_array($pricing)) {
            foreach (self::asList($pricing['PassengerType'] ?? null) as $passengerType) {
                if (! is_array($passengerType)) {
                    continue;
                }

                $code = self::attr($passengerType, 'Code');
                if ($code !== '') {
                    break;
                }
            }

            if ($code === '') {
                foreach (self::asList($pricing['FareInfo'] ?? null) as $fareInfo) {
                    if (! is_array($fareInfo)) {
                        continue;
                    }

                    $code = self::attr($fareInfo, 'PassengerTypeCode');
                    if ($code !== '') {
                        break;
                    }
                }
            }
        }

        $code = strtoupper(trim($code));

        return [
            'code' => $code,
            'label' => $code !== '' ? self::passengerTypeLabel($code) : '',
        ];
    }

    public static function passengerTypeLabel(string $code): string
    {
        $normalized = strtoupper(trim($code));

        if (preg_match('/^CNN\d{2}$/', $normalized) === 1) {
            return 'Child';
        }

        return match ($normalized) {
            'ADT' => 'Adult',
            'CNN', 'C06', 'C11', 'CHD' => 'Child',
            'INF' => 'Infant',
            default => $normalized !== '' ? $normalized : 'Passenger',
        };
    }

    /**
     * @param  list<array<string, mixed>>  $tickets
     * @return list<array<string, mixed>>
     */
    private static function dedupeTickets(array $tickets): array
    {
        $seen = [];
        $unique = [];

        foreach ($tickets as $ticket) {
            $key = (string) ($ticket['ticket_number'] ?? '');
            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $ticket;
        }

        return $unique;
    }

    private static function ticketStatusLabel(string $code): string
    {
        return match (strtoupper(trim($code))) {
            'N' => 'Open (unused)',
            'O' => 'Open',
            'T' => 'Ticketed',
            'V' => 'Void',
            'R' => 'Refunded',
            'X' => 'Exchanged',
            default => $code !== '' ? strtoupper($code) : 'Issued',
        };
    }

    private static function couponStatusLabel(string $code): string
    {
        return match (strtoupper(trim($code))) {
            'O' => 'Open',
            'U' => 'Used',
            'E' => 'Exchanged',
            'R' => 'Refunded',
            'V' => 'Void',
            default => $code !== '' ? strtoupper($code) : '—',
        };
    }

    private static function moneyLabel(mixed $raw): ?string
    {
        $text = trim((string) $raw);
        if ($text === '') {
            return null;
        }

        if (preg_match('/^([A-Z]{3})([\d.,]+)$/i', $text, $matches)) {
            return strtoupper($matches[1]) . ' ' . number_format((float) str_replace(',', '', $matches[2]), 2);
        }

        return $text;
    }

    private static function formatDateTime(mixed $raw): ?string
    {
        $text = trim((string) $raw);
        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return $text;
        }
    }

    private static function boolLabel(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false
            ? 'Non-refundable'
            : 'Refundable';
    }

    private static function attr(mixed $node, string $name, mixed $default = ''): string
    {
        if (! is_array($node)) {
            return (string) $default;
        }

        if (isset($node['@attributes'][$name])) {
            return trim((string) $node['@attributes'][$name]);
        }

        return trim((string) ($node[$name] ?? $default));
    }

    private static function nodeText(mixed $node, string $name): string
    {
        if (! is_array($node)) {
            return '';
        }

        $value = $node[$name] ?? null;
        if (is_string($value)) {
            return trim($value);
        }

        if (is_array($value)) {
            return self::attr($value, 'Value') ?: self::attr($value, 'value');
        }

        return '';
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

        return array_is_list($value) ? $value : [$value];
    }
}
