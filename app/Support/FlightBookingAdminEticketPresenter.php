<?php

namespace App\Support;

use App\Models\B2bFlightBooking;
use App\Support\Travelport\TravelportHoldPayloadBuilder;
use App\Support\Travelport\TravelportHoldPricingInfoParser;
use App\Support\Travelport\TravelportTicketDetailsPresenter;

final class FlightBookingAdminEticketPresenter
{
    /**
     * @param  array{source: ?string, error: ?string, tickets: list<array<string, mixed>>}  $ticketDetails
     * @param  array<string, mixed>  $fareBreakdown
     * @return array<string, mixed>
     */
    public static function present(B2bFlightBooking $booking, array $ticketDetails, array $fareBreakdown): array
    {
        $tickets = is_array($ticketDetails['tickets'] ?? null) ? $ticketDetails['tickets'] : [];
        $storedFares = self::storedFaresFromBooking($booking);
        $pnrReferences = self::resolvePnrReferences($booking, $tickets);

        return [
            'source' => $ticketDetails['source'] ?? null,
            'error' => $ticketDetails['error'] ?? null,
            'tickets' => $tickets,
            'stored_fares' => $storedFares,
            'fare_breakdown' => $fareBreakdown,
            'itinerary' => is_array($booking->itinerary_data) ? $booking->itinerary_data : [],
            'pnr_references' => $pnrReferences,
            'has_tickets' => $tickets !== [],
            'has_stored_fares' => $storedFares !== [],
            'has_content' => $tickets !== []
                || $storedFares !== []
                || ($fareBreakdown['has_breakdown'] ?? false)
                || ($fareBreakdown['has_pax_lines'] ?? false)
                || ($pnrReferences['gds_pnr'] ?? '') !== ''
                || ($pnrReferences['supplier_pnr'] ?? '') !== '',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $tickets
     * @return array{gds_pnr: string, supplier_pnr: string, supplier_code: string}
     */
    public static function resolvePnrReferences(B2bFlightBooking $booking, array $tickets = []): array
    {
        $gdsPnr = strtoupper(trim((string) ($booking->sabre_record_locator ?? '')));
        $supplierPnr = '';
        $supplierCode = '';

        foreach ($tickets as $ticket) {
            if (! is_array($ticket)) {
                continue;
            }

            if ($gdsPnr === '') {
                $gdsPnr = strtoupper(trim((string) ($ticket['gds_pnr'] ?? $ticket['pnr'] ?? '')));
            }

            if ($supplierPnr === '') {
                $supplierPnr = strtoupper(trim((string) ($ticket['supplier_pnr'] ?? '')));
            }

            if ($supplierCode === '') {
                $supplierCode = strtoupper(trim((string) ($ticket['supplier_code'] ?? '')));
            }
        }

        foreach ([
            is_array($booking->ticket_response) ? $booking->ticket_response : null,
            is_array($booking->booking_response) ? $booking->booking_response : null,
        ] as $source) {
            if (! is_array($source)) {
                continue;
            }

            if ($gdsPnr === '') {
                $gdsPnr = strtoupper(trim(TravelportHoldPricingInfoParser::extractProviderLocatorCode($source)));
            }

            if ($supplierPnr === '') {
                $supplierPnr = strtoupper(trim(self::extractSupplierLocatorCode($source)));
            }

            if ($supplierCode === '') {
                $supplierCode = strtoupper(trim(self::extractSupplierCode($source)));
            }
        }

        return [
            'gds_pnr' => $gdsPnr,
            'supplier_pnr' => $supplierPnr,
            'supplier_code' => $supplierCode,
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private static function extractSupplierLocatorCode(array $source): string
    {
        foreach ([
            'Body.AirTicketingRsp.ETR',
            'AirTicketingRsp.ETR',
            'ETR',
            'Body.AirCreateReservationRsp.UniversalRecord.AirReservation.SupplierLocator',
            'UniversalRecord.AirReservation.SupplierLocator',
            'AirReservation.SupplierLocator',
            'SupplierLocator',
        ] as $path) {
            foreach (self::asList(data_get($source, $path)) as $node) {
                if (! is_array($node)) {
                    continue;
                }

                $code = trim((string) (
                    data_get($node, '@attributes.SupplierLocatorCode')
                    ?? data_get($node, 'SupplierLocatorCode')
                    ?? data_get($node, '@attributes.LocatorCode')
                    ?? data_get($node, 'LocatorCode')
                    ?? ''
                ));

                if ($code !== '') {
                    return $code;
                }
            }
        }

        $raw = (string) ($source['raw'] ?? '');
        if ($raw !== '' && preg_match('/<(?:[\w-]+:)?SupplierLocator\b[^>]*\bSupplierLocatorCode="([^"]+)"/i', $raw, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private static function extractSupplierCode(array $source): string
    {
        foreach ([
            'Body.AirTicketingRsp.ETR',
            'AirTicketingRsp.ETR',
            'ETR',
            'SupplierLocator',
        ] as $path) {
            foreach (self::asList(data_get($source, $path)) as $node) {
                if (! is_array($node)) {
                    continue;
                }

                $code = trim((string) (
                    data_get($node, '@attributes.SupplierCode')
                    ?? data_get($node, 'SupplierCode')
                    ?? ''
                ));

                if ($code !== '') {
                    return strtoupper($code);
                }
            }
        }

        $raw = (string) ($source['raw'] ?? '');
        if ($raw !== '' && preg_match('/<(?:[\w-]+:)?SupplierLocator\b[^>]*\bSupplierCode="([^"]+)"/i', $raw, $matches)) {
            return strtoupper(trim($matches[1]));
        }

        return '';
    }

    /**
     * Stored fare rows from hold / ticket XML when e-ticket blocks are not available yet.
     *
     * @return list<array<string, mixed>>
     */
    public static function storedFaresFromBooking(B2bFlightBooking $booking): array
    {
        if (! $booking->isTravelport()) {
            return [];
        }

        $searchData = TravelportHoldPayloadBuilder::normalizeStoredSearchRequest(
            is_array($booking->search_request) ? $booking->search_request : [],
        );

        $sources = [
            is_array($booking->ticket_response) ? $booking->ticket_response : null,
            is_array($booking->booking_response) ? $booking->booking_response : null,
        ];

        $rows = [];

        foreach ($sources as $source) {
            if (! is_array($source)) {
                continue;
            }

            foreach (['Body.AirTicketingRsp.ETR', 'AirTicketingRsp.ETR', 'ETR'] as $path) {
                foreach (self::asList(data_get($source, $path)) as $etr) {
                    if (! is_array($etr)) {
                        continue;
                    }

                    foreach (self::asList($etr['AirPricingInfo'] ?? null) as $pricingInfo) {
                        if (! is_array($pricingInfo)) {
                            continue;
                        }

                        self::appendStoredFareRow($rows, $pricingInfo, $searchData);
                    }
                }
            }

            foreach ([
                'Body.AirCreateReservationRsp.UniversalRecord.AirReservation.AirPricingInfo',
                'UniversalRecord.AirReservation.AirPricingInfo',
                'AirReservation.AirPricingInfo',
                'AirPricingInfo',
            ] as $path) {
                foreach (self::asList(data_get($source, $path)) as $pricingInfo) {
                    if (! is_array($pricingInfo)) {
                        continue;
                    }

                    self::appendStoredFareRow($rows, $pricingInfo, $searchData);
                }
            }
        }

        return array_values($rows);
    }

    /**
     * @param  array<string, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $pricingInfo
     * @param  array<string, mixed>  $searchData
     */
    private static function appendStoredFareRow(array &$rows, array $pricingInfo, array $searchData): void
    {
        $row = self::mapStoredFareRow($pricingInfo, $searchData);
        if ($row === null) {
            return;
        }

        $key = (string) ($row['pricing_info_key'] ?? '') . '|' . (string) ($row['passenger_type_code'] ?? '');
        if ($key === '|' || isset($rows[$key])) {
            return;
        }

        $rows[$key] = $row;
    }

    /**
     * @param  array<string, mixed>  $pricingInfo
     * @param  array<string, mixed>  $searchData
     * @return array<string, mixed>|null
     */
    private static function mapStoredFareRow(array $pricingInfo, array $searchData): ?array
    {
        $attrs = $pricingInfo['@attributes'] ?? $pricingInfo;
        $base = self::parseMoney((string) ($attrs['BasePrice'] ?? ''));
        $tax = self::parseMoney((string) ($attrs['Taxes'] ?? ''));
        $total = self::parseMoney((string) ($attrs['TotalPrice'] ?? ''));

        if ($base === null && $tax === null && $total === null) {
            return null;
        }

        $passengerTypes = self::asList($pricingInfo['PassengerType'] ?? null);
        $typeCode = '';
        foreach ($passengerTypes as $passengerType) {
            if (! is_array($passengerType)) {
                continue;
            }
            $typeCode = strtoupper(trim((string) (($passengerType['@attributes']['Code'] ?? $passengerType['Code'] ?? ''))));
            if ($typeCode !== '') {
                break;
            }
        }

        if ($typeCode === '') {
            $fareInfo = self::asList($pricingInfo['FareInfo'] ?? null)[0] ?? null;
            if (is_array($fareInfo)) {
                $fareAttrs = $fareInfo['@attributes'] ?? $fareInfo;
                $typeCode = strtoupper(trim((string) ($fareAttrs['PassengerTypeCode'] ?? '')));
            }
        }

        $fareInfos = [];
        foreach (self::asList($pricingInfo['FareInfo'] ?? null) as $fareInfo) {
            if (! is_array($fareInfo)) {
                continue;
            }
            $fareAttrs = $fareInfo['@attributes'] ?? $fareInfo;
            $fareInfos[] = [
                'fare_basis' => (string) ($fareAttrs['FareBasis'] ?? ''),
                'passenger_type_code' => (string) ($fareAttrs['PassengerTypeCode'] ?? $typeCode),
                'origin' => strtoupper((string) ($fareAttrs['Origin'] ?? '')),
                'destination' => strtoupper((string) ($fareAttrs['Destination'] ?? '')),
                'effective_date' => (string) ($fareAttrs['EffectiveDate'] ?? ''),
                'not_valid_before' => (string) ($fareAttrs['NotValidBefore'] ?? ''),
                'not_valid_after' => (string) ($fareAttrs['NotValidAfter'] ?? ''),
                'endorsements' => self::endorsementsFromFareInfo($fareInfo),
                'baggage' => self::baggageFromFareInfo($fareInfo),
            ];
        }

        $bookingInfo = self::asList($pricingInfo['BookingInfo'] ?? null)[0] ?? null;
        $bookingAttrs = is_array($bookingInfo) ? ($bookingInfo['@attributes'] ?? $bookingInfo) : [];

        $paxCount = max(1, count($passengerTypes));

        return [
            'passenger_type' => $typeCode !== '' ? TravelportTicketDetailsPresenter::passengerTypeLabel($typeCode) : 'Passenger',
            'passenger_type_code' => $typeCode,
            'passenger_count' => $paxCount,
            'pricing_info_key' => (string) ($attrs['Key'] ?? ''),
            'pricing_method' => (string) ($attrs['PricingMethod'] ?? ''),
            'pricing_type' => (string) ($attrs['PricingType'] ?? ''),
            'e_ticketability' => (string) ($attrs['ETicketability'] ?? ''),
            'latest_ticketing_time' => self::formatDateTime((string) ($attrs['LatestTicketingTime'] ?? '')),
            'base_price' => self::moneyLabel($attrs['BasePrice'] ?? null),
            'taxes' => self::moneyLabel($attrs['Taxes'] ?? null),
            'total_price' => self::moneyLabel($attrs['TotalPrice'] ?? null),
            'approximate_base_price' => self::moneyLabel($attrs['ApproximateBasePrice'] ?? null),
            'approximate_total_price' => self::moneyLabel($attrs['ApproximateTotalPrice'] ?? null),
            'fare_calculation' => self::nodeText($pricingInfo, 'FareCalc'),
            'tax_items' => self::taxItemsFromPricingInfo($pricingInfo),
            'fare_infos' => $fareInfos,
            'booking_code' => strtoupper((string) ($bookingAttrs['BookingCode'] ?? '')),
            'cabin_class' => (string) ($bookingAttrs['CabinClass'] ?? ''),
            'search_context' => [
                'adults' => (int) ($searchData['adults'] ?? 0),
                'children' => (int) ($searchData['children'] ?? 0),
                'infants' => (int) ($searchData['infants'] ?? 0),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $fareInfo
     * @return list<string>
     */
    public static function endorsementsFromFareInfo(array $fareInfo): array
    {
        $values = [];
        foreach (self::asList($fareInfo['Endorsement'] ?? null) as $endorsement) {
            if (is_array($endorsement)) {
                $value = trim((string) (($endorsement['@attributes']['Value'] ?? $endorsement['Value'] ?? '')));
            } else {
                $value = trim((string) $endorsement);
            }
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $fareInfo
     */
    public static function baggageFromFareInfo(array $fareInfo): ?string
    {
        $allowance = $fareInfo['BaggageAllowance'] ?? null;
        if (! is_array($allowance)) {
            return null;
        }

        $maxWeight = $allowance['MaxWeight'] ?? null;
        if (is_array($maxWeight)) {
            $attrs = $maxWeight['@attributes'] ?? $maxWeight;
            $value = trim((string) ($attrs['Value'] ?? ''));
            $unit = trim((string) ($attrs['Unit'] ?? ''));
            if ($value !== '') {
                return trim($value . ' ' . $unit);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $pricingInfo
     * @return list<array{category: string, label: string, amount: string}>
     */
    public static function taxItemsFromPricingInfo(array $pricingInfo): array
    {
        $items = [];

        foreach (self::asList($pricingInfo['TaxInfo'] ?? null) as $taxInfo) {
            if (! is_array($taxInfo)) {
                continue;
            }

            $attrs = $taxInfo['@attributes'] ?? $taxInfo;
            $category = strtoupper(trim((string) ($attrs['Category'] ?? '')));
            $amount = self::moneyLabel($attrs['Amount'] ?? null);

            if ($category === '' && $amount === null) {
                continue;
            }

            $items[] = [
                'category' => $category,
                'label' => self::taxCategoryLabel($category),
                'amount' => $amount ?? '—',
            ];
        }

        return $items;
    }

    public static function taxCategoryLabel(string $category): string
    {
        return match (strtoupper(trim($category))) {
            'AE' => 'UAE Passenger Service Charge',
            'F6' => 'Passenger Facility Charge',
            'TP' => 'Passenger Security & Safety Fee',
            'ZR' => 'Advanced Passenger Information Fee',
            'YQ' => 'Carrier-imposed Fuel Surcharge',
            'YR' => 'Carrier-imposed Surcharge',
            'XT' => 'Combined Tax Total',
            'WO' => 'Airport Tax',
            'E3' => 'Security Tax',
            default => $category !== '' ? 'Tax ' . $category : 'Tax',
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

    /**
     * @return array{currency: string, amount: float}|null
     */
    private static function parseMoney(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^([A-Z]{3})([\d.,]+)$/i', $raw, $matches)) {
            return [
                'currency' => strtoupper($matches[1]),
                'amount' => (float) str_replace(',', '', $matches[2]),
            ];
        }

        return null;
    }

    private static function formatDateTime(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($raw)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return $raw;
        }
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function nodeText(array $node, string $name): ?string
    {
        $value = $node[$name] ?? null;
        if (is_string($value)) {
            $text = trim($value);

            return $text !== '' ? $text : null;
        }

        return null;
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
