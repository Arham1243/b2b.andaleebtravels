<?php

namespace App\Support\Travelport;

use App\Models\B2bFlightBooking;
use App\Support\FlightBookingTicketResolver;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

final class TravelportCertificationPackageBuilder
{
    private const PROVIDER_CODE = '1G';

    private const BRANCH_CODE = 'P7250866';

    /**
     * @return array{folder: string, files: array<string, string>}
     */
    public static function build(B2bFlightBooking $booking): array
    {
        self::assertCanExport($booking);

        $folder = $booking->booking_number;
        $files = [];

        $holdRaw = self::responseRaw(is_array($booking->booking_response) ? $booking->booking_response : []);
        if ($holdRaw !== '') {
            $files['01_AirCreateReservationRsp.xml'] = self::prettyXml($holdRaw);
        }

        $ticketRaw = self::responseRaw(is_array($booking->ticket_response) ? $booking->ticket_response : []);
        if ($ticketRaw !== '') {
            $files['02_AirTicketingRsp.xml'] = self::prettyXml($ticketRaw);
        }

        $bookingRequest = is_array($booking->booking_request) ? $booking->booking_request : [];
        if ($bookingRequest !== []) {
            $files['03_AirCreateReservationReq_inputs.json'] = self::encodeJson(
                self::redactPii($bookingRequest),
            );
        }

        $ticketRequest = is_array($booking->ticket_request) ? $booking->ticket_request : [];
        if ($ticketRequest !== []) {
            $files['04_AirTicketingReq_inputs.json'] = self::encodeJson($ticketRequest);
        }

        $searchContext = self::buildSearchContext($booking);
        if ($searchContext !== null) {
            $files['05_search_context.json'] = self::encodeJson($searchContext);
        }

        $summary = self::buildSummary($booking, $holdRaw, $ticketRaw, $searchContext);
        $files['00_booking_summary.json'] = self::encodeJson($summary);
        $files['README.txt'] = self::buildReadme($booking, $summary);

        ksort($files);

        return [
            'folder' => $folder,
            'files' => $files,
        ];
    }

    public static function download(B2bFlightBooking $booking): StreamedResponse
    {
        $package = self::build($booking);
        $zipName = $package['folder'] . '-travelport-cert.zip';

        return response()->streamDownload(function () use ($package): void {
            $tmp = tempnam(sys_get_temp_dir(), 'travelport_cert_');
            if ($tmp === false) {
                return;
            }

            $zipPath = $tmp . '.zip';
            @unlink($tmp);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return;
            }

            foreach ($package['files'] as $name => $contents) {
                $zip->addFromString($package['folder'] . '/' . $name, $contents);
            }

            $zip->close();
            readfile($zipPath);
            @unlink($zipPath);
        }, $zipName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    public static function assertCanExport(B2bFlightBooking $booking): void
    {
        if (! $booking->isTravelport()) {
            abort(422, 'Travelport certification logs are only available for Travelport bookings.');
        }

        $bookingResponse = is_array($booking->booking_response) ? $booking->booking_response : [];
        if (self::responseRaw($bookingResponse) === '' && ($bookingResponse === [])) {
            abort(422, 'No Travelport hold response is stored for this booking yet.');
        }
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private static function responseRaw(array $response): string
    {
        $raw = trim((string) ($response['raw'] ?? ''));
        if ($raw !== '') {
            return $raw;
        }

        return '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildSearchContext(B2bFlightBooking $booking): ?array
    {
        $searchRequest = is_array($booking->search_request) ? $booking->search_request : [];
        $searchResponse = is_array($booking->search_response) ? $booking->search_response : [];

        if ($searchRequest === [] && $searchResponse === []) {
            return null;
        }

        $travelportRequest = $searchRequest['travelport'] ?? $searchRequest;
        if (! is_array($travelportRequest)) {
            $travelportRequest = [];
        }

        $lfsMeta = self::extractLowFareSearchMetadata($searchResponse);

        return [
            'note' => 'Parsed LowFareSearchRsp metadata only. Full LFS response XML was not persisted in application database.',
            'search_request' => [
                'travelport' => $travelportRequest,
            ],
            'low_fare_search_rsp' => $lfsMeta,
        ];
    }

    /**
     * @param  array<string, mixed>  $searchResponse
     * @return array{TraceId: ?string, TransactionId: ?string}
     */
    private static function extractLowFareSearchMetadata(array $searchResponse): array
    {
        foreach (['gds', 'ndc'] as $key) {
            $parsed = $searchResponse[$key] ?? null;
            if (! is_array($parsed)) {
                continue;
            }

            $traceId = self::traceIdFromParsed($parsed, 'LowFareSearchRsp');
            $transactionId = data_get($parsed, 'Body.LowFareSearchRsp.@attributes.TransactionId')
                ?? data_get($parsed, 'Body.LowFareSearchRsp.TransactionId');

            if ($traceId !== null || is_string($transactionId)) {
                return [
                    'TraceId' => $traceId,
                    'TransactionId' => is_string($transactionId) ? $transactionId : null,
                ];
            }
        }

        return [
            'TraceId' => null,
            'TransactionId' => null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $searchContext
     * @return array<string, mixed>
     */
    private static function buildSummary(
        B2bFlightBooking $booking,
        string $holdRaw,
        string $ticketRaw,
        ?array $searchContext,
    ): array {
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $firstSegment = data_get($itinerary, 'legs.0.segments.0', []);

        $carrier = trim((string) (
            $firstSegment['carrier']
            ?? data_get($booking->booking_request, 'pricing_data.carrier')
            ?? ''
        ));
        $flightNumber = trim((string) ($firstSegment['flight_number'] ?? ''));
        $flight = $carrier !== '' && $flightNumber !== ''
            ? strtoupper($carrier) . $flightNumber
            : null;

        $from = strtoupper(trim((string) ($booking->from_airport ?? '')));
        $to = strtoupper(trim((string) ($booking->to_airport ?? '')));
        $route = $from !== '' && $to !== '' ? "{$from}-{$to}" : null;

        return [
            'agency' => trim((string) config('services.travelport.card_holder', config('app.name', 'Andaleeb Travel Agency'))),
            'provider_code' => self::PROVIDER_CODE,
            'branch_code' => self::BRANCH_CODE,
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'universal_locator' => $booking->travelportUniversalLocator() ?: null,
            'air_reservation_locator' => trim((string) ($booking->sabre_record_locator ?? '')) ?: null,
            'provider_pnr' => self::extractXmlAttribute($holdRaw, 'ProviderReservationInfo', 'LocatorCode'),
            'supplier_pnr' => self::extractXmlAttribute($holdRaw, 'SupplierLocator', 'SupplierLocatorCode'),
            'route' => $route,
            'flight' => $flight,
            'departure_date' => $booking->departure_date?->format('Y-m-d'),
            'passengers' => [
                'ADT' => (int) $booking->adults,
                'CNN' => (int) $booking->children,
                'INF' => (int) $booking->infants,
            ],
            'ticket_numbers' => FlightBookingTicketResolver::forBooking($booking),
            'trace_ids' => [
                'AirCreateReservationRsp' => self::traceIdFromRaw($holdRaw, 'AirCreateReservationRsp'),
                'AirTicketingRsp' => self::traceIdFromRaw($ticketRaw, 'AirTicketingRsp'),
                'LowFareSearchRsp' => data_get($searchContext, 'low_fare_search_rsp.TraceId'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private static function buildReadme(B2bFlightBooking $booking, array $summary): string
    {
        $agency = (string) ($summary['agency'] ?? 'Andaleeb Travel Agency');
        $bookingNumber = (string) ($summary['booking_number'] ?? $booking->booking_number);
        $bookingId = (string) ($summary['booking_id'] ?? $booking->id);
        $date = Carbon::parse($booking->created_at)->format('Y-m-d');
        $universal = (string) ($summary['universal_locator'] ?? '—');
        $airReservation = (string) ($summary['air_reservation_locator'] ?? '—');
        $providerPnr = (string) ($summary['provider_pnr'] ?? '—');
        $supplierPnr = (string) ($summary['supplier_pnr'] ?? '—');
        $flight = (string) ($summary['flight'] ?? '');
        $supplierCode = strlen($flight) >= 2 ? strtoupper(substr($flight, 0, 2)) : 'Airline';

        return <<<TXT
Travelport uAPI Certification Sample Logs
=========================================
Agency: {$agency}
Booking: {$bookingNumber} (DB id {$bookingId})
Date: {$date}
Provider: 1G (Galileo)
Branch: P7250866

Transaction flow demonstrated
-----------------------------
1. LowFareSearch (search context metadata in 05_search_context.json)
2. AirPrice (pricing inputs embedded in 03_AirCreateReservationReq_inputs.json)
3. AirCreateReservation — response in 01_AirCreateReservationRsp.xml
4. AirTicketing — response in 02_AirTicketingRsp.xml

Files in this package
---------------------
00_booking_summary.json          Booking identifiers and TraceIds
01_AirCreateReservationRsp.xml   Hold/booking SUCCESS response (SOAP)
02_AirTicketingRsp.xml           Ticketing SUCCESS response (SOAP)
03_AirCreateReservationReq_inputs.json  Structured hold request inputs (PII redacted)
04_AirTicketingReq_inputs.json   Structured ticket request inputs
05_search_context.json           Search parameters + LFS TraceId (not full LFS XML)

Note to Travelport API team
---------------------------
Our application persists successful hold and ticket RESPONSE SOAP on the booking record.
REQUEST SOAP bodies for LowFareSearch, AirPrice, AirCreateReservation, and AirTicketing
are not currently stored in the database. Files 03, 04, and 05 represent the closest
available reconstruction of those requests. We can provide full request/response SOAP
pairs from a fresh test booking if required.

Locators
--------
Universal Record: {$universal}
Air Reservation:  {$airReservation}
GDS PNR:          {$providerPnr}
Supplier ({$supplierCode}):    {$supplierPnr}

TXT;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function redactPii(array $data): array
    {
        array_walk_recursive($data, function (&$value, $key): void {
            if (! is_string($value)) {
                return;
            }

            $normalizedKey = strtolower((string) $key);
            if (! in_array($normalizedKey, [
                'firstname',
                'lastname',
                'first_name',
                'last_name',
                'email',
                'phonenumber',
                'phone_number',
                'phone',
                'dob',
                'date_of_birth',
            ], true)) {
                return;
            }

            $value = match ($normalizedKey) {
                'email' => 'redacted@example.com',
                'phonenumber', 'phone_number', 'phone' => '00000000',
                default => 'REDACTED',
            };
        });

        return $data;
    }

    private static function prettyXml(string $xml): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if (! @$dom->loadXML($xml)) {
            return $xml;
        }

        return $dom->saveXML() ?: $xml;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function encodeJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private static function traceIdFromParsed(array $parsed, string $node): ?string
    {
        $traceId = data_get($parsed, "Body.{$node}.@attributes.TraceId")
            ?? data_get($parsed, "Body.{$node}.TraceId");

        return is_string($traceId) && trim($traceId) !== '' ? trim($traceId) : null;
    }

    private static function traceIdFromRaw(string $raw, string $node): ?string
    {
        if ($raw === '') {
            return null;
        }

        $pattern = '/<(?:[\w-]+:)?' . preg_quote($node, '/') . '[^>]+TraceId="([^"]+)"/i';
        if (preg_match($pattern, $raw, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private static function extractXmlAttribute(string $raw, string $element, string $attribute): ?string
    {
        if ($raw === '') {
            return null;
        }

        $pattern = '/<(?:[\w-]+:)?' . preg_quote($element, '/') . '[^>]+' . preg_quote($attribute, '/') . '="([^"]+)"/i';
        if (preg_match($pattern, $raw, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
