<?php

namespace App\Services\Travelport;

use App\Models\B2bFlightBooking;
use App\Models\Config;
use App\Support\FlightBookingTicketResolver;
use App\Support\FlightPassengerFareLinesPresenter;
use App\Support\Travelport\TravelportAirPriceParser;
use App\Support\Travelport\TravelportAirPricePresenter;
use App\Support\Travelport\TravelportAirTicketingResult;
use App\Support\Travelport\TravelportHoldPayloadBuilder;
use App\Support\Travelport\TravelportHoldPricingInfoParser;
use App\Support\Travelport\TravelportHoldTravelerKeyResolver;
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
    /**
     * Refresh per-passenger fare lines via Air Price (display-safe; does not fail on total drift).
     *
     * @param  array<string, mixed>  $itineraryData
     * @param  array<string, mixed>  $searchParams
     * @param  array<string, mixed>  $passengersData
     * @return array{success: bool, error?: string, repriced_total?: float, itinerary_updates?: array<string, mixed>}
     */
    public function refreshFareBreakdown(
        array $itineraryData,
        array $searchParams,
        array $passengersData = [],
    ): array {
        return $this->runAirPriceFareRefresh($itineraryData, $searchParams, $passengersData, strictTotal: false);
    }

    /**
     * Re-run Air Price for an existing booking and persist corrected fare lines.
     *
     * @return array{success: bool, error?: string, message?: string}
     */
    public function refreshBookingFareBreakdown(B2bFlightBooking $booking): array
    {
        $itineraryData = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        if ($itineraryData === []) {
            return [
                'success' => false,
                'error' => 'No itinerary data stored on this booking.',
            ];
        }

        $searchRequest = is_array($booking->search_request) ? $booking->search_request : [];
        $passengersData = is_array($booking->passengers_data) ? $booking->passengers_data : [];
        $searchRequest = TravelportHoldPayloadBuilder::enrichSearchDataWithPassengerAges(
            $searchRequest,
            $passengersData,
        );

        $refresh = $this->refreshFareBreakdown($itineraryData, $searchRequest, $passengersData);
        if (! ($refresh['success'] ?? false)) {
            return [
                'success' => false,
                'error' => $refresh['error'] ?? 'Unable to refresh fare breakdown.',
            ];
        }

        $updates = is_array($refresh['itinerary_updates'] ?? null) ? $refresh['itinerary_updates'] : [];
        if ($updates === []) {
            return [
                'success' => true,
                'message' => 'Fare breakdown is already up to date.',
            ];
        }

        $itineraryData = array_merge($itineraryData, $updates);
        $bookingUpdate = ['itinerary_data' => $itineraryData];

        $repricedTotal = (float) ($updates['totalPrice'] ?? 0);
        $quotedTotal = (float) $booking->total_amount;
        if ($repricedTotal > 0 && ($quotedTotal <= 0 || abs($repricedTotal - $quotedTotal) <= 0.05)) {
            $bookingUpdate['total_amount'] = $repricedTotal;
            $bookingUpdate['original_amount'] = $repricedTotal;
        }

        $booking->update($bookingUpdate);

        return [
            'success' => true,
            'message' => 'Fare breakdown refreshed from Travelport Air Price.',
        ];
    }

    /**
     * @param  array<string, mixed>  $passengersData
     */
    public function revalidateItinerary(array $itineraryData, array $searchParams, array $passengersData = []): array
    {
        return $this->runAirPriceFareRefresh($itineraryData, $searchParams, $passengersData, strictTotal: true);
    }

    /**
     * @param  array<string, mixed>  $itineraryData
     * @param  array<string, mixed>  $searchParams
     * @param  array<string, mixed>  $passengersData
     * @return array{success: bool, error?: string, repriced_total?: float, itinerary_updates?: array<string, mixed>}
     */
    private function runAirPriceFareRefresh(
        array $itineraryData,
        array $searchParams,
        array $passengersData,
        bool $strictTotal,
    ): array {
        try {
            $airPrice = $this->requestAirPrice($itineraryData, $searchParams, $passengersData);
            if (! ($airPrice['success'] ?? false)) {
                return $airPrice;
            }

            $repricedTotal = (float) ($airPrice['repriced_total'] ?? 0);
            $expectedTotal = (float) ($itineraryData['totalPrice'] ?? 0);

            if ($strictTotal && $expectedTotal > 0 && $repricedTotal > 0 && abs($repricedTotal - $expectedTotal) > 0.02) {
                return [
                    'success' => false,
                    'error' => 'Fare price has changed. Please search again.',
                    'repriced_total' => $repricedTotal,
                ];
            }

            $itineraryUpdates = is_array($airPrice['itinerary_updates'] ?? null)
                ? $airPrice['itinerary_updates']
                : [];

            if (! $strictTotal && ($itineraryUpdates['totalPrice'] ?? null) !== null) {
                $oldTotal = (float) ($itineraryData['totalPrice'] ?? 0);
                $newTotal = (float) $itineraryUpdates['totalPrice'];
                if ($oldTotal > 0 && abs($newTotal - $oldTotal) > 0.05) {
                    unset($itineraryUpdates['totalPrice']);
                }
            }

            return [
                'success' => true,
                'repriced_total' => $repricedTotal,
                'itinerary_updates' => $itineraryUpdates,
            ];
        } catch (\Throwable $e) {
            Log::warning('Travelport fare refresh failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $strictTotal
                    ? 'Unable to revalidate Travelport fare. Please search again.'
                    : 'Unable to refresh Travelport fare breakdown.',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $itineraryData
     * @param  array<string, mixed>  $searchParams
     * @param  array<string, mixed>  $passengersData
     * @return array{success: bool, error?: string, repriced_total?: float, itinerary_updates?: array<string, mixed>}
     */
    private function requestAirPrice(
        array $itineraryData,
        array $searchParams,
        array $passengersData = [],
    ): array {
        $segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($itineraryData);
        if ($segments === []) {
            return [
                'success' => false,
                'error' => 'No Travelport segments found on this itinerary.',
            ];
        }

        $searchParams = TravelportHoldPayloadBuilder::normalizeStoredSearchRequest($searchParams);

        if ($passengersData !== []) {
            $searchParams = TravelportHoldPayloadBuilder::enrichSearchDataWithPassengerAges(
                $searchParams,
                $passengersData,
            );
        }

        $passengerCounts = TravelportHoldPayloadBuilder::passengerCounts([
            'adults' => (int) ($searchParams['adults'] ?? 1),
            'children' => (int) ($searchParams['children'] ?? 0),
            'infants' => (int) ($searchParams['infants'] ?? 0),
        ]);

        $travelers = [];
        if ($passengersData !== []) {
            try {
                $travelers = TravelportHoldPayloadBuilder::buildTravelers($passengersData, $searchParams);
            } catch (\Throwable) {
                $travelers = [];
            }
        }

        $priceResponse = $this->client->airPrice($segments, $passengerCounts, $searchParams, $travelers);
        if (! ($priceResponse['success'] ?? false)) {
            return [
                'success' => false,
                'error' => $priceResponse['error'] ?? 'Unable to price Travelport fare.',
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

        if (($pricingData['segments'] ?? []) === [] && $requestedClass !== '') {
            return [
                'success' => false,
                'error' => 'Booking class ' . $requestedClass . ' is no longer available on this flight. Please search again.',
            ];
        }

        $repricedTotal = $this->parseTravelportMoneyAmount((string) ($pricingData['total_price'] ?? ''));
        if ($repricedTotal === null) {
            return [
                'success' => false,
                'error' => 'Unable to read Travelport fare amount.',
            ];
        }

        $itineraryUpdates = $this->extractItineraryFareFromAirPrice(
            $priceResponse,
            $searchParams,
            $itineraryData,
        );

        return [
            'success' => true,
            'repriced_total' => $repricedTotal,
            'itinerary_updates' => $itineraryUpdates,
        ];
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

            $searchRequest = is_array($booking->search_request) ? $booking->search_request : [];
            $searchRequest = TravelportHoldPayloadBuilder::enrichSearchDataWithPassengerAges(
                $searchRequest,
                $passengersData,
            );

            $travelers = TravelportHoldPayloadBuilder::buildTravelers($passengersData, $searchRequest);
            if ($travelers === []) {
                throw new \RuntimeException('No passenger data for Travelport hold.');
            }

            $holdResponse = null;
            $pricingData = [];

            for ($attempt = 1; $attempt <= 2; $attempt++) {
                [$priceResponse, $pricingData, $itineraryData] = $this->buildFreshHoldPricing(
                    $booking,
                    $itineraryData,
                    $searchRequest,
                    $segments,
                    $passengerCounts,
                    $travelers,
                );

                $holdResponse = $this->client->airHold($travelers, $pricingData);
                if ($holdResponse['success'] ?? false) {
                    break;
                }

                $code = (string) ($holdResponse['error_code'] ?? '');
                if ($code !== '3000' || $attempt === 2) {
                    break;
                }

                Log::warning('Travelport airHold returned GDS 3000, retrying once with fresh airPrice', [
                    'booking_id' => $booking->id,
                    'trace_id' => $holdResponse['trace_id'] ?? null,
                    'error_details' => $holdResponse['error_details'] ?? null,
                ]);
            }

            if (! ($holdResponse['success'] ?? false)) {
                $code = (string) ($holdResponse['error_code'] ?? '');
                $traceId = (string) ($holdResponse['trace_id'] ?? '');
                Log::error('Travelport airHold rejected by GDS', [
                    'booking_id' => $booking->id,
                    'error_code' => $code !== '' ? $code : null,
                    'trace_id' => $traceId !== '' ? $traceId : null,
                    'error' => $holdResponse['error'] ?? null,
                    'error_details' => $holdResponse['error_details'] ?? null,
                ]);
                throw new \RuntimeException($this->formatHoldGdsError($holdResponse));
            }

            $locators = $this->parseHoldLocators($holdResponse);
            if (($locators['air_reservation_locator'] ?? '') === '') {
                throw new \RuntimeException('Travelport hold succeeded but no air reservation locator was returned.');
            }
            if (($locators['universal_locator'] ?? '') === '') {
                throw new \RuntimeException('Travelport hold succeeded but no universal record locator was returned.');
            }

            $holdExpiresAt = $this->parseHoldExpiry($pricingData['latest_ticketing_time'] ?? null);
            $holdPricingInfoKeys = TravelportHoldPricingInfoParser::extractReservationKeys($holdResponse);
            if ($holdPricingInfoKeys === []) {
                $retrieveAfterHold = $this->retrieveUniversalRecordForHold(
                    (string) ($locators['universal_locator'] ?? ''),
                );
                if ($retrieveAfterHold['success'] ?? false) {
                    $holdPricingInfoKeys = TravelportHoldPricingInfoParser::extractReservationKeys($retrieveAfterHold);
                    if ($holdPricingInfoKeys !== []) {
                        $holdResponse = $retrieveAfterHold;
                        Log::info('Travelport hold fare keys resolved from universal record retrieve', [
                            'booking_id' => $booking->id,
                            'universal_locator' => $locators['universal_locator'] ?? null,
                            'air_pricing_info_keys' => $holdPricingInfoKeys,
                        ]);
                    } else {
                        $holdResponse = $retrieveAfterHold;
                    }
                }
            }

            if ($holdPricingInfoKeys === []) {
                Log::warning('Travelport hold succeeded but no reservation AirPricingInfo keys were extracted; attempting fare storage retry', [
                    'booking_id' => $booking->id,
                    'air_reservation_locator' => $locators['air_reservation_locator'] ?? null,
                    'raw_len' => strlen((string) ($holdResponse['raw'] ?? '')),
                ]);

                $holdPricingInfoKeys = $this->storeFaresAndExtractKeys(
                    $booking->id,
                    (string) ($locators['universal_locator'] ?? ''),
                    (string) ($locators['universal_version'] ?? '0'),
                    $pricingData,
                    $travelers,
                    $holdResponse,
                );

                if ($holdPricingInfoKeys !== []) {
                    $retrieve = $this->client->universalRecordRetrieve((string) ($locators['universal_locator'] ?? ''));
                    if ($retrieve['success'] ?? false) {
                        $holdResponse = $retrieve;
                    }
                }
            }

            if ($holdPricingInfoKeys === []) {
                throw new \RuntimeException(
                    'Travelport hold completed without stored fares on PNR '
                    . trim((string) ($locators['air_reservation_locator'] ?? ''))
                    . '. Release the hold and try again.',
                );
            }
            $parsedHold = is_array($holdResponse['parsed'] ?? null) ? $holdResponse['parsed'] : [];
            $bookingResponse = $parsedHold['Body']['AirCreateReservationRsp'] ?? $parsedHold;
            if (is_array($bookingResponse)) {
                $bookingResponse['travelport_universal_locator'] = $locators['universal_locator'] ?? null;
                $bookingResponse['travelport_universal_version'] = $locators['universal_version'] ?? null;
                $bookingResponse['travelport_provider_locator'] = $locators['provider_locator'] ?? null;
                $bookingResponse['raw'] = (string) ($holdResponse['raw'] ?? $bookingResponse['raw'] ?? '');
            } else {
                $bookingResponse = [
                    'raw' => $holdResponse['raw'] ?? '',
                    'travelport_universal_locator' => $locators['universal_locator'] ?? null,
                    'travelport_universal_version' => $locators['universal_version'] ?? null,
                    'travelport_provider_locator' => $locators['provider_locator'] ?? null,
                ];
            }

            $bookingUpdate = [
                'booking_request' => [
                    'air_price_segments' => $segments,
                    'passenger_counts' => $passengerCounts,
                    'pricing_data' => $pricingData,
                    'travelers' => $travelers,
                    'hold_air_pricing_info_keys' => $holdPricingInfoKeys,
                    'travelport_provider_locator' => $locators['provider_locator'] ?? null,
                ],
                'booking_response' => $bookingResponse,
                'sabre_record_locator' => $locators['air_reservation_locator'],
                'hold_expires_at' => $holdExpiresAt,
                'itinerary_data' => $itineraryData,
            ];

            $repricedTotal = (float) ($itineraryData['totalPrice'] ?? 0);
            $quotedTotal = (float) $booking->total_amount;
            if ($repricedTotal > 0) {
                $bookingUpdate['total_amount'] = $repricedTotal;
                $bookingUpdate['original_amount'] = $repricedTotal;
            }

            $booking->update($bookingUpdate);

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

            $airPricingInfoKeys = $this->resolveAirPricingInfoKeys($booking);
            if ($airPricingInfoKeys === []) {
                throw new \RuntimeException(
                    'Unable to resolve Travelport stored fare keys for PNR '
                    . trim((string) ($booking->sabre_record_locator ?? ''))
                    . '. Release the hold and rebook this itinerary.',
                );
            }

            $gdsCommissionPercentage = $this->resolveGdsCommissionPercentage();

            $ticketResponse = $this->client->airTicket(
                $locator,
                $airPricingInfoKeys,
                $platingCarrier,
                $gdsCommissionPercentage,
            );

            $recovered = null;
            if (! ($ticketResponse['success'] ?? false)) {
                $recovered = $this->attemptRecoverTicketsAfterTicketingFailure(
                    $booking,
                    $ticketResponse,
                    $locator,
                    $airPricingInfoKeys,
                    $platingCarrier,
                    $gdsCommissionPercentage,
                );
            }

            if (! ($ticketResponse['success'] ?? false) && $recovered === null) {
                Log::error('Travelport airTicket failed', [
                    'booking_id' => $booking->id,
                    'locator' => $locator,
                    'plating_carrier' => $platingCarrier,
                    'air_pricing_info_keys' => $airPricingInfoKeys,
                    'gds_commission_percentage' => $gdsCommissionPercentage,
                    'error_code' => $ticketResponse['error_code'] ?? null,
                    'trace_id' => $ticketResponse['trace_id'] ?? null,
                    'error' => $ticketResponse['error'] ?? null,
                ]);
                throw new \RuntimeException($this->formatTravelportGdsError($ticketResponse, 'Travelport ticketing failed.'));
            }

            if ($recovered !== null) {
                Log::warning('Travelport ticketing recovered after host error by retrieving booking file', [
                    'booking_id' => $booking->id,
                    'locator' => $locator,
                    'recovery_source' => $recovered['source'] ?? null,
                    'ticket_numbers' => $recovered['ticket_numbers'] ?? [],
                    'error_code' => $ticketResponse['error_code'] ?? null,
                ]);
            }

            $parsed = is_array($ticketResponse['parsed'] ?? null) ? $ticketResponse['parsed'] : [];
            $ticketingRsp = $parsed['Body']['AirTicketingRsp'] ?? $parsed;
            $storedResponse = $recovered['ticket_response'] ?? (is_array($ticketingRsp) ? $ticketingRsp : []);
            if (is_string($ticketResponse['raw'] ?? null) && ($ticketResponse['raw'] ?? '') !== '' && ! isset($storedResponse['raw'])) {
                $storedResponse['raw'] = $ticketResponse['raw'];
            }

            $ticketNumbers = $recovered['ticket_numbers'] ?? FlightBookingTicketResolver::fromResponse($storedResponse);

            if (! TravelportAirTicketingResult::isSuccessful($storedResponse, $ticketNumbers)) {
                $error = TravelportAirTicketingResult::hasFailure($storedResponse)
                    ? TravelportAirTicketingResult::failureMessage($storedResponse)
                    : 'Travelport ticketing completed without ticket numbers.';

                throw new \RuntimeException($error);
            }

            $ticketUpdate = [
                'ticket_request' => [
                    'air_reservation_locator' => $locator,
                    'air_pricing_info_keys' => $airPricingInfoKeys,
                    'air_pricing_info_key' => $airPricingInfoKeys[0] ?? '',
                    'plating_carrier' => $platingCarrier,
                    'gds_commission_percentage' => $gdsCommissionPercentage,
                ],
                'ticket_response' => $storedResponse,
                'ticket_numbers' => $ticketNumbers,
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
            $universalLocator = $booking->travelportUniversalLocator();
            if ($universalLocator === '') {
                throw new \RuntimeException('Missing Travelport universal record locator for cancel.');
            }

            $version = $booking->travelportUniversalVersion();
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

        $provider = data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.ProviderReservationInfo.@attributes.LocatorCode')
            ?? data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.ProviderReservationInfo.LocatorCode')
            ?? data_get($parsed, 'UniversalRecord.ProviderReservationInfo.@attributes.LocatorCode')
            ?? data_get($parsed, 'UniversalRecord.ProviderReservationInfo.LocatorCode');

        if (! $provider && preg_match('/<(?:[\w-]+:)?ProviderReservationInfo\b[^>]*\bLocatorCode="([^"]+)"/i', $raw, $m)) {
            $provider = $m[1];
        }

        return [
            'universal_locator' => is_string($universal) ? $universal : null,
            'air_reservation_locator' => is_string($air) ? $air : null,
            'universal_version' => is_string($version) ? $version : null,
            'provider_locator' => is_string($provider) ? $provider : null,
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

    private function resolveGdsCommissionPercentage(): float
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $config = Config::pluck('config_value', 'config_key')->toArray();
        $cached = max(0.0, (float) ($config['TRAVELPORT_GDS_COMMISSION_PERCENTAGE'] ?? 0));

        return $cached;
    }

    /**
     * @return list<string>
     */
    private function resolveAirPricingInfoKeys(B2bFlightBooking $booking): array
    {
        $bookingRequest = is_array($booking->booking_request) ? $booking->booking_request : [];
        $bookingResponse = is_array($booking->booking_response) ? $booking->booking_response : [];

        $keys = TravelportHoldPricingInfoParser::resolveKeysForTicketing($bookingRequest, $bookingResponse);
        if ($keys !== []) {
            return $keys;
        }

        $universalLocator = $booking->travelportUniversalLocator();
        if ($universalLocator !== '') {
            $keys = $this->keysFromUniversalRecordRetrieve(
                $this->client->universalRecordRetrieve($universalLocator),
                $bookingRequest,
                $booking->id,
                'universal_locator',
                $universalLocator,
            );
            if ($keys !== []) {
                $this->persistHoldPricingInfoKeys($booking, $keys);

                return $keys;
            }
        }

        $providerLocator = trim((string) (
            data_get($bookingResponse, 'travelport_provider_locator')
            ?? data_get($bookingRequest, 'travelport_provider_locator')
            ?? TravelportHoldPricingInfoParser::extractProviderLocatorCode($bookingResponse)
            ?? ''
        ));

        $leadLastName = trim((string) (
            data_get($booking->passengers_data, 'lead.last_name')
            ?? data_get($booking->passengers_data, 'passengers.0.last_name')
            ?? data_get($bookingRequest, 'travelers.0.last_name')
            ?? ''
        ));
        if ($providerLocator !== '' && $leadLastName !== '') {
            $keys = $this->keysFromUniversalRecordRetrieve(
                $this->client->universalRecordRetrieveByProvider($providerLocator, $leadLastName),
                $bookingRequest,
                $booking->id,
                'provider_locator',
                $providerLocator,
            );
            if ($keys !== []) {
                $this->persistHoldPricingInfoKeys($booking, $keys);

                return $keys;
            }
        }

        $keys = $this->attemptStoreFaresOnBooking($booking);
        if ($keys !== []) {
            $this->persistHoldPricingInfoKeys($booking, $keys);

            return $keys;
        }

        Log::warning('Travelport fare keys could not be resolved from booking data or live retrieve', [
            'booking_id' => $booking->id,
            'air_reservation_locator' => $booking->sabre_record_locator,
            'universal_locator' => $universalLocator !== '' ? $universalLocator : null,
            'provider_locator' => $providerLocator !== '' ? $providerLocator : null,
            'has_booking_response_raw' => is_string($bookingResponse['raw'] ?? null) && ($bookingResponse['raw'] ?? '') !== '',
        ]);

        return [];
    }

    /**
     * @param  array<string, mixed>  $retrieveResponse
     * @return list<string>
     */
    private function keysFromUniversalRecordRetrieve(
        array $retrieveResponse,
        array $bookingRequest,
        int $bookingId,
        string $lookupType,
        string $lookupValue,
    ): array {
        if (! ($retrieveResponse['success'] ?? false)) {
            Log::warning('Travelport universal record retrieve failed while resolving fare keys', [
                'booking_id' => $bookingId,
                'lookup_type' => $lookupType,
                'lookup_value' => $lookupValue,
                'error' => $retrieveResponse['error'] ?? null,
            ]);

            return [];
        }

        $keys = TravelportHoldPricingInfoParser::extractKeysFromRetrieveResponse(
            [
                'raw' => (string) ($retrieveResponse['raw'] ?? ''),
                'parsed' => is_array($retrieveResponse['parsed'] ?? null) ? $retrieveResponse['parsed'] : [],
            ],
            $bookingRequest,
        );

        if ($keys === []) {
            $rawLen = strlen((string) ($retrieveResponse['raw'] ?? ''));
            Log::warning('Travelport universal record retrieve returned no reservation fare keys', [
                'booking_id' => $bookingId,
                'lookup_type' => $lookupType,
                'lookup_value' => $lookupValue,
                'raw_len' => $rawLen,
                'has_stored_fare_in_raw' => str_contains((string) ($retrieveResponse['raw'] ?? ''), 'PricingType="StoredFare"'),
                'has_air_reservation_in_raw' => (bool) preg_match('/<(?:[\w-]+:)?AirReservation\b/i', (string) ($retrieveResponse['raw'] ?? '')),
            ]);
        }

        Log::info('Travelport fare keys resolved from universal record retrieve', [
            'booking_id' => $bookingId,
            'lookup_type' => $lookupType,
            'lookup_value' => $lookupValue,
            'air_pricing_info_keys' => $keys,
        ]);

        return $keys;
    }

    /**
     * @return array<string, mixed>
     */
    private function holdResponsePayloadFromBooking(B2bFlightBooking $booking): array
    {
        $bookingResponse = is_array($booking->booking_response) ? $booking->booking_response : [];

        return [
            'parsed' => [
                'Body' => [
                    'AirCreateReservationRsp' => $bookingResponse,
                ],
            ],
            'raw' => (string) ($bookingResponse['raw'] ?? ''),
        ];
    }

    /**
     * @param  list<string>  $keys
     */
    private function persistHoldPricingInfoKeys(B2bFlightBooking $booking, array $keys): void
    {
        if ($keys === []) {
            return;
        }

        $bookingRequest = is_array($booking->booking_request) ? $booking->booking_request : [];
        $existing = is_array($bookingRequest['hold_air_pricing_info_keys'] ?? null)
            ? $bookingRequest['hold_air_pricing_info_keys']
            : [];

        if ($existing === $keys) {
            return;
        }

        $bookingRequest['hold_air_pricing_info_keys'] = $keys;
        $booking->update(['booking_request' => $bookingRequest]);
        $booking->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function retrieveUniversalRecordForHold(string $universalLocator, int $maxAttempts = 3): array
    {
        $universalLocator = trim($universalLocator);
        if ($universalLocator === '') {
            return ['success' => false];
        }

        $lastResponse = ['success' => false];
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            if ($attempt > 0) {
                usleep(750000);
            }

            $lastResponse = $this->client->universalRecordRetrieve($universalLocator);
            if (! ($lastResponse['success'] ?? false)) {
                continue;
            }

            $fareKeys = TravelportHoldPricingInfoParser::extractReservationKeys($lastResponse);
            $travelerKeys = TravelportHoldTravelerKeyResolver::extractBookingTravelersFromHold($lastResponse);
            if ($fareKeys !== [] || $travelerKeys !== []) {
                return $lastResponse;
            }
        }

        return $lastResponse;
    }

    /**
     * @param  list<array<string, mixed>>  $travelers
     * @param  array<string, mixed>  $pricingData
     * @param  array<string, mixed>  $holdResponse
     * @return list<string>
     */
    private function storeFaresAndExtractKeys(
        int $bookingId,
        string $universalLocator,
        string $version,
        array $pricingData,
        array $travelers,
        array $holdResponse = [],
    ): array {
        if ($universalLocator === '') {
            return [];
        }

        $keySourceResponse = $holdResponse;
        $retrieveResponse = $this->retrieveUniversalRecordForHold($universalLocator);
        if ($retrieveResponse['success'] ?? false) {
            $keySourceResponse = $retrieveResponse;
            $retrievedVersion = data_get($retrieveResponse, 'parsed.Body.UniversalRecordRetrieveRsp.UniversalRecord.@attributes.Version')
                ?? data_get($retrieveResponse, 'parsed.Body.UniversalRecordRetrieveRsp.UniversalRecord.Version');
            if (is_string($retrievedVersion) && trim($retrievedVersion) !== '') {
                $version = trim($retrievedVersion);
            }
        } else {
            Log::warning('Travelport universal record retrieve failed before fare storage', [
                'booking_id' => $bookingId,
                'universal_locator' => $universalLocator,
                'error' => $retrieveResponse['error'] ?? null,
            ]);
        }

        $keyMap = TravelportHoldTravelerKeyResolver::resolveRequestToGdsKeyMapFromSources(
            $travelers,
            $keySourceResponse,
            $holdResponse,
        );

        if ($keyMap === []) {
            $providerLocator = TravelportHoldPricingInfoParser::extractProviderLocatorCode(
                array_merge($holdResponse, ['parsed' => $keySourceResponse['parsed'] ?? $holdResponse['parsed'] ?? []]),
            );
            $leadLastName = trim((string) (
                $travelers[0]['lastName']
                ?? $travelers[0]['last_name']
                ?? ''
            ));

            if ($providerLocator !== '' && $leadLastName !== '') {
                $providerRetrieve = $this->client->universalRecordRetrieveByProvider($providerLocator, $leadLastName);
                if ($providerRetrieve['success'] ?? false) {
                    $keyMap = TravelportHoldTravelerKeyResolver::resolveRequestToGdsKeyMapFromSources(
                        $travelers,
                        $providerRetrieve,
                        $keySourceResponse,
                        $holdResponse,
                    );
                    if ($keyMap !== []) {
                        $keySourceResponse = $providerRetrieve;
                        Log::info('Travelport resolved GDS traveler keys from provider record retrieve', [
                            'booking_id' => $bookingId,
                            'provider_locator' => $providerLocator,
                            'key_map' => $keyMap,
                        ]);
                    }
                }
            }
        }

        if ($keyMap !== []) {
            Log::info('Travelport remapping passenger keys for fare storage', [
                'booking_id' => $bookingId,
                'universal_locator' => $universalLocator,
                'key_map' => $keyMap,
                'key_source' => ($retrieveResponse['success'] ?? false) ? 'universal_record_retrieve' : 'hold_response',
            ]);
            $pricingData = TravelportHoldTravelerKeyResolver::remapPricingDataTravelerRefs($pricingData, $keyMap);
            $travelers = TravelportHoldTravelerKeyResolver::remapTravelerKeys($travelers, $keyMap);
        } else {
            Log::warning('Travelport could not resolve GDS traveler keys for fare storage', [
                'booking_id' => $bookingId,
                'universal_locator' => $universalLocator,
                'traveler_count' => count($travelers),
                'retrieve_traveler_keys' => TravelportHoldTravelerKeyResolver::sampleExtractedTravelerKeys($keySourceResponse),
                'hold_traveler_keys' => TravelportHoldTravelerKeyResolver::sampleExtractedTravelerKeys($holdResponse),
            ]);

            return [];
        }

        $pricingData = TravelportHoldTravelerKeyResolver::remapPricingDataSegmentRefsFromHold(
            $pricingData,
            $keySourceResponse['success'] ?? false ? $keySourceResponse : $holdResponse,
        );

        $storeResponse = $this->client->storeFaresOnUniversalRecord(
            $universalLocator,
            $version,
            $pricingData,
            $travelers,
        );

        if (! ($storeResponse['success'] ?? false)) {
            Log::warning('Travelport store fares on universal record failed', [
                'booking_id' => $bookingId,
                'universal_locator' => $universalLocator,
                'error' => $storeResponse['error'] ?? null,
            ]);

            return [];
        }

        $keys = TravelportHoldPricingInfoParser::extractReservationKeys($storeResponse);
        Log::info('Travelport store fares on universal record completed', [
            'booking_id' => $bookingId,
            'universal_locator' => $universalLocator,
            'air_pricing_info_keys' => $keys,
        ]);

        return $keys;
    }

    /**
     * @return list<string>
     */
    private function attemptStoreFaresOnBooking(B2bFlightBooking $booking): array
    {
        $bookingRequest = is_array($booking->booking_request) ? $booking->booking_request : [];
        $universalLocator = $booking->travelportUniversalLocator();
        if ($universalLocator === '') {
            return [];
        }

        $travelers = is_array($bookingRequest['travelers'] ?? null) ? $bookingRequest['travelers'] : [];
        if ($travelers === []) {
            $passengersData = is_array($booking->passengers_data) ? $booking->passengers_data : [];
            $searchRequest = is_array($booking->search_request) ? $booking->search_request : [];
            $searchRequest = TravelportHoldPayloadBuilder::enrichSearchDataWithPassengerAges(
                $searchRequest,
                $passengersData,
            );
            try {
                $travelers = TravelportHoldPayloadBuilder::buildTravelers($passengersData, $searchRequest);
            } catch (\Throwable) {
                return [];
            }
        }

        $segments = is_array($bookingRequest['air_price_segments'] ?? null)
            ? $bookingRequest['air_price_segments']
            : TravelportHoldPayloadBuilder::buildAirPriceSegments(is_array($booking->itinerary_data) ? $booking->itinerary_data : []);
        if ($segments === [] || $travelers === []) {
            return [];
        }

        $passengerCounts = is_array($bookingRequest['passenger_counts'] ?? null)
            ? $bookingRequest['passenger_counts']
            : TravelportHoldPayloadBuilder::passengerCounts([
                'adults' => $booking->adults,
                'children' => $booking->children,
                'infants' => $booking->infants,
            ]);

        $searchRequest = is_array($booking->search_request) ? $booking->search_request : [];
        $searchRequest = TravelportHoldPayloadBuilder::enrichSearchDataWithPassengerAges(
            $searchRequest,
            is_array($booking->passengers_data) ? $booking->passengers_data : [],
        );

        $priceResponse = $this->client->airPrice($segments, $passengerCounts, $searchRequest, $travelers);
        if (! ($priceResponse['success'] ?? false)) {
            Log::warning('Travelport airPrice failed during fare storage recovery', [
                'booking_id' => $booking->id,
                'error' => $priceResponse['error'] ?? null,
            ]);

            return [];
        }

        $requestedClass = strtoupper(trim((string) data_get($booking->itinerary_data, 'booking_code', '')));
        $pricingData = TravelportAirPriceParser::extract(
            (string) ($priceResponse['raw'] ?? ''),
            $requestedClass,
        );
        $pricingData['passenger_types'] = TravelportHoldPayloadBuilder::passengerTypesFromTravelers($travelers);
        $pricingData = $this->expandPricingDataForTravelers($pricingData, $travelers);
        $pricingData = $this->alignPricingToSegments($pricingData);

        $version = trim((string) (
            data_get($booking->booking_response, 'travelport_universal_version')
            ?? data_get($booking->booking_response, 'UniversalRecord.@attributes.Version')
            ?? '0'
        ));

        return $this->storeFaresAndExtractKeys(
            $booking->id,
            $universalLocator,
            $version,
            $pricingData,
            $travelers,
            $this->holdResponsePayloadFromBooking($booking),
        );
    }

    /**
     * AirPrice often returns one BookingInfo per fare type, but hold needs one per traveler.
     *
     * @param  array<string, mixed>  $pricingData
     * @param  list<array<string, mixed>>  $travelers
     * @return array<string, mixed>
     */
    private function expandPricingDataForTravelers(array $pricingData, array $travelers): array
    {
        $passengerTypes = TravelportHoldPayloadBuilder::passengerTypesFromTravelers($travelers);
        $pricingData['passenger_types'] = $passengerTypes;

        $fareInfosByKey = [];
        foreach ($pricingData['fare_infos'] ?? [] as $fareInfo) {
            if (! is_array($fareInfo)) {
                continue;
            }
            $key = (string) ($fareInfo['key'] ?? '');
            if ($key !== '') {
                $fareInfosByKey[$key] = $fareInfo;
            }
        }

        $bookingInfosByType = [];
        foreach ($pricingData['booking_infos'] ?? [] as $bookingInfo) {
            if (! is_array($bookingInfo)) {
                continue;
            }

            $fareRef = (string) ($bookingInfo['fare_info_ref'] ?? '');
            $fareInfo = $fareInfosByKey[$fareRef] ?? null;
            $typeCode = TravelportHoldPayloadBuilder::normalizeHoldPassengerTypeCode(
                is_array($fareInfo) ? (string) ($fareInfo['passenger_type_code'] ?? 'ADT') : 'ADT',
            );
            $bookingInfosByType[$typeCode][] = $bookingInfo;
        }

        if ($bookingInfosByType === [] && is_array($pricingData['booking_infos'] ?? null)) {
            foreach ($pricingData['booking_infos'] as $bookingInfo) {
                if (is_array($bookingInfo)) {
                    $bookingInfosByType['ADT'][] = $bookingInfo;
                }
            }
        }

        $expandedBookingInfos = [];
        $typeUseIndex = [];
        foreach ($passengerTypes as $passengerType) {
            $typeCode = TravelportHoldPayloadBuilder::normalizeHoldPassengerTypeCode(
                (string) ($passengerType['code'] ?? 'ADT'),
            );
            $candidates = $bookingInfosByType[$typeCode] ?? [];
            if ($candidates === []) {
                continue;
            }

            $index = $typeUseIndex[$typeCode] ?? 0;
            $expandedBookingInfos[] = $candidates[min($index, count($candidates) - 1)];
            $typeUseIndex[$typeCode] = $index + 1;
        }

        if ($expandedBookingInfos !== []) {
            $pricingData['booking_infos'] = $expandedBookingInfos;
        }

        $neededHostRefs = array_values(array_unique(array_filter(array_map(
            static fn ($bookingInfo) => is_array($bookingInfo) ? (string) ($bookingInfo['host_token_ref'] ?? '') : '',
            $pricingData['booking_infos'] ?? [],
        ))));

        if ($neededHostRefs !== []) {
            $hostTokensByKey = [];
            foreach ($pricingData['host_tokens'] ?? [] as $hostToken) {
                if (! is_array($hostToken)) {
                    continue;
                }
                $key = (string) ($hostToken['key'] ?? '');
                if ($key !== '') {
                    $hostTokensByKey[$key] = $hostToken;
                }
            }

            $pricingData['host_tokens'] = array_values(array_filter(array_map(
                static fn (string $ref) => $hostTokensByKey[$ref] ?? null,
                $neededHostRefs,
            )));
        }

        return $pricingData;
    }

    /**
     * @param  array<string, mixed>  $pricingData
     * @param  list<array<string, mixed>>  $travelers
     */
    private function assertHoldPricingDataComplete(array $pricingData, array $travelers): void
    {
        $passengerCount = count($travelers);
        $passengerTypeCount = count($pricingData['passenger_types'] ?? []);
        $bookingInfoCount = count($pricingData['booking_infos'] ?? []);
        $hostTokenCount = count($pricingData['host_tokens'] ?? []);
        $fareInfoCount = count($pricingData['fare_infos'] ?? []);

        if ($passengerTypeCount !== $passengerCount) {
            throw new \RuntimeException('Travelport pricing does not match passenger count. Please search again.');
        }

        $requiredTypes = [];
        foreach ($travelers as $traveler) {
            if (! is_array($traveler)) {
                continue;
            }
            $typeCode = TravelportHoldPayloadBuilder::normalizeHoldPassengerTypeCode(
                (string) ($traveler['traveler_type'] ?? $traveler['traveler_type_code'] ?? 'ADT'),
            );
            $requiredTypes[$typeCode] = true;
        }

        $availableFareTypes = [];
        foreach ($pricingData['fare_infos'] ?? [] as $fareInfo) {
            if (! is_array($fareInfo)) {
                continue;
            }
            $typeCode = TravelportHoldPayloadBuilder::normalizeHoldPassengerTypeCode(
                (string) ($fareInfo['passenger_type_code'] ?? 'ADT'),
            );
            $availableFareTypes[$typeCode] = true;
        }

        foreach (array_keys($requiredTypes) as $typeCode) {
            if (! isset($availableFareTypes[$typeCode])) {
                Log::warning('Travelport hold pricing missing fare for passenger type', [
                    'missing_type' => $typeCode,
                    'available_fare_types' => array_keys($availableFareTypes),
                ]);
                throw new \RuntimeException('Travelport pricing is incomplete for this passenger mix. Please search again.');
            }
        }

        if ($bookingInfoCount < $passengerCount || $hostTokenCount < 1 || $fareInfoCount < 1) {
            Log::warning('Travelport hold pricing incomplete after alignment', [
                'passenger_count' => $passengerCount,
                'booking_info_count' => $bookingInfoCount,
                'host_token_count' => $hostTokenCount,
                'fare_info_count' => $fareInfoCount,
            ]);
            throw new \RuntimeException('Travelport pricing is incomplete for this passenger mix. Please search again.');
        }
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

        $uniqueSegments = [];
        $seenSegmentKeys = [];
        foreach ($pricingData['segments'] ?? [] as $segment) {
            if (! is_array($segment)) {
                continue;
            }
            $key = (string) ($segment['key'] ?? '');
            if ($key === '' || isset($seenSegmentKeys[$key])) {
                continue;
            }
            $seenSegmentKeys[$key] = true;
            $uniqueSegments[] = $segment;
        }
        $pricingData['segments'] = $uniqueSegments;
        $segmentKeys = array_values(array_filter(array_map(
            static fn ($seg) => is_array($seg) ? (string) ($seg['key'] ?? '') : '',
            $pricingData['segments'],
        )));

        $bookingInfos = [];
        foreach ($pricingData['booking_infos'] ?? [] as $bookingInfo) {
            if (! is_array($bookingInfo)) {
                continue;
            }
            $segmentRef = (string) ($bookingInfo['segment_ref'] ?? '');
            if ($segmentRef === '' || ! in_array($segmentRef, $segmentKeys, true)) {
                continue;
            }

            $bookingInfos[] = $bookingInfo;
        }
        $pricingData['booking_infos'] = $bookingInfos;

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
     * @param  array<string, mixed>  $itineraryData
     * @param  array<string, mixed>  $searchRequest
     * @param  list<array<string, mixed>>  $segments
     * @param  array<string, int>  $passengerCounts
     * @param  list<array<string, mixed>>  $travelers
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function buildFreshHoldPricing(
        B2bFlightBooking $booking,
        array $itineraryData,
        array $searchRequest,
        array $segments,
        array $passengerCounts,
        array $travelers,
    ): array {
        $priceResponse = $this->client->airPrice($segments, $passengerCounts, $searchRequest, $travelers);
        if (! ($priceResponse['success'] ?? false)) {
            throw new \RuntimeException($priceResponse['error'] ?? 'Travelport airPrice failed.');
        }

        $requestedClass = strtoupper(trim((string) ($itineraryData['booking_code'] ?? '')));
        $pricingData = TravelportAirPriceParser::extract(
            (string) ($priceResponse['raw'] ?? ''),
            $requestedClass,
        );

        if (($pricingData['solution_key'] ?? '') === '' || ($pricingData['segments'] ?? []) === []) {
            if ($requestedClass !== '') {
                throw new \RuntimeException(
                    'Booking class ' . $requestedClass . ' is no longer available on this flight. Please search again.',
                );
            }

            throw new \RuntimeException('Unable to parse Travelport pricing for hold.');
        }

        $itineraryFare = $this->extractItineraryFareFromAirPrice(
            $priceResponse,
            $searchRequest,
            $itineraryData,
        );
        if ($itineraryFare !== []) {
            $itineraryData = array_merge($itineraryData, $itineraryFare);
        }

        $quotedTotal = (float) $booking->total_amount;
        $repricedTotal = (float) ($itineraryFare['totalPrice'] ?? 0);
        if ($repricedTotal > 0 && $quotedTotal > 0 && abs($repricedTotal - $quotedTotal) > 0.05) {
            throw new \RuntimeException(sprintf(
                'Repriced total (%.2f) differs from quoted total (%.2f). Please search again.',
                $repricedTotal,
                $quotedTotal,
            ));
        }

        $pricingData['passenger_types'] = TravelportHoldPayloadBuilder::passengerTypesFromTravelers($travelers);
        $pricingData = $this->expandPricingDataForTravelers($pricingData, $travelers);
        $pricingData = $this->alignPricingToSegments($pricingData);
        $this->assertHoldPricingDataComplete($pricingData, $travelers);

        return [$priceResponse, $pricingData, $itineraryData];
    }

    /**
     * GDS 12008 ("IGNORE AND RETRIEVE BOOKING FILE") may still produce tickets on the PNR.
     * If no tickets exist yet, the host work area is out of sync — re-retrieve the booking
     * file and retry ticketing once.
     *
     * @param  array<string, mixed>  $ticketResponse
     * @param  list<string>  $airPricingInfoKeys
     * @return array{ticket_numbers: list<string>, ticket_response: array<string, mixed>, source: string}|null
     */
    private function attemptRecoverTicketsAfterTicketingFailure(
        B2bFlightBooking $booking,
        array $ticketResponse,
        string $airReservationLocator,
        array $airPricingInfoKeys = [],
        string $platingCarrier = '',
        float $gdsCommissionPercentage = 0.0,
    ): ?array {
        $parsed = is_array($ticketResponse['parsed'] ?? null) ? $ticketResponse['parsed'] : [];
        $ticketingRsp = $parsed['Body']['AirTicketingRsp'] ?? [];
        $stored = is_array($ticketingRsp) ? $ticketingRsp : [];
        if (is_string($ticketResponse['raw'] ?? null) && ($ticketResponse['raw'] ?? '') !== '') {
            $stored['raw'] = $ticketResponse['raw'];
        }

        $numbers = FlightBookingTicketResolver::fromResponse($stored);
        if ($numbers !== []) {
            return [
                'ticket_numbers' => $numbers,
                'ticket_response' => $stored,
                'source' => 'air_ticket_response',
            ];
        }

        $universalLocator = $booking->travelportUniversalLocator();
        if ($universalLocator !== '') {
            $retrieve = $this->client->universalRecordRetrieve($universalLocator);
            if ($retrieve['success'] ?? false) {
                $retrieveRaw = (string) ($retrieve['raw'] ?? '');
                $numbers = TravelportAirTicketingResult::ticketNumbersFromTkneRaw($retrieveRaw);
                if ($numbers !== []) {
                    return [
                        'ticket_numbers' => $numbers,
                        'ticket_response' => $stored !== [] ? $stored : ['raw' => $retrieveRaw],
                        'source' => 'universal_record_retrieve_tkne',
                    ];
                }
            }
        }

        $providerLocator = $booking->travelportProviderLocator();
        if ($providerLocator !== '') {
            $document = $this->client->airRetrieveDocument($providerLocator, [], $airReservationLocator);
            if ($document['success'] ?? false) {
                $docStored = [
                    'parsed' => is_array($document['parsed'] ?? null) ? $document['parsed'] : [],
                    'raw' => (string) ($document['raw'] ?? ''),
                ];
                $numbers = FlightBookingTicketResolver::fromResponse($docStored);
                if ($numbers !== []) {
                    return [
                        'ticket_numbers' => $numbers,
                        'ticket_response' => $stored !== [] ? $stored : $docStored,
                        'source' => 'air_retrieve_document',
                    ];
                }
            }
        }

        // No tickets found anywhere. For a host sync error (12008 / IGNORE AND RETRIEVE
        // BOOKING FILE) the booking file was just re-synced above; retry ticketing once.
        if ($this->isIgnoreAndRetrieveHostError($ticketResponse) && $airPricingInfoKeys !== [] && $platingCarrier !== '') {
            Log::warning('Travelport retrying ticketing once after host sync error (12008)', [
                'booking_id' => $booking->id,
                'locator' => $airReservationLocator,
                'plating_carrier' => $platingCarrier,
            ]);

            $retryResponse = $this->client->airTicket(
                $airReservationLocator,
                $airPricingInfoKeys,
                $platingCarrier,
                $gdsCommissionPercentage,
            );

            $retryParsed = is_array($retryResponse['parsed'] ?? null) ? $retryResponse['parsed'] : [];
            $retryStored = is_array($retryParsed['Body']['AirTicketingRsp'] ?? null)
                ? $retryParsed['Body']['AirTicketingRsp']
                : [];
            if (is_string($retryResponse['raw'] ?? null) && ($retryResponse['raw'] ?? '') !== '') {
                $retryStored['raw'] = $retryResponse['raw'];
            }

            $retryNumbers = FlightBookingTicketResolver::fromResponse($retryStored);

            if (($retryResponse['success'] ?? false) || $retryNumbers !== []) {
                if ($retryNumbers === [] && $universalLocator !== '') {
                    $retrieveAfterRetry = $this->client->universalRecordRetrieve($universalLocator);
                    if ($retrieveAfterRetry['success'] ?? false) {
                        $retryNumbers = TravelportAirTicketingResult::ticketNumbersFromTkneRaw(
                            (string) ($retrieveAfterRetry['raw'] ?? ''),
                        );
                    }
                }

                if (! TravelportAirTicketingResult::shouldTreatAsFailure($retryStored, $retryNumbers)) {
                    return [
                        'ticket_numbers' => $retryNumbers,
                        'ticket_response' => $retryStored,
                        'source' => 'air_ticket_retry_after_sync',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $ticketResponse
     */
    private function isIgnoreAndRetrieveHostError(array $ticketResponse): bool
    {
        $code = trim((string) ($ticketResponse['error_code'] ?? ''));
        if ($code === '12008') {
            return true;
        }

        $haystack = strtoupper(
            (string) ($ticketResponse['error'] ?? '') . ' ' . (string) ($ticketResponse['raw'] ?? ''),
        );

        return str_contains($haystack, 'IGNORE AND RETRIEVE BOOKING FILE');
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function formatTravelportGdsError(array $response, string $fallback): string
    {
        $code = trim((string) ($response['error_code'] ?? ''));
        $traceId = trim((string) ($response['trace_id'] ?? ''));
        $raw = trim((string) ($response['error'] ?? $fallback));
        $details = is_array($response['error_details'] ?? null) ? $response['error_details'] : [];
        $segmentErrors = is_array($details['segment_errors'] ?? null) ? $details['segment_errors'] : [];
        $segmentErrorText = implode('; ', array_filter(array_map('strval', $segmentErrors)));

        if ($code === '3000') {
            $availabilityHint = null;
            $classInvalid = false;
            foreach ($segmentErrors as $segmentError) {
                $message = (string) $segmentError;
                if (stripos($message, 'CLASS DOES NOT EXIST') !== false) {
                    $classInvalid = true;
                }
                if (stripos($message, 'AVAIL') !== false
                    || stripos($message, 'WL CLOSED') !== false
                    || stripos($message, 'UNABLE') !== false) {
                    $availabilityHint ??= $message;
                }
            }

            if ($classInvalid) {
                $msg = 'The airline rejected the hold because the booking class for this fare is no longer valid on the flight';
                if ($segmentErrorText !== '') {
                    $msg .= ' (' . trim($segmentErrorText) . ')';
                }
                $msg .= '. Please search again and choose a fresh fare.';
            } elseif (is_string($availabilityHint) && $availabilityHint !== '') {
                $msg = 'The airline rejected the hold because seats are no longer available at the quoted fare (' . trim($availabilityHint) . '). Please search again.';
            } else {
                $msg = 'The airline reservation system rejected the hold (GDS error 3000). '
                    . 'This is usually caused by sold-out inventory, a fare/class mismatch, or a host availability issue — not a passenger form error.';
                if ($segmentErrorText !== '') {
                    $msg .= ' Host detail: ' . $segmentErrorText . '.';
                }
            }

            if ($traceId !== '') {
                $msg .= ' Reference trace: ' . $traceId . '.';
            }

            return $msg;
        }

        if ($code === '12008' || stripos($raw, 'IGNORE AND RETRIEVE BOOKING FILE') !== false) {
            $msg = 'The airline host returned a ticketing error (GDS 12008). '
                . 'Tickets may still have been issued on the PNR — retrieve the booking in Travelport or retry fulfillment from admin.';
            if ($traceId !== '') {
                $msg .= ' Reference trace: ' . $traceId . '.';
            }

            return $msg;
        }

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

    /**
     * @param  array<string, mixed>  $priceResponse
     * @param  array<string, mixed>  $searchRequest
     * @param  array<string, mixed>  $itineraryData
     * @return array<string, mixed>
     */
    private function extractItineraryFareFromAirPrice(
        array $priceResponse,
        array $searchRequest,
        array $itineraryData,
    ): array {
        $parsed = is_array($priceResponse['parsed'] ?? null) ? $priceResponse['parsed'] : null;
        if ($parsed === null) {
            return [];
        }

        $legs = is_array($itineraryData['legs'] ?? null) ? $itineraryData['legs'] : [];
        $fareOptions = TravelportAirPricePresenter::toFareOptions($parsed, $searchRequest, $legs);
        $repricedFare = $fareOptions[0] ?? null;

        if (! is_array($repricedFare)) {
            return [];
        }

        $updates = array_filter([
            'passenger_fare_lines' => $repricedFare['passenger_fare_lines'] ?? null,
            'passenger_fare_warning' => $repricedFare['passenger_fare_warning'] ?? null,
            'supplierBasePrice' => $repricedFare['supplierBasePrice'] ?? null,
            'supplierTaxes' => $repricedFare['supplierTaxes'] ?? null,
            'basePrice' => $repricedFare['basePrice'] ?? null,
            'taxes' => $repricedFare['taxes'] ?? null,
            'totalPrice' => $repricedFare['totalPrice'] ?? null,
            'currency' => $repricedFare['currency'] ?? null,
        ], static fn ($value) => $value !== null);

        if (($updates['passenger_fare_lines'] ?? null) === null) {
            return $updates;
        }

        $merged = FlightPassengerFareLinesPresenter::syncItineraryFareTotals(
            array_merge($itineraryData, $updates),
        );

        return array_intersect_key(
            $merged,
            array_flip([
                'passenger_fare_lines',
                'passenger_fare_warning',
                'supplierBasePrice',
                'supplierTaxes',
                'basePrice',
                'taxes',
                'totalPrice',
                'currency',
            ]),
        );
    }
}
