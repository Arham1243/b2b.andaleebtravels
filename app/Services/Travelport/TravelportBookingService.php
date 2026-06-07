<?php

namespace App\Services\Travelport;

use App\Models\B2bFlightBooking;
use App\Support\Travelport\TravelportAirPriceParser;
use App\Support\Travelport\TravelportHoldPayloadBuilder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TravelportBookingService
{
    public function __construct(
        private readonly TravelportApiClient $client = new TravelportApiClient(),
    ) {}

    /**
     * Pre-payment price check via airPrice (Book Now checkout).
     *
     * @param  array<string, mixed>  $itineraryData
     * @param  array<string, mixed>  $searchParams
     * @return array{success: bool, error?: string, repriced_total?: float}
     */
    public function revalidateItinerary(array $itineraryData, array $searchParams): array
    {
        try {
            $segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($itineraryData);
            if ($segments === []) {
                return [
                    'success' => false,
                    'error' => 'No Travelport segments found on this itinerary.',
                ];
            }

            $passengerCounts = TravelportHoldPayloadBuilder::passengerCounts([
                'adults' => (int) ($searchParams['adults'] ?? 1),
                'children' => (int) ($searchParams['children'] ?? 0),
                'infants' => (int) ($searchParams['infants'] ?? 0),
            ]);

            $priceResponse = $this->client->airPrice($segments, $passengerCounts);
            if (! ($priceResponse['success'] ?? false)) {
                return [
                    'success' => false,
                    'error' => $priceResponse['error'] ?? 'Unable to revalidate Travelport fare.',
                ];
            }

            $requestedClass = strtoupper(trim((string) ($itineraryData['booking_code'] ?? '')));
            $pricingData = TravelportAirPriceParser::extract(
                (string) ($priceResponse['raw'] ?? ''),
                $requestedClass,
            );

            if (($pricingData['solution_key'] ?? '') === '') {
                return [
                    'success' => false,
                    'error' => 'Unable to parse Travelport pricing for this fare.',
                ];
            }

            $repricedTotal = $this->parseTravelportMoneyAmount((string) ($pricingData['total_price'] ?? ''));
            $expectedTotal = (float) ($itineraryData['totalPrice'] ?? 0);

            if ($repricedTotal === null) {
                return [
                    'success' => false,
                    'error' => 'Unable to read revalidated Travelport fare amount.',
                ];
            }

            if ($expectedTotal > 0 && abs($repricedTotal - $expectedTotal) > 0.02) {
                return [
                    'success' => false,
                    'error' => 'Fare price has changed. Please search again.',
                    'repriced_total' => $repricedTotal,
                ];
            }

            return [
                'success' => true,
                'repriced_total' => $repricedTotal,
            ];
        } catch (\Throwable $e) {
            Log::warning('Travelport revalidation failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Unable to revalidate Travelport fare. Please search again.',
            ];
        }
    }

    /**
     * @return array{success: bool, locator?: string, universal_locator?: string, error?: string, data?: array<string, mixed>}
     */
    public function createHold(B2bFlightBooking $booking): array
    {
        try {
            $itineraryData = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
            $passengersData = is_array($booking->passengers_data) ? $booking->passengers_data : [];

            $segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($itineraryData);
            if ($segments === []) {
                throw new \RuntimeException('No Travelport segments found on this itinerary.');
            }

            $passengerCounts = TravelportHoldPayloadBuilder::passengerCounts([
                'adults' => $booking->adults,
                'children' => $booking->children,
                'infants' => $booking->infants,
            ]);

            $priceResponse = $this->client->airPrice($segments, $passengerCounts);
            if (! ($priceResponse['success'] ?? false)) {
                throw new \RuntimeException($priceResponse['error'] ?? 'Travelport airPrice failed.');
            }

            $requestedClass = strtoupper(trim((string) ($itineraryData['booking_code'] ?? '')));
            $pricingData = TravelportAirPriceParser::extract(
                (string) ($priceResponse['raw'] ?? ''),
                $requestedClass,
            );

            if (($pricingData['solution_key'] ?? '') === '' || ($pricingData['segments'] ?? []) === []) {
                throw new \RuntimeException('Unable to parse Travelport pricing for hold.');
            }

            $travelers = TravelportHoldPayloadBuilder::buildTravelers($passengersData);
            if ($travelers === []) {
                throw new \RuntimeException('No passenger data for Travelport hold.');
            }

            $pricingData['passenger_types'] = TravelportHoldPayloadBuilder::passengerTypesFromTravelers($travelers);
            $pricingData = $this->alignPricingToSegments($pricingData);

            $holdResponse = $this->client->airHold($travelers, $pricingData);
            if (! ($holdResponse['success'] ?? false)) {
                $code = (string) ($holdResponse['error_code'] ?? '');
                $traceId = (string) ($holdResponse['trace_id'] ?? '');
                Log::error('Travelport airHold rejected by GDS', [
                    'booking_id' => $booking->id,
                    'error_code' => $code !== '' ? $code : null,
                    'trace_id' => $traceId !== '' ? $traceId : null,
                    'error' => $holdResponse['error'] ?? null,
                ]);
                throw new \RuntimeException($this->formatHoldGdsError($holdResponse));
            }

            $locators = $this->parseHoldLocators($holdResponse);
            if (($locators['air_reservation_locator'] ?? '') === '') {
                throw new \RuntimeException('Travelport hold succeeded but no air reservation locator was returned.');
            }

            $holdExpiresAt = $this->parseHoldExpiry($pricingData['latest_ticketing_time'] ?? null);
            $parsedHold = is_array($holdResponse['parsed'] ?? null) ? $holdResponse['parsed'] : [];
            $bookingResponse = $parsedHold['Body']['AirCreateReservationRsp'] ?? $parsedHold;
            if (is_array($bookingResponse)) {
                $bookingResponse['travelport_universal_locator'] = $locators['universal_locator'] ?? null;
                $bookingResponse['travelport_universal_version'] = $locators['universal_version'] ?? null;
            } else {
                $bookingResponse = [
                    'raw' => $holdResponse['raw'] ?? '',
                    'travelport_universal_locator' => $locators['universal_locator'] ?? null,
                    'travelport_universal_version' => $locators['universal_version'] ?? null,
                ];
            }

            $booking->update([
                'booking_request' => [
                    'air_price_segments' => $segments,
                    'passenger_counts' => $passengerCounts,
                    'pricing_data' => $pricingData,
                    'travelers' => $travelers,
                ],
                'booking_response' => $bookingResponse,
                'sabre_record_locator' => $locators['air_reservation_locator'],
                'hold_expires_at' => $holdExpiresAt,
            ]);

            return [
                'success' => true,
                'locator' => $locators['air_reservation_locator'],
                'universal_locator' => $locators['universal_locator'] ?? null,
                'data' => $bookingResponse,
            ];
        } catch (\Throwable $e) {
            Log::error('Travelport hold creation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public function issueTicket(B2bFlightBooking $booking): array
    {
        try {
            $locator = trim((string) ($booking->sabre_record_locator ?? ''));
            if ($locator === '') {
                throw new \RuntimeException('Missing Travelport air reservation locator for ticketing.');
            }

            $platingCarrier = $this->resolvePlatingCarrier($booking);
            if ($platingCarrier === '') {
                throw new \RuntimeException('Missing validating carrier for Travelport ticketing.');
            }

            $ticketResponse = $this->client->airTicket($locator, $platingCarrier);
            if (! ($ticketResponse['success'] ?? false)) {
                Log::error('Travelport airTicket failed', [
                    'booking_id' => $booking->id,
                    'locator' => $locator,
                    'plating_carrier' => $platingCarrier,
                    'error_code' => $ticketResponse['error_code'] ?? null,
                    'trace_id' => $ticketResponse['trace_id'] ?? null,
                    'error' => $ticketResponse['error'] ?? null,
                ]);
                throw new \RuntimeException($this->formatTravelportGdsError($ticketResponse, 'Travelport ticketing failed.'));
            }

            $parsed = is_array($ticketResponse['parsed'] ?? null) ? $ticketResponse['parsed'] : [];
            $ticketUpdate = [
                'ticket_request' => [
                    'air_reservation_locator' => $locator,
                    'plating_carrier' => $platingCarrier,
                ],
                'ticket_response' => $parsed['Body']['AirTicketingRsp'] ?? $parsed,
                'ticket_status' => 'issued',
            ];

            if (in_array($booking->booking_status, ['hold', 'pending'], true)) {
                $ticketUpdate['booking_status'] = 'confirmed';
            }

            $booking->update($ticketUpdate);
            $booking->reconcileStatusAfterHoldPayment();

            return [
                'success' => true,
                'data' => $ticketUpdate['ticket_response'],
            ];
        } catch (\Throwable $e) {
            Log::error('Travelport ticketing failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            $booking->update(['ticket_status' => 'failed']);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public function cancelHold(B2bFlightBooking $booking): array
    {
        try {
            $universalLocator = $this->resolveUniversalLocator($booking);
            if ($universalLocator === '') {
                throw new \RuntimeException('Missing Travelport universal record locator for cancel.');
            }

            $version = $this->resolveUniversalVersion($booking);
            $cancelResponse = $this->client->airCancel($universalLocator, $version);
            if (! ($cancelResponse['success'] ?? false)) {
                Log::error('Travelport airCancel failed', [
                    'booking_id' => $booking->id,
                    'universal_locator' => $universalLocator,
                    'version' => $version,
                    'error_code' => $cancelResponse['error_code'] ?? null,
                    'trace_id' => $cancelResponse['trace_id'] ?? null,
                    'error' => $cancelResponse['error'] ?? null,
                ]);
                throw new \RuntimeException($this->formatTravelportGdsError($cancelResponse, 'Travelport cancel failed.'));
            }

            return [
                'success' => true,
                'data' => is_array($cancelResponse['parsed'] ?? null) ? $cancelResponse['parsed'] : ['raw' => $cancelResponse['raw'] ?? ''],
            ];
        } catch (\Throwable $e) {
            Log::error('Travelport cancel hold failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array{universal_locator: ?string, air_reservation_locator: ?string, universal_version: ?string}
     */
    private function parseHoldLocators(array $response): array
    {
        $parsed = is_array($response['parsed'] ?? null) ? $response['parsed'] : [];
        $raw = (string) ($response['raw'] ?? '');

        $universal = data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.@attributes.LocatorCode')
            ?? data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.LocatorCode');

        $air = data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.AirReservation.@attributes.LocatorCode')
            ?? data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.AirReservation.LocatorCode');

        $version = data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.@attributes.Version')
            ?? data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.Version');

        if (! $universal && preg_match('/UniversalRecord[^>]+LocatorCode="([^"]+)"/i', $raw, $m)) {
            $universal = $m[1];
        }
        if (! $air && preg_match('/<(?:[\w-]+:)?AirReservation[^>]+LocatorCode="([^"]+)"/i', $raw, $m)) {
            $air = $m[1];
        }
        if (! $version && preg_match('/UniversalRecord[^>]+Version="([^"]+)"/i', $raw, $m)) {
            $version = $m[1];
        }

        return [
            'universal_locator' => is_string($universal) ? $universal : null,
            'air_reservation_locator' => is_string($air) ? $air : null,
            'universal_version' => is_string($version) ? $version : null,
        ];
    }

    private function parseHoldExpiry(?string $latestTicketingTime): Carbon
    {
        if ($latestTicketingTime) {
            try {
                return Carbon::parse($latestTicketingTime);
            } catch (\Throwable) {
                // fall through
            }
        }

        return now()->addHour();
    }

    private function resolveUniversalLocator(B2bFlightBooking $booking): string
    {
        $fromResponse = data_get($booking->booking_response, 'travelport_universal_locator');
        if (is_string($fromResponse) && trim($fromResponse) !== '') {
            return trim($fromResponse);
        }

        $parsed = data_get($booking->booking_response, 'UniversalRecord.@attributes.LocatorCode')
            ?? data_get($booking->booking_response, 'UniversalRecord.LocatorCode')
            ?? data_get($booking->booking_response, 'Body.AirCreateReservationRsp.UniversalRecord.@attributes.LocatorCode')
            ?? data_get($booking->booking_response, 'Body.AirCreateReservationRsp.UniversalRecord.LocatorCode');

        return is_string($parsed) ? trim($parsed) : '';
    }

    private function resolveUniversalVersion(B2bFlightBooking $booking): string
    {
        $fromResponse = data_get($booking->booking_response, 'travelport_universal_version');
        if (is_string($fromResponse) && trim($fromResponse) !== '') {
            return trim($fromResponse);
        }

        $parsed = data_get($booking->booking_response, 'UniversalRecord.@attributes.Version')
            ?? data_get($booking->booking_response, 'UniversalRecord.Version')
            ?? data_get($booking->booking_response, 'Body.AirCreateReservationRsp.UniversalRecord.@attributes.Version')
            ?? data_get($booking->booking_response, 'Body.AirCreateReservationRsp.UniversalRecord.Version');

        return is_string($parsed) && trim($parsed) !== '' ? trim($parsed) : '0';
    }

    private function resolvePlatingCarrier(B2bFlightBooking $booking): string
    {
        $itineraryData = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];

        return trim((string) (
            $itineraryData['validating_carrier']
            ?? data_get($itineraryData, 'legs.0.segments.0.carrier')
            ?? data_get($booking->booking_request, 'pricing_data.carrier')
            ?? ''
        ));
    }

    /**
     * @param  array<string, mixed>  $pricingData
     * @return array<string, mixed>
     */
    private function alignPricingToSegments(array $pricingData): array
    {
        $segmentKeys = array_values(array_filter(array_map(
            static fn ($seg) => is_array($seg) ? (string) ($seg['key'] ?? '') : '',
            $pricingData['segments'] ?? [],
        )));

        if ($segmentKeys === []) {
            return $pricingData;
        }

        $bookingInfos = [];
        foreach ($pricingData['booking_infos'] ?? [] as $bookingInfo) {
            if (! is_array($bookingInfo)) {
                continue;
            }
            $segmentRef = (string) ($bookingInfo['segment_ref'] ?? '');
            if ($segmentRef === '' || ! in_array($segmentRef, $segmentKeys, true)) {
                continue;
            }
            if (! isset($bookingInfos[$segmentRef])) {
                $bookingInfos[$segmentRef] = $bookingInfo;
            }
        }
        $pricingData['booking_infos'] = array_values($bookingInfos);

        $fareRefs = array_unique(array_filter(array_column($pricingData['booking_infos'], 'fare_info_ref')));
        if ($fareRefs !== []) {
            $pricingData['fare_infos'] = array_values(array_filter(
                $pricingData['fare_infos'] ?? [],
                static fn ($fareInfo) => is_array($fareInfo) && in_array((string) ($fareInfo['key'] ?? ''), $fareRefs, true),
            ));
        }

        return $pricingData;
    }

    /**
     * @param  array<string, mixed>  $holdResponse
     */
    private function formatHoldGdsError(array $holdResponse): string
    {
        return $this->formatTravelportGdsError($holdResponse, 'Travelport airHold failed.');
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function formatTravelportGdsError(array $response, string $fallback): string
    {
        $code = trim((string) ($response['error_code'] ?? ''));
        $traceId = trim((string) ($response['trace_id'] ?? ''));
        $raw = trim((string) ($response['error'] ?? $fallback));

        if ($code === '3515' || str_contains(strtolower($raw), 'primary host transaction')) {
            $msg = 'Travelport could not place the hold with the airline reservation system (GDS error 3515). '
                . 'Pricing succeeded but the host rejected the booking — this is usually a Travelport sandbox/PCC configuration issue, not a form error.';
            if ($traceId !== '') {
                $msg .= ' Reference trace: ' . $traceId . '.';
            }

            return $msg;
        }

        if ($traceId !== '') {
            return $raw . ' (Trace: ' . $traceId . ')';
        }

        return $raw !== '' ? $raw : $fallback;
    }

    private function parseTravelportMoneyAmount(string $raw): ?float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/([\d,]+(?:\.\d+)?)/', $raw, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }

        return null;
    }
}
