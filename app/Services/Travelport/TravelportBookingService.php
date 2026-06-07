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
                throw new \RuntimeException($holdResponse['error'] ?? 'Travelport airHold failed.');
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
            } else {
                $bookingResponse = [
                    'raw' => $holdResponse['raw'] ?? '',
                    'travelport_universal_locator' => $locators['universal_locator'] ?? null,
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

            $itineraryData = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
            $platingCarrier = trim((string) (
                $itineraryData['validating_carrier']
                ?? data_get($itineraryData, 'legs.0.segments.0.carrier')
                ?? data_get($booking->booking_request, 'pricing_data.carrier')
                ?? ''
            ));

            if ($platingCarrier === '') {
                throw new \RuntimeException('Missing validating carrier for Travelport ticketing.');
            }

            $ticketResponse = $this->client->airTicket($locator, $platingCarrier);
            if (! ($ticketResponse['success'] ?? false)) {
                throw new \RuntimeException($ticketResponse['error'] ?? 'Travelport airTicket failed.');
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

            if ($booking->booking_status === 'hold') {
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
        $universalLocator = $this->resolveUniversalLocator($booking);
        if ($universalLocator === '') {
            throw new \RuntimeException('Missing Travelport universal record locator for cancel.');
        }

        $cancelResponse = $this->client->airCancel($universalLocator);
        if (! ($cancelResponse['success'] ?? false)) {
            throw new \RuntimeException($cancelResponse['error'] ?? 'Travelport cancel failed.');
        }

        return [
            'success' => true,
            'data' => is_array($cancelResponse['parsed'] ?? null) ? $cancelResponse['parsed'] : ['raw' => $cancelResponse['raw'] ?? ''],
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array{universal_locator: ?string, air_reservation_locator: ?string}
     */
    private function parseHoldLocators(array $response): array
    {
        $parsed = is_array($response['parsed'] ?? null) ? $response['parsed'] : [];
        $raw = (string) ($response['raw'] ?? '');

        $universal = data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.@attributes.LocatorCode')
            ?? data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.LocatorCode');

        $air = data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.AirReservation.@attributes.LocatorCode')
            ?? data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.AirReservation.LocatorCode');

        if (! $universal && preg_match('/UniversalRecord[^>]+LocatorCode="([^"]+)"/i', $raw, $m)) {
            $universal = $m[1];
        }
        if (! $air && preg_match('/<(?:[\w-]+:)?AirReservation[^>]+LocatorCode="([^"]+)"/i', $raw, $m)) {
            $air = $m[1];
        }

        return [
            'universal_locator' => is_string($universal) ? $universal : null,
            'air_reservation_locator' => is_string($air) ? $air : null,
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
            ?? data_get($booking->booking_response, 'UniversalRecord.LocatorCode');

        return is_string($parsed) ? trim($parsed) : '';
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
}
