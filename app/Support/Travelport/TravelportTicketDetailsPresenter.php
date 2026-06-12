<?php

namespace App\Support\Travelport;

use App\Models\B2bFlightBooking;
use App\Support\FlightBookingAdminEticketPresenter;
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

        return self::attachUniversalRecordContactSsrs(self::dedupeTickets($tickets), $booking);
    }

    /**
     * Galileo hoists contact SSRs (CTCM/CTCE/CTCR) to the UniversalRecord level,
     * so they never appear inside the ETR BookingTraveler. Pull them from the
     * stored hold/booking response and attach them to the matching passenger.
     *
     * @param  list<array<string, mixed>>  $tickets
     * @return list<array<string, mixed>>
     */
    private static function attachUniversalRecordContactSsrs(array $tickets, B2bFlightBooking $booking): array
    {
        if ($tickets === []) {
            return $tickets;
        }

        foreach (self::universalRecordContactSsrs($booking) as $ssr) {
            $idx = self::ticketIndexForSsrNameSelect($tickets, (string) $ssr['free_text']);

            $alreadyShown = false;
            foreach ($tickets[$idx]['ssrs'] ?? [] as $existing) {
                if (($existing['type'] ?? '') === $ssr['type'] && ($existing['free_text'] ?? '') === $ssr['free_text']) {
                    $alreadyShown = true;
                    break;
                }
            }

            if (! $alreadyShown) {
                $tickets[$idx]['ssrs'][] = $ssr;
            }
        }

        return $tickets;
    }

    /**
     * @return list<array{type: string, status: string, free_text: string, carrier: string}>
     */
    private static function universalRecordContactSsrs(B2bFlightBooking $booking): array
    {
        $bookingResponse = is_array($booking->booking_response) ? $booking->booking_response : [];
        $raw = (string) ($bookingResponse['raw'] ?? '');
        if ($raw === '') {
            return [];
        }

        if (preg_match_all('/<(?:[\w-]+:)?SSR\b([^>]*?)\/?>/i', $raw, $matches) === false) {
            return [];
        }

        $items = [];
        $seen = [];
        foreach ($matches[1] as $attrString) {
            $attrs = [];
            preg_match_all('/([\w:-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/', $attrString, $attrMatches, PREG_SET_ORDER);
            foreach ($attrMatches as $attrMatch) {
                $attrs[$attrMatch[1]] = html_entity_decode($attrMatch[2] !== '' ? $attrMatch[2] : ($attrMatch[3] ?? ''), ENT_QUOTES | ENT_XML1);
            }

            $type = strtoupper(trim((string) ($attrs['Type'] ?? '')));
            if (! in_array($type, ['CTCM', 'CTCE', 'CTCR'], true)) {
                continue;
            }

            $freeText = trim((string) ($attrs['FreeText'] ?? ''));
            $dedupeKey = $type . '|' . $freeText;
            if ($freeText === '' || isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $items[] = [
                'type' => $type,
                'status' => trim((string) ($attrs['Status'] ?? '')),
                'free_text' => $freeText,
                'carrier' => strtoupper(trim((string) ($attrs['Carrier'] ?? ''))),
            ];
        }

        return $items;
    }

    /**
     * Match the SSR's trailing name select (e.g. "-1KHAN/MUHAMMAD A") to a
     * ticket's passenger name; defaults to the first (lead) ticket.
     *
     * @param  list<array<string, mixed>>  $tickets
     */
    private static function ticketIndexForSsrNameSelect(array $tickets, string $freeText): int
    {
        if (preg_match('/-\d([A-Z][A-Z ]*)\/([A-Z][A-Z ]*)$/i', trim($freeText), $m) !== 1) {
            return 0;
        }

        $last = strtoupper(trim($m[1]));
        $first = strtoupper(trim($m[2]));

        foreach ($tickets as $idx => $ticket) {
            $name = strtoupper((string) ($ticket['passenger_name'] ?? ''));
            if ($name !== '' && str_contains($name, $last) && str_contains($name, $first)) {
                return $idx;
            }
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $etr
     * @return list<array<string, mixed>>
     */
    private static function buildTicketsFromEtr(array $etr, B2bFlightBooking $booking): array
    {
        $traveler = self::primaryTravelerFromEtr($etr);
        $passengerName = self::passengerNameFromTraveler($traveler);
        $passengerType = self::passengerTypeFromEtr($etr);
        $pricing = self::asList($etr['AirPricingInfo'] ?? null)[0] ?? null;
        $pricingDetails = is_array($pricing) ? self::pricingDetailsFromNode($pricing, $etr) : [];

        $totalPrice = self::moneyLabel(
            self::attr($pricing, 'TotalPrice') ?: self::attr($etr, 'TotalPrice'),
        );
        $basePrice = self::moneyLabel(self::attr($pricing, 'BasePrice'));
        $taxes = self::moneyLabel(self::attr($pricing, 'Taxes') ?: self::attr($etr, 'Taxes'));

        $supplierLocator = self::asList($etr['SupplierLocator'] ?? null)[0] ?? null;
        $supplierCode = is_array($supplierLocator) ? self::attr($supplierLocator, 'SupplierCode') : '';
        $supplierPnr = is_array($supplierLocator) ? self::attr($supplierLocator, 'SupplierLocatorCode') : '';

        $providerPnr = strtoupper(trim(self::attr($etr, 'ProviderLocatorCode')));
        if ($providerPnr === '' && $booking->isTravelport()) {
            $providerPnr = strtoupper(trim($booking->travelportProviderLocator()));
        }

        $airReservationLocator = strtoupper(trim(self::nodeText($etr, 'AirReservationLocatorCode') ?? ''));
        if ($airReservationLocator === '' && $booking->isTravelport()) {
            $airReservationLocator = strtoupper(trim($booking->travelportAirReservationLocator()));
        }

        $shared = [
            'passenger_name' => $passengerName,
            'passenger_type' => $passengerType['label'],
            'passenger_type_code' => $passengerType['code'],
            'passenger_dob' => self::formatDate(self::attr($traveler, 'DOB')),
            'passenger_gender' => self::attr($traveler, 'Gender'),
            'pnr' => $providerPnr !== '' ? $providerPnr : ($booking->isTravelport() ? '' : trim((string) ($booking->sabre_record_locator ?? ''))),
            'gds_pnr' => $providerPnr,
            'air_reservation_locator' => $airReservationLocator,
            'supplier_code' => strtoupper($supplierCode),
            'supplier_pnr' => strtoupper($supplierPnr),
            'provider_code' => self::attr($etr, 'ProviderCode'),
            'iata_number' => self::attr($etr, 'IATANumber'),
            'pseudo_city_code' => self::attr($etr, 'PseudoCityCode'),
            'plating_carrier' => strtoupper(self::attr($etr, 'PlatingCarrier', '')),
            'issued_date' => self::formatDateTime(self::attr($etr, 'IssuedDate')),
            'refundable' => self::boolLabel(self::attr($etr, 'Refundable')),
            'exchangeable' => self::boolLabel(self::attr($etr, 'Exchangeable')),
            'total_price' => $totalPrice,
            'base_price' => $basePrice,
            'taxes' => $taxes,
            'fare_basis' => self::attr(self::asList($pricing['FareInfo'] ?? null)[0] ?? [], 'FareBasis'),
            'fare_calculation' => self::nodeText($etr, 'FareCalc') ?: ($pricingDetails['fare_calculation'] ?? null),
            'payment_amount' => self::moneyLabel(self::attr(self::asList($etr['Payment'] ?? null)[0] ?? [], 'Amount')),
            'pricing' => $pricingDetails,
            'ssrs' => self::ssrsFromEtr($etr),
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
            'not_valid_before' => self::formatDate(self::attr($coupon, 'NotValidBefore')),
            'not_valid_after' => self::formatDate(self::attr($coupon, 'NotValidAfter')),
            'stopover' => self::attr($coupon, 'StopoverCode'),
        ];
    }

    /**
     * @param  array<string, mixed>  $etr
     * @return array<string, mixed>
     */
    private static function primaryTravelerFromEtr(array $etr): array
    {
        foreach (self::asList($etr['BookingTraveler'] ?? null) as $traveler) {
            if (is_array($traveler)) {
                return $traveler;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $traveler
     */
    private static function passengerNameFromTraveler(array $traveler): string
    {
        if ($traveler === []) {
            return '';
        }

        $nameNode = $traveler['BookingTravelerName'] ?? null;
        if (! is_array($nameNode)) {
            return '';
        }

        $attrs = $nameNode['@attributes'] ?? $nameNode;
        $first = trim((string) ($attrs['First'] ?? ''));
        $last = trim((string) ($attrs['Last'] ?? ''));

        return trim("{$first} {$last}");
    }

    /**
     * @param  array<string, mixed>  $pricing
     * @param  array<string, mixed>  $etr
     * @return array<string, mixed>
     */
    private static function pricingDetailsFromNode(array $pricing, array $etr): array
    {
        $fareInfos = [];
        foreach (self::asList($pricing['FareInfo'] ?? null) as $fareInfo) {
            if (! is_array($fareInfo)) {
                continue;
            }

            $attrs = $fareInfo['@attributes'] ?? $fareInfo;
            $fareInfos[] = [
                'fare_basis' => (string) ($attrs['FareBasis'] ?? ''),
                'passenger_type_code' => (string) ($attrs['PassengerTypeCode'] ?? ''),
                'origin' => strtoupper((string) ($attrs['Origin'] ?? '')),
                'destination' => strtoupper((string) ($attrs['Destination'] ?? '')),
                'effective_date' => self::formatDateTime((string) ($attrs['EffectiveDate'] ?? '')),
                'not_valid_before' => self::formatDate((string) ($attrs['NotValidBefore'] ?? '')),
                'not_valid_after' => self::formatDate((string) ($attrs['NotValidAfter'] ?? '')),
                'endorsements' => FlightBookingAdminEticketPresenter::endorsementsFromFareInfo($fareInfo),
                'baggage' => FlightBookingAdminEticketPresenter::baggageFromFareInfo($fareInfo),
            ];
        }

        $bookingInfo = self::asList($pricing['BookingInfo'] ?? null)[0] ?? null;
        $bookingAttrs = is_array($bookingInfo) ? ($bookingInfo['@attributes'] ?? $bookingInfo) : [];

        return [
            'pricing_info_key' => self::attr($pricing, 'Key'),
            'pricing_method' => self::attr($pricing, 'PricingMethod'),
            'pricing_type' => self::attr($pricing, 'PricingType'),
            'e_ticketability' => self::attr($pricing, 'ETicketability'),
            'fare_calculation_ind' => self::attr($pricing, 'FareCalculationInd'),
            'latest_ticketing_time' => self::formatDateTime(self::attr($pricing, 'LatestTicketingTime')),
            'true_last_date_to_ticket' => self::formatDateTime(self::attr($pricing, 'TrueLastDateToTicket')),
            'approximate_base_price' => self::moneyLabel(self::attr($pricing, 'ApproximateBasePrice')),
            'approximate_total_price' => self::moneyLabel(self::attr($pricing, 'ApproximateTotalPrice')),
            'fare_calculation' => self::nodeText($pricing, 'FareCalc') ?: self::nodeText($etr, 'FareCalc'),
            'booking_code' => strtoupper(self::attr($bookingAttrs, 'BookingCode')),
            'cabin_class' => self::attr($bookingAttrs, 'CabinClass'),
            'tax_items' => FlightBookingAdminEticketPresenter::taxItemsFromPricingInfo($pricing),
            'fare_infos' => $fareInfos,
        ];
    }

    /**
     * @param  array<string, mixed>  $etr
     * @return list<array{type: string, status: string, free_text: string, carrier: string}>
     */
    private static function ssrsFromEtr(array $etr): array
    {
        $items = [];

        foreach (self::asList($etr['BookingTraveler'] ?? null) as $traveler) {
            if (! is_array($traveler)) {
                continue;
            }

            foreach (self::asList($traveler['SSR'] ?? null) as $ssr) {
                if (! is_array($ssr)) {
                    continue;
                }

                $type = strtoupper(self::attr($ssr, 'Type'));
                $freeText = self::attr($ssr, 'FreeText');
                if ($type === '' && $freeText === '') {
                    continue;
                }

                $items[] = [
                    'type' => $type,
                    'status' => self::attr($ssr, 'Status'),
                    'free_text' => $freeText,
                    'carrier' => strtoupper(self::attr($ssr, 'Carrier')),
                ];
            }
        }

        return $items;
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

    private static function formatDate(mixed $raw): ?string
    {
        $text = trim((string) $raw);
        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text)->format('d M Y');
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
