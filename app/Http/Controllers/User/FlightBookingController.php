<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\B2bFlightBooking;
use App\Models\B2bSavedPassenger;
use App\Models\B2bWalletLedger;
use App\Support\CountryCatalog;
use App\Support\SupportContact;
use App\Support\FlightPassengerDobValidator;
use App\Support\FlightPassengerFareLinesPresenter;
use App\Support\Travelport\TravelportHoldPayloadBuilder;
use App\Support\WalletLedgerDescription;
use App\Services\FlightBookingConfirmationNotifier;
use App\Services\FlightService;
use App\Services\Travelport\TravelportBookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FlightBookingController extends Controller
{
    public function checkout(Request $request, int $itinerary)
    {
        $results = session('flight_search_results', []);
        $params = session('flight_search_params', []);
        $fareIndex = max(0, (int) $request->query('fare', 0));

        $itineraryData = $this->resolveItineraryFare($results, $itinerary, $fareIndex);

        if (!$itineraryData || empty($params)) {
            return redirect()->route('user.flights.index')
                ->with('notify_error', 'Please search for flights again.');
        }

        $itineraryData = $this->refreshTravelportItineraryForDisplay(
            $itineraryData,
            $params,
            $itinerary,
            $fareIndex,
        );

        $isTravelport = strtolower((string) ($itineraryData['supplier'] ?? 'sabre')) === 'travelport';

        $totalAmount = (float) ($itineraryData['totalPrice'] ?? 0);
        $currency = $itineraryData['currency'] ?? 'AED';
        $walletBalance = (float) (Auth::user()->totalSpendableBalance() ?? 0);

        try {
            $savedPassengers = Auth::user()
                ->savedPassengers()
                ->orderBy('first_name')
                ->get()
                ->toArray();
        } catch (\Throwable $e) {
            $savedPassengers = [];
        }

        return view('user.flights.checkout', $this->passengerPageData([
            'itineraryId' => $itinerary,
            'selectedFareIndex' => $fareIndex,
            'itinerary' => $itineraryData,
            'searchParams' => $params,
            'totalAmount' => $totalAmount,
            'currency' => $currency,
            'walletBalance' => $walletBalance,
            'savedPassengers' => $savedPassengers,
            'requireTravelportDob' => $isTravelport,
        ]));
    }

    public function processPayment(Request $request, FlightService $flightService)
    {
        $validated = $request->validate(array_merge([
            'itinerary_id' => 'required|integer',
            'lead.email' => 'required|email',
            'lead.address' => 'nullable|string|max:500',

            'passengers' => 'required|array|min:1',
            'passengers.*.type' => 'required|in:ADT,C06,INF',
            'passengers.*.title' => 'required|string',
            'passengers.*.first_name' => 'required|string|max:60',
            'passengers.*.last_name' => 'required|string|max:60',
            'passengers.*.dob' => 'required|date|before_or_equal:today',
            'passengers.*.accompanying_adult' => 'nullable|integer|min:0',
            'passengers.*.nationality' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'passengers.*.issuing_country' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'passengers.*.passport_no' => 'nullable|string|max:20',
            'passengers.*.passport_exp' => 'nullable|date',
            'passengers.*.save_profile' => 'nullable|in:1',

            'payment_method' => 'required|in:payby,tabby,tamara,wallet',
            'use_wallet' => 'nullable|in:1',
            'wallet_amount' => 'nullable|numeric|min:0',
            'fare_option' => 'nullable|integer|min:0',
        ], $this->leadContactValidationRules($request)));

        $validated = $this->normalizePassengerCountries($validated);

        $p0 = $validated['passengers'][0] ?? null;
        if (!$p0 || ($p0['type'] ?? '') !== 'ADT') {
            return redirect()->back()
                ->withInput()
                ->with('notify_error', 'First passenger must be an adult.');
        }

        $results = session('flight_search_results', []);
        $params = session('flight_search_params', []);
        $this->validatePassportExpiryDates($validated['passengers'], $params);
        $this->validatePassengerDobDates($validated['passengers'], $params);

        $itineraryId = (int) $validated['itinerary_id'];
        $fareIndex = max(0, (int) ($validated['fare_option'] ?? 0));
        $itineraryData = $this->resolveItineraryFare($results, $itineraryId, $fareIndex);

        if (!$itineraryData || empty($params)) {
            return redirect()->route('user.flights.index')
                ->with('notify_error', 'Flight selection expired. Please search again.');
        }

        $isTravelport = strtolower((string) ($itineraryData['supplier'] ?? 'sabre')) === 'travelport';
        $validated['lead'] = $this->normalizeLeadContact($validated['lead'] ?? [], $isTravelport);

        $lead = [
            'title' => $p0['title'] ?? 'Mr',
            'first_name' => $p0['first_name'] ?? '',
            'last_name' => $p0['last_name'] ?? '',
            'email' => $validated['lead']['email'],
            'phone' => $validated['lead']['phone'] ?? '',
            'address' => $validated['lead']['address'] ?? null,
        ];

        if ($isTravelport) {
            $lead = array_merge($lead, array_intersect_key($validated['lead'], array_flip([
                'phone_dial_code',
                'phone_area_code',
                'phone_number',
                'phone_local',
            ])));
        }

        $this->validateTravelportPassengerAssociation($validated['passengers'], $params, $isTravelport);

        if ($isTravelport) {
            $revalidate = app(TravelportBookingService::class)->revalidateItinerary(
                $itineraryData,
                $params,
                ['passengers' => $validated['passengers']],
            );
            if (! ($revalidate['success'] ?? false)) {
                return redirect()->route('user.flights.index')
                    ->with('notify_error', $revalidate['error'] ?? 'Unable to revalidate fare. Please search again.');
            }

            $itineraryUpdates = is_array($revalidate['itinerary_updates'] ?? null)
                ? $revalidate['itinerary_updates']
                : [];
            if ($itineraryUpdates !== []) {
                $itineraryData = array_merge($itineraryData, $itineraryUpdates);
            }
        } else {
            [$sabreItineraryId, $sabreGroupIndex] = $this->resolveSabreItineraryLookup($itineraryData, $itineraryId);

            $revalidate = $flightService->revalidateItinerary(
                session('flight_search_response', []),
                $sabreItineraryId,
                (int) ($params['adults'] ?? 1),
                (int) ($params['children'] ?? 0),
                (int) ($params['infants'] ?? 0),
                $sabreGroupIndex,
                (int) ($itineraryData['sabre_pricing_index'] ?? 0),
            );

            if (!($revalidate['success'] ?? false)) {
                return redirect()->route('user.flights.index')
                    ->with('notify_error', $revalidate['error'] ?? 'Unable to revalidate flight itinerary. Please search again.');
            }
        }

        $this->persistSavedPassengerProfiles($validated['passengers']);

        $totalAmount = (float) ($itineraryData['totalPrice'] ?? 0);
        $currency = $itineraryData['currency'] ?? 'AED';
        $pricingFields = flightBookingPricingFields($itineraryData, $totalAmount);

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
                'lead' => $lead,
                'passengers' => $validated['passengers'],
            ],
            'itinerary_data' => $itineraryData,
            'search_request' => session('flight_search_payload'),
            'search_response' => $isTravelport
                ? (session('flight_search_responses')['travelport'] ?? null)
                : session('flight_search_response'),
            'provider' => normalizeFlightBookingProvider($itineraryData['supplier'] ?? null),
            'total_amount' => $pricingFields['total_amount'],
            'original_amount' => $pricingFields['original_amount'],
            'vendor_discount_amount' => $pricingFields['vendor_discount_amount'],
            'vendor_discount_snapshot' => $pricingFields['vendor_discount_snapshot'],
            'vendor_markup_amount' => $pricingFields['vendor_markup_amount'],
            'vendor_markup_snapshot' => $pricingFields['vendor_markup_snapshot'],
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
            $maxApplicable = min((float) $user->totalSpendableBalance(), $totalAmount);

            $walletDeduction = $requestedWalletAmount > 0
                ? min($requestedWalletAmount, $maxApplicable)
                : $maxApplicable;

            if ($walletDeduction <= 0) {
                return redirect()->back()
                    ->withInput()
                    ->with('notify_error', 'Insufficient wallet balance.');
            }

            $booking->update([
                'wallet_amount' => $walletDeduction,
            ]);
        }

        $remainingAmount = round($totalAmount - $walletDeduction, 2);

        if (($validated['payment_method'] ?? null) === 'wallet' || $remainingAmount <= 0) {
            if (!$useWallet) {
                return redirect()->back()
                    ->withInput()
                    ->with('notify_error', 'Please enable wallet to pay with wallet balance.');
            }

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
            $booking = B2bFlightBooking::where('b2b_vendor_id', Auth::id())->findOrFail($booking);
            $booking->reconcileStatusAfterHoldPayment();

            // Idempotent: do not re-verify payment or re-issue ticket on reload
            if ($booking->payment_status === 'paid' && $booking->hasVerifiedTicketIssue()) {
                return redirect()->route('user.flights.payment.success.view', ['booking' => $booking->id]);
            }

            $needsPaymentStep = ($booking->payment_status !== 'paid');

            if ($needsPaymentStep) {
                if ($booking->payment_method === 'wallet' && (float) $booking->wallet_amount >= (float) $booking->total_amount) {
                    $vendor = $booking->vendor;
                    if (! $vendor->canDebitAmount((float) $booking->wallet_amount)) {
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
                } elseif ($booking->payment_method === 'hold') {
                    return redirect()->route('user.flights.hold.confirm', ['booking' => $booking->id])
                        ->with('notify_error', 'Please complete payment for this hold booking first.');
                } else {
                    throw new \Exception('Invalid payment method: ' . ($booking->payment_method ?? 'unknown'));
                }

                if (!($verificationResult['success'] ?? false)) {
                    $booking->update([
                        'payment_status' => 'failed',
                        'booking_status' => 'failed',
                        'wallet_amount' => 0,
                    ]);

                    return redirect()->route('user.flights.payment.failed', ['booking' => $booking->id])
                        ->with('notify_error', 'Payment verification failed. Please contact support.');
                }

                if ((float) $booking->wallet_amount > 0) {
                    B2bWalletLedger::recordDebit(
                        $booking->b2b_vendor_id,
                        (float) $booking->wallet_amount,
                        WalletLedgerDescription::debitFlightPayment($booking),
                        B2bFlightBooking::class,
                        $booking->id
                    );
                }

                $booking->update([
                    'payment_status' => 'paid',
                    'payment_response' => $verificationResult['data'] ?? null,
                ]);
                $booking->reconcileStatusAfterHoldPayment();
            }

            $fulfillment = $flightService->fulfillPaidBooking($booking);

            if (! ($fulfillment['success'] ?? false)) {
                $errMsg = $fulfillment['error'] ?? 'Unable to complete your booking. Our team will contact you shortly.';

                if (($fulfillment['stage'] ?? '') === 'ticket') {
                    $errMsg = 'Booking confirmed but ticketing failed. Please contact support.';
                }

                return redirect()->route('user.flights.payment.failed', ['booking' => $booking->id])
                    ->with('notify_error', $errMsg);
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
            $booking = B2bFlightBooking::where('b2b_vendor_id', Auth::id())->findOrFail($booking);
            $booking->reconcileStatusAfterHoldPayment();

            if ($booking->payment_status !== 'paid' || $booking->ticket_status !== 'issued') {
                return redirect()->route('user.flights.index')
                    ->with('notify_error', 'This booking is not ready yet or payment is incomplete.');
            }

            app(FlightBookingConfirmationNotifier::class)->sendOnce($booking);
            $booking->refresh();

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
        $fareIndex = max(0, (int) $request->query('fare', 0));

        $itineraryData = $this->resolveItineraryFare($results, $itinerary, $fareIndex);

        if (!$itineraryData || empty($params)) {
            return redirect()->route('user.flights.index')
                ->with('notify_error', 'Please search for flights again.');
        }

        $itineraryData = $this->refreshTravelportItineraryForDisplay(
            $itineraryData,
            $params,
            $itinerary,
            $fareIndex,
        );

        try {
            $savedPassengers = Auth::user()
                ->savedPassengers()
                ->orderBy('first_name')
                ->get()
                ->toArray();
        } catch (\Throwable $e) {
            $savedPassengers = [];
        }

        return view('user.flights.hold', $this->passengerPageData([
            'itineraryId'     => $itinerary,
            'selectedFareIndex' => $fareIndex,
            'itinerary'       => $itineraryData,
            'searchParams'    => $params,
            'totalAmount'     => (float) ($itineraryData['totalPrice'] ?? 0),
            'currency'        => $itineraryData['currency'] ?? 'AED',
            'savedPassengers' => $savedPassengers,
        ]));
    }

    public function processHold(Request $request, FlightService $flightService)
    {
        $itineraryId = (int) $request->input('itinerary_id', 0);
        $fareIndex   = max(0, (int) $request->input('fare_option', 0));

        try {
            $validated = $request->validate(array_merge([
                'itinerary_id'              => 'required|integer',
                // lead name fields are synced from passenger[0] via JS as hidden inputs
                'lead.title'                => 'nullable|string',
                'lead.first_name'           => 'nullable|string|max:60',
                'lead.last_name'            => 'nullable|string|max:60',
                'lead.email'                => 'required|email',
                'passengers'                => 'required|array|min:1',
                'passengers.*.type'         => 'required|in:ADT,C06,INF',
                'passengers.*.title'        => 'required|string',
                'passengers.*.first_name'   => 'required|string|max:60',
                'passengers.*.last_name'    => 'required|string|max:60',
                'passengers.*.dob'          => 'required|date|before_or_equal:today',
                'passengers.*.accompanying_adult' => 'nullable|integer|min:0',
                'passengers.*.nationality'  => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
                'passengers.*.issuing_country' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
                'passengers.*.passport_no'  => 'nullable|string|max:20',
                'passengers.*.passport_exp' => 'nullable|date',
                'passengers.*.save_profile' => 'nullable|in:1',
                'fare_option' => 'nullable|integer|min:0',
            ], $this->leadContactValidationRules($request)), [
                'passengers.*.nationality.required' => 'Please select a nationality from the country list.',
                'passengers.*.nationality.size' => 'Please select a nationality from the country list.',
                'passengers.*.issuing_country.required' => 'Please select an issuing country from the country list.',
                'passengers.*.issuing_country.size' => 'Please select an issuing country from the country list.',
            ]);

            $validated = $this->normalizePassengerCountries($validated);

            $results       = session('flight_search_results', []);
            $params        = session('flight_search_params', []);
            $this->validatePassportExpiryDates($validated['passengers'], $params);
            $this->validatePassengerDobDates($validated['passengers'], $params);
            $itineraryId   = (int) $validated['itinerary_id'];
            $fareIndex     = max(0, (int) ($validated['fare_option'] ?? 0));
        } catch (ValidationException $e) {
            Log::warning('Hold checkout validation failed', [
                'itinerary_id' => $itineraryId,
                'fare_index'   => $fareIndex,
                'user_id'      => Auth::id(),
                'errors'       => $e->errors(),
            ]);

            throw $e;
        }

        $itineraryData = $this->resolveItineraryFare($results, $itineraryId, $fareIndex);

        if (!$itineraryData || empty($params)) {
            return redirect()->route('user.flights.index')
                ->with('notify_error', 'Flight selection expired. Please search again.');
        }

        $isTravelport = strtolower((string) ($itineraryData['supplier'] ?? 'sabre')) === 'travelport';
        $validated['lead'] = $this->normalizeLeadContact($validated['lead'] ?? [], $isTravelport);
        $this->validateTravelportPassengerAssociation($validated['passengers'], $params, $isTravelport);

        if ($isTravelport) {
            $revalidate = app(TravelportBookingService::class)->revalidateItinerary(
                $itineraryData,
                $params,
                ['passengers' => $validated['passengers']],
            );
            if (! ($revalidate['success'] ?? false)) {
                return $this->holdCheckoutRedirect($itineraryId, $fareIndex)
                    ->with('notify_error', $revalidate['error'] ?? 'Unable to revalidate fare. Please search again.');
            }

            $itineraryUpdates = is_array($revalidate['itinerary_updates'] ?? null)
                ? $revalidate['itinerary_updates']
                : [];
            if ($itineraryUpdates !== []) {
                $itineraryData = array_merge($itineraryData, $itineraryUpdates);
            }
        }

        $this->persistSavedPassengerProfiles($validated['passengers']);

        $totalAmount  = (float) ($itineraryData['totalPrice'] ?? 0);
        $currency     = $itineraryData['currency'] ?? 'AED';
        $pricingFields = flightBookingPricingFields($itineraryData, $totalAmount);

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
            'search_response' => $isTravelport
                ? (session('flight_search_responses')['travelport'] ?? null)
                : session('flight_search_response'),
            'provider'        => normalizeFlightBookingProvider($itineraryData['supplier'] ?? null),
            'total_amount'    => $pricingFields['total_amount'],
            'original_amount' => $pricingFields['original_amount'],
            'vendor_discount_amount' => $pricingFields['vendor_discount_amount'],
            'vendor_discount_snapshot' => $pricingFields['vendor_discount_snapshot'],
            'vendor_markup_amount' => $pricingFields['vendor_markup_amount'],
            'vendor_markup_snapshot' => $pricingFields['vendor_markup_snapshot'],
            'currency'        => $currency,
            'payment_method'  => 'hold',
            'source_market'   => $this->getSourceMarketFromIP(),
        ];

        $booking = $flightService->createBookingRecord($bookingData);

        $pnrResult = $isTravelport
            ? $flightService->createTravelportHoldPnr($booking)
            : $flightService->createSabrePnr($booking);

        if (!($pnrResult['success'] ?? false)) {
            $booking->update(['booking_status' => 'failed']);
            Log::error('Hold PNR creation failed', [
                'booking_id' => $booking->id,
                'result'     => $pnrResult,
            ]);
            $errMsg = $pnrResult['error'] ?? $pnrResult['message'] ?? 'Unable to create hold booking. Please try again.';
            return $this->holdCheckoutRedirect($itineraryId, $fareIndex)
                ->with('notify_error', $errMsg);
        }

        $booking->refresh();
        $booking->update(['booking_status' => 'hold']);

        return redirect()->route('user.flights.hold.success', ['booking' => $booking->id]);
    }

    public function holdSuccess(int $booking)
    {
        try {
            $booking = B2bFlightBooking::where('b2b_vendor_id', Auth::id())->findOrFail($booking);

            if (! $booking->isOnHold()) {
                return redirect()->route('user.bookings.flights.detail', $booking->id);
            }

            return view('user.flights.hold-success', compact('booking'));
        } catch (\Exception $e) {
            return redirect()->route('user.flights.index')->with('notify_error', 'Booking not found.');
        }
    }

    /* =====================================================================
       HOLD → CONFIRM (pay & issue ticket for an existing hold PNR)
       ===================================================================== */

    public function holdConfirmPage(int $booking)
    {
        $booking = B2bFlightBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($booking);

        if (! $booking->isOnHold()) {
            return redirect()->route('user.bookings.flights.detail', $booking->id)
                ->with('notify_error', 'This booking is not in a hold state.');
        }

        if (empty($booking->sabre_record_locator)) {
            return redirect()->route('user.bookings.flights.detail', $booking->id)
                ->with('notify_error', 'No PNR found for this booking. Cannot proceed to payment.');
        }

        $walletBalance = (float) (Auth::user()->totalSpendableBalance() ?? 0);

        return view('user.flights.hold-confirm', compact('booking', 'walletBalance'));
    }

    public function holdConfirmPay(Request $request, int $booking, FlightService $flightService)
    {
        $booking = B2bFlightBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($booking);

        if (! $booking->isOnHold()) {
            return back()->with('notify_error', 'This booking is not in a hold state.');
        }

        if (empty($booking->sabre_record_locator)) {
            return back()->with('notify_error', 'No PNR found for this booking. Cannot proceed to payment.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:payby,tabby,tamara,wallet',
            'use_wallet'     => 'nullable|in:1',
            'wallet_amount'  => 'nullable|numeric|min:0',
        ]);

        $totalAmount    = (float) $booking->total_amount;
        $useWallet      = !empty($validated['use_wallet']);
        $walletDeduction = 0;

        if ($useWallet) {
            $user                = Auth::user();
            $requestedAmount     = (float) ($validated['wallet_amount'] ?? 0);
            $maxApplicable       = min((float) $user->totalSpendableBalance(), $totalAmount);
            $walletDeduction     = $requestedAmount > 0
                ? min($requestedAmount, $maxApplicable)
                : $maxApplicable;

            if ($walletDeduction <= 0) {
                return back()->with('notify_error', 'Insufficient wallet balance.');
            }
        }

        $remainingAmount = round($totalAmount - $walletDeduction, 2);

        $booking->update([
            'wallet_amount'  => $walletDeduction > 0 ? $walletDeduction : 0,
        ]);

        if (($validated['payment_method'] ?? null) === 'wallet' || $remainingAmount <= 0) {
            if (!$useWallet) {
                return back()->with('notify_error', 'Please enable wallet to pay with wallet balance.');
            }

            $booking->update(['payment_method' => 'wallet']);
            return redirect()->route('user.flights.payment.success', ['booking' => $booking->id]);
        }

        $method = $validated['payment_method'];
        $booking->update(['payment_method' => $method]);

        try {
            $redirectUrl = $flightService->getRedirectUrl($booking, $method);
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            $booking->update(['payment_method' => 'hold']); // revert on failure
            Log::error('Hold confirm payment redirect failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
            return back()->with('notify_error', 'Unable to initiate payment. Please try again.');
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
            'passenger_type' => 'nullable|string|in:ADT,C06,CHD,INF',
            'dob'          => 'nullable|date',
            'nationality'  => 'nullable|string|size:2',
            'issuing_country' => 'nullable|string|size:2',
            'passport_no'  => 'nullable|string|max:20',
            'passport_exp' => 'nullable|date',
        ]);

        $data['passenger_type'] = B2bSavedPassenger::normalizeType($data['passenger_type'] ?? null);

        $pax = Auth::user()->savedPassengers()->create($data);
        return response()->json(['success' => true, 'passenger' => $pax]);
    }

    protected function passengerPageData(array $data = []): array
    {
        return array_merge([
            'countries' => CountryCatalog::forAutocomplete(),
            'defaultLeadContact' => SupportContact::defaultLeadContact(),
        ], $data);
    }

    /**
     * @param  list<array<string, mixed>>  $passengers
     */
    protected function persistSavedPassengerProfiles(array $passengers): void
    {
        try {
            foreach ($passengers as $pax) {
                if (empty($pax['save_profile'])) {
                    continue;
                }

                $passengerType = B2bSavedPassenger::normalizeType($pax['type'] ?? null);

                Auth::user()->savedPassengers()->updateOrCreate(
                    [
                        'passport_no' => $pax['passport_no'] ?? null,
                        'first_name' => $pax['first_name'],
                        'last_name' => $pax['last_name'],
                        'passenger_type' => $passengerType,
                    ],
                    [
                        'title' => $pax['title'],
                        'dob' => $pax['dob'] ?? null,
                        'nationality' => $pax['nationality'] ?? null,
                        'issuing_country' => $pax['issuing_country'] ?? null,
                        'passport_exp' => $pax['passport_exp'] ?? null,
                    ],
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Could not save passenger profile: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, string>
     */
    protected function leadContactValidationRules(Request $request): array
    {
        $hasTravelportPhone = trim((string) $request->input('lead.phone_local', '')) !== ''
            || trim((string) $request->input('lead.phone_dial_code', '')) !== '';

        if ($hasTravelportPhone) {
            return [
                'lead.phone_dial_code' => 'required|string|regex:/^[0-9]{1,4}$/',
                'lead.phone_local' => 'required|string|regex:/^[0-9]{5,15}$/',
                'lead.phone' => 'nullable|string|max:25',
            ];
        }

        return [
            'lead.phone' => 'required|string|max:25',
        ];
    }

    /**
     * @param  array<string, mixed>  $lead
     * @return array<string, mixed>
     */
    protected function normalizeLeadContact(array $lead, bool $isTravelport): array
    {
        if (trim((string) ($lead['phone_local'] ?? '')) === '' && trim((string) ($lead['phone_dial_code'] ?? '')) === '') {
            return $lead;
        }

        return TravelportHoldPayloadBuilder::normalizeLeadPhone($lead);
    }

    /**
     * @param  array<string, mixed>  $searchParams
     */
    protected function resolveLatestTravelDate(array $searchParams): ?Carbon
    {
        return FlightPassengerDobValidator::resolveLatestTravelDate($searchParams);
    }

    /**
     * @param  array<int, array<string, mixed>>  $passengers
     * @param  array<string, mixed>  $searchParams
     */
    protected function validatePassengerDobDates(array $passengers, array $searchParams): void
    {
        $errors = FlightPassengerDobValidator::validatePassengers($passengers, $searchParams);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $passengers
     * @param  array<string, mixed>  $searchParams
     */
    protected function validateTravelportPassengerAssociation(array $passengers, array $searchParams, bool $isTravelport): void
    {
        if (! $isTravelport) {
            return;
        }

        $adultCount = 0;
        $infantCount = 0;
        $errors = [];

        foreach ($passengers as $passenger) {
            if (! is_array($passenger)) {
                continue;
            }

            $type = strtoupper(trim((string) ($passenger['type'] ?? 'ADT')));
            if ($type === 'ADT') {
                $adultCount++;
            } elseif ($type === 'INF') {
                $infantCount++;
            }
        }

        $adultCount = max($adultCount, (int) ($searchParams['adults'] ?? 0));

        foreach ($passengers as $index => $passenger) {
            if (! is_array($passenger)) {
                continue;
            }

            $type = strtoupper(trim((string) ($passenger['type'] ?? 'ADT')));
            if ($type !== 'INF') {
                continue;
            }

            $adultIndex = (int) ($passenger['accompanying_adult'] ?? 0);
            if ($adultIndex < 0 || $adultIndex >= $adultCount) {
                $errors["passengers.{$index}.accompanying_adult"] = 'Select which adult this infant travels with.';
            }
        }

        foreach ($passengers as $index => $passenger) {
            if (! is_array($passenger)) {
                continue;
            }

            if (trim((string) ($passenger['passport_no'] ?? '')) === '') {
                $errors["passengers.{$index}.passport_no"] = 'Passport number is required for Travelport bookings.';
            }

            if (trim((string) ($passenger['passport_exp'] ?? '')) === '') {
                $errors["passengers.{$index}.passport_exp"] = 'Passport expiry is required for Travelport bookings.';
            }

            if (trim((string) ($passenger['dob'] ?? '')) === '') {
                $errors["passengers.{$index}.dob"] = 'Date of birth is required for Travelport bookings.';
            }
        }

        if ($infantCount > $adultCount) {
            $errors['passengers'] = 'Each infant must travel with an adult. Number of infants cannot exceed number of adults.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $passengers
     * @param  array<string, mixed>  $searchParams
     */
    protected function validatePassportExpiryDates(array $passengers, array $searchParams): void
    {
        $travelDate = $this->resolveLatestTravelDate($searchParams);
        $today = Carbon::today();
        $errors = [];

        foreach ($passengers as $index => $passenger) {
            $expiry = $passenger['passport_exp'] ?? null;
            if ($expiry === null || $expiry === '') {
                continue;
            }

            try {
                $expiryDate = Carbon::parse($expiry)->startOfDay();
            } catch (\Throwable $e) {
                $errors["passengers.{$index}.passport_exp"] = 'Enter a valid passport expiry date.';
                continue;
            }

            if ($expiryDate->lt($today)) {
                $errors["passengers.{$index}.passport_exp"] = 'Passport expiry cannot be in the past.';
                continue;
            }

            if ($travelDate && $expiryDate->lte($travelDate)) {
                $errors["passengers.{$index}.passport_exp"] = 'Passport must be valid after the travel date (' . $travelDate->format('d M Y') . ').';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function holdCheckoutRedirect(int $itineraryId, int $fareIndex = 0)
    {
        return redirect()->route('user.flights.hold', [
            'itinerary' => $itineraryId,
            'fare'      => $fareIndex,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function normalizePassengerCountries(array $validated): array
    {
        foreach ($validated['passengers'] as &$passenger) {
            if (isset($passenger['nationality'])) {
                $passenger['nationality'] = strtoupper((string) $passenger['nationality']);
            }
            if (isset($passenger['issuing_country'])) {
                $passenger['issuing_country'] = strtoupper((string) $passenger['issuing_country']);
            }
        }
        unset($passenger);

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $itineraryData
     * @return array{0: int, 1: int|null}
     */
    private function resolveSabreItineraryLookup(array $itineraryData, int $sessionItineraryId): array
    {
        $sabreItineraryId = (int) ($itineraryData['sabre_itinerary_id'] ?? $sessionItineraryId);
        $groupIndex = array_key_exists('sabre_group_index', $itineraryData)
            ? (int) $itineraryData['sabre_group_index']
            : null;

        return [$sabreItineraryId, $groupIndex];
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $results
     *
     * @return array<string, mixed>|null
     */
    private function resolveItineraryFare(array $results, int $itineraryId, int $fareIndex): ?array
    {
        $card = $results[$itineraryId] ?? null;
        if (! is_array($card)) {
            return null;
        }

        $options = $card['fare_options'] ?? null;
        if (! is_array($options) || $options === []) {
            return FlightPassengerFareLinesPresenter::syncItineraryFareTotals($card);
        }

        $selected = $options[$fareIndex] ?? $options[0];
        if (! is_array($selected)) {
            return FlightPassengerFareLinesPresenter::syncItineraryFareTotals($card);
        }

        $selectedSupplierTotal = round(
            (float) ($selected['supplierBasePrice'] ?? 0) + (float) ($selected['supplierTaxes'] ?? 0),
            2,
        );

        return FlightPassengerFareLinesPresenter::syncItineraryFareTotals(array_merge($card, [
            'totalPrice' => $selected['totalPrice'] ?? $card['totalPrice'] ?? null,
            'supplierPrice' => $selected['supplierPrice']
                ?? $selected['originalPrice']
                ?? ($selectedSupplierTotal > 0 ? $selectedSupplierTotal : null)
                ?? $card['supplierPrice']
                ?? null,
            'originalPrice' => $selected['originalPrice']
                ?? ($selectedSupplierTotal > 0 ? $selectedSupplierTotal : null)
                ?? $card['originalPrice']
                ?? null,
            'supplierBasePrice' => $selected['supplierBasePrice'] ?? $card['supplierBasePrice'] ?? null,
            'supplierTaxes' => $selected['supplierTaxes'] ?? $card['supplierTaxes'] ?? null,
            'basePrice' => $selected['basePrice'] ?? $card['basePrice'] ?? null,
            'taxes' => $selected['taxes'] ?? $card['taxes'] ?? null,
            'vendorDiscount' => $selected['vendorDiscount'] ?? $card['vendorDiscount'] ?? null,
            'vendorMarkup' => $selected['vendorMarkup'] ?? $card['vendorMarkup'] ?? null,
            'amountAfterDiscount' => $selected['amountAfterDiscount'] ?? $card['amountAfterDiscount'] ?? null,
            'vendorPricing' => $selected['vendorPricing'] ?? $card['vendorPricing'] ?? null,
            'currency' => $selected['currency'] ?? $card['currency'] ?? null,
            'fare_brand' => $selected['fare_brand'] ?? $card['fare_brand'] ?? null,
            'non_refundable' => $selected['non_refundable'] ?? $card['non_refundable'] ?? false,
            'baggage_notes' => $selected['baggage_notes'] ?? $card['baggage_notes'] ?? null,
            'baggage_details' => $selected['baggage_details'] ?? $card['baggage_details'] ?? [],
            'fare_rules' => $selected['fare_rules'] ?? $card['fare_rules'] ?? [],
            'fare_tags' => $selected['fare_tags'] ?? $card['fare_tags'] ?? [],
            'pricing_subsource' => $selected['pricing_subsource'] ?? $card['pricing_subsource'] ?? '',
            'validating_carrier' => $selected['validating_carrier'] ?? $card['validating_carrier'] ?? null,
            'governing_carriers' => $selected['governing_carriers'] ?? $card['governing_carriers'] ?? null,
            'supplier' => $selected['supplier'] ?? $card['supplier'] ?? 'sabre',
            'booking_code' => $selected['booking_code'] ?? $card['booking_code'] ?? null,
            'fare_basis' => $selected['fare_basis'] ?? $card['fare_basis'] ?? null,
            'travelport_segments' => $selected['travelport_segments'] ?? $card['travelport_segments'] ?? [],
            'travelport_fare_rule' => $selected['travelport_fare_rule'] ?? $card['travelport_fare_rule'] ?? null,
            'travelport_price_point_key' => $selected['travelport_price_point_key'] ?? $card['travelport_price_point_key'] ?? null,
            'travelport_pricing_index' => $selected['travelport_pricing_index'] ?? $card['travelport_pricing_index'] ?? 0,
            'travelport_air_price_solution' => $selected['travelport_air_price_solution']
                ?? $card['travelport_air_price_solution']
                ?? (! empty($selected['fare_basis']) && ! empty($selected['fare_brand']) ? true : null),
            'travelport_host_token' => $selected['travelport_host_token'] ?? $card['travelport_host_token'] ?? null,
            'sabre_pricing_index' => $selected['sabre_pricing_index'] ?? 0,
            'selected_fare_index' => $fareIndex,
            'passenger_fare_lines' => $selected['passenger_fare_lines'] ?? $card['passenger_fare_lines'] ?? [],
            'passenger_fare_warning' => $selected['passenger_fare_warning'] ?? $card['passenger_fare_warning'] ?? null,
        ]));
    }

    /**
     * @param  array<string, mixed>  $itineraryData
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function refreshTravelportItineraryForDisplay(
        array $itineraryData,
        array $params,
        int $itineraryId,
        int $fareIndex,
    ): array {
        $isTravelport = strtolower((string) ($itineraryData['supplier'] ?? 'sabre')) === 'travelport';
        $children = (int) ($params['children'] ?? 0);
        $childPricedAsAdult = ($itineraryData['passenger_fare_warning'] ?? '') === 'child_priced_as_adult'
            || FlightPassengerFareLinesPresenter::passengerFareWarning(
                is_array($itineraryData['passenger_fare_lines'] ?? null) ? $itineraryData['passenger_fare_lines'] : [],
            ) === 'child_priced_as_adult';

        if (! $isTravelport || ($children <= 0 && ! $childPricedAsAdult)) {
            return $itineraryData;
        }

        $params = TravelportHoldPayloadBuilder::ensureChildAgesInSearchData($params);
        session([
            'flight_search_params' => array_merge(session('flight_search_params', []), [
                'child_ages' => $params['child_ages'] ?? [],
                'child_age' => $params['child_age'] ?? null,
                'infant_ages' => $params['infant_ages'] ?? null,
                'infant_age' => $params['infant_age'] ?? null,
            ]),
        ]);

        $refresh = app(TravelportBookingService::class)->refreshFareBreakdown($itineraryData, $params);
        if (! ($refresh['success'] ?? false)) {
            return $itineraryData;
        }

        $updates = is_array($refresh['itinerary_updates'] ?? null) ? $refresh['itinerary_updates'] : [];
        if ($updates === []) {
            return $itineraryData;
        }

        $itineraryData = array_merge($itineraryData, $updates);
        $this->persistSessionItineraryFare($itineraryId, $fareIndex, $updates);

        return $itineraryData;
    }

    /**
     * @param  array<string, mixed>  $updates
     */
    private function persistSessionItineraryFare(int $itineraryId, int $fareIndex, array $updates): void
    {
        if ($updates === []) {
            return;
        }

        $results = session('flight_search_results', []);
        $card = $results[$itineraryId] ?? null;
        if (! is_array($card)) {
            return;
        }

        $fareKeys = [
            'passenger_fare_lines',
            'passenger_fare_warning',
            'supplierBasePrice',
            'supplierTaxes',
            'basePrice',
            'taxes',
            'totalPrice',
        ];
        $farePatch = array_intersect_key($updates, array_flip($fareKeys));

        $options = is_array($card['fare_options'] ?? null) ? $card['fare_options'] : [];
        if (isset($options[$fareIndex]) && is_array($options[$fareIndex]) && $farePatch !== []) {
            $options[$fareIndex] = array_merge($options[$fareIndex], $farePatch);
            $card['fare_options'] = $options;
        }

        $results[$itineraryId] = array_merge($card, $farePatch);
        session(['flight_search_results' => $results]);
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
