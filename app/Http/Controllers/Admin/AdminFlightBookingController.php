<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesFlightEticketExport;
use App\Models\B2bFlightBooking;
use App\Models\B2bVendor;
use App\Services\FlightService;
use App\Services\Travelport\TravelportApiClient;
use App\Services\Travelport\TravelportBookingService;
use App\Support\BookingCancellationEligibility;
use App\Support\FlightBookingAdminPresenter;
use App\Support\FlightBookingAdminEticketPresenter;
use App\Support\FlightPassengerFareLinesPresenter;
use App\Support\SabreFareRulesRequestBuilder;
use App\Support\SabrePricingResolver;
use App\Support\SupplierFlightBookingDetailsPresenter;
use App\Support\Travelport\TravelportCertificationPackageBuilder;
use App\Support\Travelport\TravelportFareRulesResponseParser;
use App\Support\Travelport\TravelportStoredFareRuleResolver;
use Illuminate\Http\Request;

class AdminFlightBookingController extends Controller
{
    use HandlesFlightEticketExport;
    public function index(Request $request)
    {
        $filterVendorId = $request->input('vendor_id');
        $filterVendor = null;

        if ($filterVendorId !== null && $filterVendorId !== '' && ctype_digit((string) $filterVendorId)) {
            $filterVendor = B2bVendor::find((int) $filterVendorId);
        }

        $bookings = B2bFlightBooking::query()
            ->with(['vendor.parentVendor'])
            ->when($filterVendor !== null, fn ($q) => $q->where('b2b_vendor_id', $filterVendor->id))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.flight-bookings.list', compact('bookings', 'filterVendor'))
            ->with('title', 'Flight Bookings');
    }

    public function show(int $id, FlightService $flightService, TravelportBookingService $travelportBookingService)
    {
        $booking = B2bFlightBooking::with(['vendor.parentVendor'])->findOrFail($id);
        $booking->reconcileStatusAfterHoldPayment();

        if ($booking->isSabre() && $booking->hasAirlinePnr()) {
            $flightService->syncSabreTicketNumbersIfMissing($booking);
            $booking->refresh();
        }

        // Admin uses saved Sabre responses only (no live GetReservation SOAP lookup).
        $supplierBookingDetails = SupplierFlightBookingDetailsPresenter::present($booking, null);
        $cancellation = BookingCancellationEligibility::forFlight($booking);
        $adminDetails = FlightBookingAdminPresenter::present($booking);
        $ticketDetails = $flightService->resolveTicketDetails($booking);
        $legs = FlightItineraryLegsNormalizer::forBooking($booking, $ticketDetails);

        if ($booking->isTravelport()) {
            if (FlightPassengerFareLinesPresenter::needsFareBreakdownRefresh($booking)) {
                $travelportBookingService->refreshBookingFareBreakdown($booking);
                $booking->refresh();
            }
        }

        $fareBreakdown = flightFareBreakdownForBooking($booking);
        $adminEticketDetails = FlightBookingAdminEticketPresenter::present($booking, $ticketDetails, $fareBreakdown);

        return view('admin.flight-bookings.show', compact('booking', 'supplierBookingDetails', 'cancellation', 'adminDetails', 'ticketDetails', 'legs', 'fareBreakdown', 'adminEticketDetails'))
            ->with('title', 'Booking ' . $booking->booking_number);
    }

    public function eticketPdf(int $flight_booking, FlightService $flightService, Request $request)
    {
        $booking = B2bFlightBooking::findOrFail($flight_booking);

        return $this->flightEticketExportResponse($booking, $flightService, $request);
    }

    public function travelportCertLogs(int $flight_booking)
    {
        $booking = B2bFlightBooking::findOrFail($flight_booking);

        return TravelportCertificationPackageBuilder::download($booking);
    }

    public function refreshFareBreakdown(int $id, TravelportBookingService $travelportBookingService)
    {
        $booking = B2bFlightBooking::findOrFail($id);

        if (! $booking->isTravelport()) {
            return redirect()
                ->route('admin.flight-bookings.show', $booking->id)
                ->with('notify_error', 'Fare breakdown refresh is only available for Travelport bookings.');
        }

        $result = $travelportBookingService->refreshBookingFareBreakdown($booking);

        if (! ($result['success'] ?? false)) {
            return redirect()
                ->route('admin.flight-bookings.show', $booking->id)
                ->with('notify_error', $result['error'] ?? 'Unable to refresh fare breakdown.');
        }

        return redirect()
            ->route('admin.flight-bookings.show', $booking->id)
            ->with('notify_success', $result['message'] ?? 'Fare breakdown refreshed.');
    }

    public function fareRules(int $id, FlightService $flightService)
    {
        $booking = B2bFlightBooking::findOrFail($id);

        if ($booking->isTravelport()) {
            return $this->travelportFareRulesForBooking($booking);
        }

        $itineraryData = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $grouped = is_array($booking->search_response) ? $booking->search_response : [];

        if ($itineraryData === [] || $grouped === []) {
            return response()->json([
                'success' => false,
                'error' => 'Saved search data is not available for this booking.',
            ], 422);
        }

        $pricingBlock = SabrePricingResolver::pricingBlockFromStoredCard($itineraryData, $grouped);
        if ($pricingBlock === null) {
            return response()->json([
                'success' => false,
                'error' => 'Fare not found for this booking.',
            ], 404);
        }

        $departureDate = $booking->departure_date?->format('Y-m-d');
        $returnDate = $booking->return_date?->format('Y-m-d');
        $ruleRequests = SabreFareRulesRequestBuilder::fromPricingBlock(
            $pricingBlock,
            $grouped,
            $departureDate,
            $returnDate,
        );

        if ($ruleRequests === []) {
            return response()->json([
                'success' => false,
                'error' => 'Fare rule details are not available for this fare.',
            ], 422);
        }

        $structuredFallback = is_array($itineraryData['fare_rules'] ?? null) ? $itineraryData['fare_rules'] : null;

        try {
            $components = $flightService->fetchFareRulesText(
                $ruleRequests,
                $structuredFallback,
            );

            return response()->json([
                'success' => true,
                'components' => $components,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Unable to load full fare rules right now. Please try again.',
            ], 500);
        }
    }

    private function travelportFareRulesForBooking(B2bFlightBooking $booking)
    {
        $itineraryData = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $ruleRequest = TravelportStoredFareRuleResolver::resolveFromBooking($booking);
        $storedRules = is_array($itineraryData['fare_rules'] ?? null) ? $itineraryData['fare_rules'] : [];

        if ($ruleRequest === null) {
            $fallback = TravelportStoredFareRuleResolver::componentsFromStoredSummary($storedRules);
            if ($fallback !== []) {
                return response()->json([
                    'success' => true,
                    'components' => $fallback,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Fare rule details are not available for this Travelport booking.',
            ], 422);
        }

        try {
            $response = (new TravelportApiClient())->airFareRules($ruleRequest);

            if (! ($response['success'] ?? false)) {
                $fallback = TravelportStoredFareRuleResolver::componentsFromStoredSummary($storedRules);
                if ($fallback !== []) {
                    return response()->json([
                        'success' => true,
                        'components' => $fallback,
                        'warning' => $response['error'] ?? 'Live fare rules unavailable; showing search summary.',
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'error' => $response['error'] ?? 'Unable to load fare rules from Travelport.',
                ], 500);
            }

            $components = TravelportFareRulesResponseParser::toComponents(
                (string) ($response['raw'] ?? ''),
                $ruleRequest,
            );

            if ($components === []) {
                $fallback = TravelportStoredFareRuleResolver::componentsFromStoredSummary($storedRules);
                if ($fallback !== []) {
                    return response()->json([
                        'success' => true,
                        'components' => $fallback,
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'error' => 'No detailed fare rules returned for this fare.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'components' => $components,
            ]);
        } catch (\Throwable $e) {
            report($e);

            $fallback = TravelportStoredFareRuleResolver::componentsFromStoredSummary($storedRules);
            if ($fallback !== []) {
                return response()->json([
                    'success' => true,
                    'components' => $fallback,
                    'warning' => 'Live fare rules unavailable; showing search summary.',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Unable to load full fare rules right now. Please try again.',
            ], 500);
        }
    }
}
