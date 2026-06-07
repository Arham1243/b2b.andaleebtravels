<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bFlightBooking;
use App\Models\B2bVendor;
use App\Services\FlightService;
use App\Support\BookingCancellationEligibility;
use App\Support\SabreFareRulesRequestBuilder;
use App\Support\SabrePricingResolver;
use App\Support\SupplierFlightBookingDetailsPresenter;
use Illuminate\Http\Request;

class AdminFlightBookingController extends Controller
{
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

    public function show(int $id)
    {
        $booking = B2bFlightBooking::with(['vendor.parentVendor'])->findOrFail($id);
        $booking->reconcileStatusAfterHoldPayment();

        // Admin uses saved Sabre responses only (no live GetReservation SOAP lookup).
        $supplierBookingDetails = SupplierFlightBookingDetailsPresenter::present($booking, null);
        $cancellation = BookingCancellationEligibility::forFlight($booking);

        return view('admin.flight-bookings.show', compact('booking', 'supplierBookingDetails', 'cancellation'))
            ->with('title', 'Booking ' . $booking->booking_number);
    }

    public function fareRules(int $id, FlightService $flightService)
    {
        $booking = B2bFlightBooking::findOrFail($id);
        $provider = strtolower((string) ($booking->provider ?? 'sabre'));

        if ($provider !== 'sabre') {
            return response()->json([
                'success' => false,
                'error' => 'Fare rules are not available for this provider yet.',
            ], 422);
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
        $ruleRequests = SabreFareRulesRequestBuilder::fromPricingBlock($pricingBlock, $grouped, $departureDate);

        if ($ruleRequests === []) {
            return response()->json([
                'success' => false,
                'error' => 'Fare rule details are not available for this fare.',
            ], 422);
        }

        try {
            $components = $flightService->fetchFareRulesText($ruleRequests);

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
}
