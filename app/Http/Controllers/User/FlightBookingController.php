<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\B2bFlightBooking;
use App\Models\B2bSavedPassenger;
use App\Models\B2bWalletLedger;
use App\Services\FlightService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FlightBookingController extends Controller
{
    public function checkout(Request $request, int $itinerary)
    {
        $results = session('flight_search_results', []);
        $params = session('flight_search_params', []);

        $itineraryData = $results[$itinerary] ?? null;

        if (!$itineraryData || empty($params)) {
            return redirect()->route('user.flights.index')
                ->with('notify_error', 'Please search for flights again.');
        }

        $totalAmount = (float) ($itineraryData['totalPrice'] ?? 0);
        $currency = $itineraryData['currency'] ?? 'AED';
        $walletBalance = (float) (Auth::user()->main_balance ?? 0);

        return view('user.flights.checkout', [
            'itineraryId' => $itinerary,
            'itinerary' => $itineraryData,
            'searchParams' => $params,
            'totalAmount' => $totalAmount,
            'currency' => $currency,
            'walletBalance' => $walletBalance,
        ]);
    }

    public function processPayment(Request $request, FlightService $flightService)
    {
        $validated = $request->validate([
            'itinerary_id' => 'required|integer',
            'lead.title' => 'required|string',
            'lead.first_name' => 'required|string',
            'lead.last_name' => 'required|string',
            'lead.email' => 'required|email',
            'lead.phone' => 'required|string',
            'lead.address' => 'nullable|string',

            'passengers' => 'required|array|min:1',
            'passengers.*.type' => 'required|in:ADT,C06,INF',
            'passengers.*.title' => 'required|string',
            'passengers.*.first_name' => 'required|string',
            'passengers.*.last_name' => 'required|string',

            'payment_method' => 'required_without:use_wallet|in:payby,tabby,tamara',
            'use_wallet' => 'nullable|in:1',
            'wallet_amount' => 'nullable|numeric|min:0',
        ]);

        $results = session('flight_search_results', []);
        $params = session('flight_search_params', []);

        $itineraryId = (int) $validated['itinerary_id'];
        $itineraryData = $results[$itineraryId] ?? null;

        if (!$itineraryData || empty($params)) {
            return redirect()->route('user.flights.index')
                ->with('notify_error', 'Flight selection expired. Please search again.');
        }

        $revalidate = $flightService->revalidateItinerary(
            session('flight_search_response', []),
            $itineraryId,
            (int) ($params['adults'] ?? 1),
            (int) ($params['children'] ?? 0),
            (int) ($params['infants'] ?? 0)
        );

        if (!($revalidate['success'] ?? false)) {
            return redirect()->route('user.flights.index')
                ->with('notify_error', $revalidate['error'] ?? 'Unable to revalidate flight itinerary. Please search again.');
        }

        $totalAmount = (float) ($itineraryData['totalPrice'] ?? 0);
        $currency = $itineraryData['currency'] ?? 'AED';

        $departureDate = !empty($params['departure_date'])
            ? Carbon::parse($params['departure_date'])->format('Y-m-d')
            : null;
        $returnDate = !empty($params['return_date'])
            ? Carbon::parse($params['return_date'])->format('Y-m-d')
            : null;

        $bookingData = [
            'itinerary_id' => $itineraryId,
            'from_airport' => $params['from'] ?? null,
            'to_airport' => $params['to'] ?? null,
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'adults' => (int) ($params['adults'] ?? 1),
            'children' => (int) ($params['children'] ?? 0),
            'infants' => (int) ($params['infants'] ?? 0),
            'passengers_data' => [
                'lead' => $validated['lead'],
                'passengers' => $validated['passengers'],
            ],
            'itinerary_data' => $itineraryData,
            'search_request' => session('flight_search_payload'),
            'search_response' => session('flight_search_response'),
            'total_amount' => $totalAmount,
            'currency' => $currency,
            'payment_method' => $validated['payment_method'] ?? null,
            'source_market' => $this->getSourceMarketFromIP(),
        ];

        $booking = $flightService->createBookingRecord($bookingData);

        $useWallet = !empty($validated['use_wallet']);
        $walletDeduction = 0;

        if ($useWallet) {
            $user = Auth::user();
            $requestedWalletAmount = (float) ($validated['wallet_amount'] ?? 0);
            $walletDeduction = min($requestedWalletAmount, (float) $user->main_balance, $totalAmount);

            if ($walletDeduction > 0) {
                $booking->update([
                    'wallet_amount' => $walletDeduction,
                ]);
            }
        }

        $remainingAmount = $totalAmount - $walletDeduction;

        if ($remainingAmount <= 0) {
            $booking->update([
                'payment_method' => 'wallet',
            ]);

            return redirect()->route('user.flights.payment.success', ['booking' => $booking->id]);
        }

        try {
            $redirectUrl = $flightService->getRedirectUrl($booking, $validated['payment_method']);
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            $booking->update([
                'booking_status' => 'failed',
                'payment_status' => 'failed',
                'wallet_amount' => 0,
            ]);

            Log::error('Flight payment redirect failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('user.flights.payment.failed', ['booking' => $booking->id])
                ->with('notify_error', 'Unable to process payment. Please try again.');
        }
    }

    public function paymentSuccess(Request $request, int $booking, FlightService $flightService)
    {
        try {
            $booking = B2bFlightBooking::findOrFail($booking);

            if ($booking->isPaid() && $booking->isConfirmed()) {
                return redirect()->route('user.flights.payment.success.view', ['booking' => $booking->id]);
            }

            if ($booking->payment_method === 'wallet' && $booking->wallet_amount >= $booking->total_amount) {
                $vendor = $booking->vendor;
                if ((float) $vendor->main_balance < (float) $booking->wallet_amount) {
                    $booking->update([
                        'payment_status' => 'failed',
                        'booking_status' => 'failed',
                        'wallet_amount' => 0,
                    ]);

                    return redirect()->route('user.flights.payment.failed', ['booking' => $booking->id])
                        ->with('notify_error', 'Insufficient wallet balance.');
                }

                $verificationResult = ['success' => true, 'data' => ['method' => 'wallet']];
            } elseif ($booking->payment_method === 'payby') {
                $verificationResult = $flightService->verifyPayByPayment($booking);
            } elseif ($booking->payment_method === 'tabby') {
                $verificationResult = $flightService->verifyTabbyPayment($booking);
            } elseif ($booking->payment_method === 'tamara') {
                $verificationResult = $flightService->verifyTamaraPayment($booking);
            } else {
                throw new \Exception('Invalid payment method');
            }

            if (!$verificationResult['success']) {
                $booking->update([
                    'payment_status' => 'failed',
                    'booking_status' => 'failed',
                    'wallet_amount' => 0,
                ]);

                return redirect()->route('user.flights.payment.failed', ['booking' => $booking->id])
                    ->with('notify_error', 'Payment verification failed. Please contact support.');
            }

            if ($booking->wallet_amount > 0) {
                B2bWalletLedger::recordDebit(
                    $booking->b2b_vendor_id,
                    (float) $booking->wallet_amount,
                    'Flight Booking #' . $booking->booking_number,
                    B2bFlightBooking::class,
                    $booking->id
                );
            }

            $booking->update([
                'payment_status' => 'paid',
                'payment_response' => $verificationResult['data'] ?? null,
            ]);

            $pnrResult = $flightService->createSabrePnr($booking);
            if (!$pnrResult['success']) {
                $booking->update([
                    'booking_status' => 'failed',
                ]);

                return redirect()->route('user.flights.payment.failed', ['booking' => $booking->id])
                    ->with('notify_error', 'Unable to confirm your booking. Our team will contact you shortly.');
            }

            $ticketResult = $flightService->issueSabreTicket($booking);
            if (!$ticketResult['success']) {
                return redirect()->route('user.flights.payment.failed', ['booking' => $booking->id])
                    ->with('notify_error', 'Booking confirmed but ticketing failed. Please contact support.');
            }

            return redirect()->route('user.flights.payment.success.view', ['booking' => $booking->id]);
        } catch (\Exception $e) {
            Log::error('Flight payment success processing failed', [
                'booking_id' => $booking ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('user.flights.index')
                ->with('notify_error', 'An error occurred while processing your payment. Please contact support.');
        }
    }

    public function paymentSuccessView(int $booking)
    {
        try {
            $booking = B2bFlightBooking::findOrFail($booking);

            if (!$booking->isPaid()) {
                return redirect()->route('user.flights.index')
                    ->with('notify_error', 'Invalid booking access.');
            }

            return view('user.flights.payment-success', compact('booking'));
        } catch (\Exception $e) {
            return redirect()->route('user.flights.index')
                ->with('notify_error', 'Booking not found.');
        }
    }

    public function paymentFailed(Request $request, ?int $booking = null)
    {
        try {
            if ($booking) {
                $booking = B2bFlightBooking::findOrFail($booking);

                if ($booking->payment_status === 'pending') {
                    $booking->update([
                        'payment_status' => 'failed',
                        'booking_status' => 'failed',
                        'wallet_amount' => 0,
                    ]);
                }

                return view('user.flights.payment-failed', compact('booking'));
            }

            return view('user.flights.payment-failed', ['booking' => null]);
        } catch (\Exception $e) {
            return view('user.flights.payment-failed', ['booking' => null]);
        }
    }

    /* =====================================================================
       HOLD BOOKING (PNR created without payment)
       ===================================================================== */

    public function holdCheckout(Request $request, int $itinerary)
    {
        $results = session('flight_search_results', []);
        $params  = session('flight_search_params', []);

        $itineraryData = $results[$itinerary] ?? null;

        if (!$itineraryData || empty($params)) {
            return redirect()->route('user.flights.index')
                ->with('notify_error', 'Please search for flights again.');
        }

        try {
            $savedPassengers = Auth::user()
                ->savedPassengers()
                ->orderBy('first_name')
                ->get()
                ->toArray();
        } catch (\Throwable $e) {
            $savedPassengers = [];
        }

        return view('user.flights.hold', [
            'itineraryId'     => $itinerary,
            'itinerary'       => $itineraryData,
            'searchParams'    => $params,
            'totalAmount'     => (float) ($itineraryData['totalPrice'] ?? 0),
            'currency'        => $itineraryData['currency'] ?? 'AED',
            'savedPassengers' => $savedPassengers,
        ]);
    }

    public function processHold(Request $request, FlightService $flightService)
    {
        $validated = $request->validate([
            'itinerary_id'              => 'required|integer',
            // lead name fields are synced from passenger[0] via JS as hidden inputs
            'lead.title'                => 'nullable|string',
            'lead.first_name'           => 'nullable|string|max:60',
            'lead.last_name'            => 'nullable|string|max:60',
            'lead.email'                => 'required|email',
            'lead.phone'                => 'required|string|max:25',
            'passengers'                => 'required|array|min:1',
            'passengers.*.type'         => 'required|in:ADT,C06,INF',
            'passengers.*.title'        => 'required|string',
            'passengers.*.first_name'   => 'required|string|max:60',
            'passengers.*.last_name'    => 'required|string|max:60',
            'passengers.*.dob'          => 'nullable|date',
            'passengers.*.nationality'  => 'nullable|string|max:4',
            'passengers.*.passport_no'  => 'nullable|string|max:20',
            'passengers.*.passport_exp' => 'nullable|date',
            'passengers.*.save_profile' => 'nullable|in:1',
        ]);

        $results       = session('flight_search_results', []);
        $params        = session('flight_search_params', []);
        $itineraryId   = (int) $validated['itinerary_id'];
        $itineraryData = $results[$itineraryId] ?? null;

        if (!$itineraryData || empty($params)) {
            return redirect()->route('user.flights.index')
                ->with('notify_error', 'Flight selection expired. Please search again.');
        }

        // Save any passenger profiles requested (silently skip if table not yet migrated)
        try {
            foreach ($validated['passengers'] as $pax) {
                if (!empty($pax['save_profile'])) {
                    Auth::user()->savedPassengers()->updateOrCreate(
                        [
                            'passport_no' => $pax['passport_no'] ?? null,
                            'first_name'  => $pax['first_name'],
                            'last_name'   => $pax['last_name'],
                        ],
                        [
                            'title'        => $pax['title'],
                            'dob'          => $pax['dob'] ?? null,
                            'nationality'  => $pax['nationality'] ?? null,
                            'passport_exp' => $pax['passport_exp'] ?? null,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Could not save passenger profile: ' . $e->getMessage());
        }

        $totalAmount  = (float) ($itineraryData['totalPrice'] ?? 0);
        $currency     = $itineraryData['currency'] ?? 'AED';

        $bookingData = [
            'itinerary_id'    => $itineraryId,
            'from_airport'    => $params['from'] ?? null,
            'to_airport'      => $params['to'] ?? null,
            'departure_date'  => !empty($params['departure_date']) ? Carbon::parse($params['departure_date'])->format('Y-m-d') : null,
            'return_date'     => !empty($params['return_date'])    ? Carbon::parse($params['return_date'])->format('Y-m-d') : null,
            'adults'          => (int) ($params['adults'] ?? 1),
            'children'        => (int) ($params['children'] ?? 0),
            'infants'         => (int) ($params['infants'] ?? 0),
            'passengers_data' => ['lead' => $validated['lead'], 'passengers' => $validated['passengers']],
            'itinerary_data'  => $itineraryData,
            'search_request'  => session('flight_search_payload'),
            'search_response' => session('flight_search_response'),
            'total_amount'    => $totalAmount,
            'currency'        => $currency,
            'payment_method'  => 'hold',
            'source_market'   => $this->getSourceMarketFromIP(),
        ];

        $booking = $flightService->createBookingRecord($bookingData);

        // Create Sabre PNR (hold – TicketType 7TAW, no payment required)
        $pnrResult = $flightService->createSabrePnr($booking);

        if (!($pnrResult['success'] ?? false)) {
            $booking->update(['booking_status' => 'failed']);
            Log::error('Hold PNR creation failed', [
                'booking_id' => $booking->id,
                'result'     => $pnrResult,
            ]);
            $errMsg = $pnrResult['error'] ?? $pnrResult['message'] ?? 'Unable to create hold booking. Please try again.';
            return redirect()
                ->route('user.flights.hold', ['itinerary' => $itineraryId] + request()->query())
                ->with('notify_error', $errMsg);
        }

        $booking->update(['booking_status' => 'hold']);

        return redirect()->route('user.flights.hold.success', ['booking' => $booking->id]);
    }

    public function holdSuccess(int $booking)
    {
        try {
            $booking = B2bFlightBooking::findOrFail($booking);
            return view('user.flights.hold-success', compact('booking'));
        } catch (\Exception $e) {
            return redirect()->route('user.flights.index')->with('notify_error', 'Booking not found.');
        }
    }

    /* =====================================================================
       SAVED PASSENGERS (AJAX)
       ===================================================================== */

    public function getSavedPassengers()
    {
        $passengers = Auth::user()->savedPassengers()->orderBy('first_name')->get();
        return response()->json($passengers);
    }

    public function savePassenger(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string',
            'first_name'   => 'required|string|max:60',
            'last_name'    => 'required|string|max:60',
            'dob'          => 'nullable|date',
            'nationality'  => 'nullable|string|max:4',
            'passport_no'  => 'nullable|string|max:20',
            'passport_exp' => 'nullable|date',
        ]);

        $pax = Auth::user()->savedPassengers()->create($data);
        return response()->json(['success' => true, 'passenger' => $pax]);
    }

    protected function getSourceMarketFromIP(): string
    {
        try {
            $ip = request()->ip();
            $response = file_get_contents("https://ipinfo.io/{$ip}/json");
            $data = json_decode($response, true);

            if (isset($data['country'])) {
                return $data['country'];
            }
        } catch (\Exception $e) {
            // noop
        }

        return 'AE';
    }
}
