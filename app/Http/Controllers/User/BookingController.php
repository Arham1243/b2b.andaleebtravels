<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\B2bFlightBooking;
use App\Models\B2bHotelBooking;
use App\Services\BookingCancellationNotifier;
use App\Services\BookingCancellationRecorder;
use App\Services\BookingWalletRefundService;
use App\Services\FlightService;
use App\Services\HotelService;
use App\Support\BookingCancellationEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingWalletRefundService $bookingWalletRefundService,
    ) {}

    public function index()
    {
        return redirect()->route('user.bookings.flights');
    }

    public function flights(Request $request)
    {
        $query = B2bFlightBooking::where('b2b_vendor_id', Auth::id())
            ->orderByDesc('created_at');

        $status = $request->query('status', 'all');
        if ($status && $status !== 'all') {
            if ($status === 'confirmed') {
                $query->where(function ($q) {
                    $q->whereIn('booking_status', ['confirmed', 'completed'])
                        ->orWhere(function ($q2) {
                            $q2->where('booking_status', 'hold')->where('payment_status', 'paid');
                        });
                });
            } elseif ($status === 'hold') {
                $query->where('booking_status', 'hold')->where('payment_status', '!=', 'paid');
            } else {
                $query->where('booking_status', $status);
            }
        }

        $search = $request->query('search', '');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhere('sabre_record_locator', 'like', "%{$search}%")
                  ->orWhere('from_airport', 'like', "%{$search}%")
                  ->orWhere('to_airport', 'like', "%{$search}%");
            });
        }

        $flightBookings = $query->paginate(10)->withQueryString();
        $counts         = $this->bookingCounts();

        return view('user.bookings.flights', compact('flightBookings', 'counts', 'status', 'search'));
    }

    public function flightDetail(int $id, FlightService $flightService)
    {
        $booking = B2bFlightBooking::where('b2b_vendor_id', Auth::id())->findOrFail($id);
        $booking->reconcileStatusAfterHoldPayment();

        if ($booking->isSabre() && $booking->hasAirlinePnr()) {
            $flightService->syncSabreTicketNumbersIfMissing($booking);
            $booking->refresh();
        }

        $counts  = $this->bookingCounts();
        $cancellation = BookingCancellationEligibility::forFlight($booking);
        $ticketDetails = $flightService->resolveTicketDetails($booking);

        return view('user.bookings.flight-detail', compact('booking', 'counts', 'cancellation', 'ticketDetails'));
    }

    public function hotels(Request $request)
    {
        $query = B2bHotelBooking::where('b2b_vendor_id', Auth::id())
            ->orderByDesc('created_at');

        $status = $request->query('status', 'all');
        if ($status && $status !== 'all') {
            if ($status === 'confirmed') {
                $query->whereIn('booking_status', ['confirmed', 'completed']);
            } else {
                $query->where('booking_status', $status);
            }
        }

        $search = $request->query('search', '');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhere('hotel_name', 'like', "%{$search}%");
            });
        }

        $hotelBookings = $query->paginate(10)->withQueryString();
        $counts        = $this->bookingCounts();

        return view('user.bookings.hotels', compact('hotelBookings', 'counts', 'status', 'search'));
    }

    public function hotelDetail(int $id, HotelService $hotelService)
    {
        $booking = B2bHotelBooking::where('b2b_vendor_id', Auth::id())->findOrFail($id);
        $counts  = $this->bookingCounts();
        $cancellation = BookingCancellationEligibility::resolveForHotelPage($booking, $hotelService);

        return view('user.bookings.hotel-detail', compact('booking', 'counts', 'cancellation'));
    }

    private function bookingCounts(): array
    {
        $vid = Auth::id();
        return [
            'flights' => B2bFlightBooking::where('b2b_vendor_id', $vid)->count(),
            'hotels'  => B2bHotelBooking::where('b2b_vendor_id', $vid)->count(),
        ];
    }

    public function getCancellationCharges(Request $request, HotelService $hotelService)
    {
        $request->validate([
            'booking_id' => 'required|integer',
        ]);

        $booking = B2bHotelBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($request->booking_id);

        $response = $hotelService->getCancellationCharges($booking);
        $eligibility = BookingCancellationEligibility::forHotel($booking, $response);

        if (!($eligibility['can_cancel'] ?? false)) {
            return response()->view('user.bookings.partials.cancellation-unavailable', [
                'cancellation' => $eligibility,
            ], 422);
        }

        $cancelUrl = route('user.bookings.hotels.cancel', $booking->id);

        return view('user.bookings.partials.cancellation-charges', compact('booking', 'response', 'cancelUrl'));
    }

    public function cancelHotelBooking($id, HotelService $hotelService)
    {
        $booking = B2bHotelBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($id);

        if ($booking->booking_status === 'cancelled') {
            return back()->with('notify_error', 'Booking already cancelled.');
        }

        if ($booking->payment_status !== 'paid') {
            return back()->with('notify_error', 'Only paid bookings can be cancelled.');
        }

        DB::beginTransaction();

        try {
            $charges = $hotelService->getCancellationCharges($booking);
            BookingCancellationEligibility::assertYalagoCanCancel($booking, $charges);
            $cancelResponse = $hotelService->cancelYalagoBooking($booking, $charges);

            $before = $booking->only(['payment_status', 'booking_status']);
            $isRefundable = BookingCancellationEligibility::hotelIsRefundableForWalletRefund($booking, $charges);

            $update = [
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'vendor',
                'cancel_response' => BookingCancellationRecorder::envelope('hotel_yalago_cancel', $cancelResponse, 'vendor'),
            ];

            if ($refundedPayment = $this->bookingWalletRefundService->paymentStatusAfterCancellationRefund($before, $isRefundable)) {
                $update['payment_status'] = $refundedPayment;
            }

            $booking->update($update);

            $ledger = $this->bookingWalletRefundService->processAfterCancellation($booking->fresh(), $before, $isRefundable);

            DB::commit();

            app(BookingCancellationNotifier::class)->notifyHotelCancelled($booking->fresh());

            $message = 'Booking #' . $booking->booking_number . ' cancelled successfully.';
            if ($ledger !== null) {
                $message .= ' ' . number_format((float) $ledger->amount, 2) . ' AED credited to your wallet.';
            }

            return redirect()
                ->route('user.bookings.hotels')
                ->with('notify_success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Hotel booking cancellation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('notify_error', 'Unable to cancel booking: ' . $e->getMessage());
        }
    }

    public function cancelTboBooking(Request $request, HotelService $hotelService)
    {
        $request->validate([
            'booking_id' => 'required|integer',
        ]);

        $booking = B2bHotelBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($request->booking_id);

        if (($booking->supplier ?? '') !== 'tbo') {
            return back()->with('notify_error', 'Invalid booking supplier.');
        }

        if ($booking->booking_status === 'cancelled') {
            return back()->with('notify_error', 'Booking already cancelled.');
        }

        if ($booking->payment_status !== 'paid') {
            return back()->with('notify_error', 'Only paid bookings can be cancelled.');
        }

        DB::beginTransaction();

        try {
            BookingCancellationEligibility::assertTboCanCancel($booking);
            $cancelResponse = $hotelService->cancelTboBooking($booking);

            $before = $booking->only(['payment_status', 'booking_status']);
            $isRefundable = BookingCancellationEligibility::hotelIsRefundableForWalletRefund($booking);

            $update = [
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'vendor',
                'cancel_response' => BookingCancellationRecorder::envelope('hotel_tbo_cancel', $cancelResponse, 'vendor'),
            ];

            if ($refundedPayment = $this->bookingWalletRefundService->paymentStatusAfterCancellationRefund($before, $isRefundable)) {
                $update['payment_status'] = $refundedPayment;
            }

            $booking->update($update);

            $ledger = $this->bookingWalletRefundService->processAfterCancellation($booking->fresh(), $before, $isRefundable);

            DB::commit();

            app(BookingCancellationNotifier::class)->notifyHotelCancelled($booking->fresh());

            $message = 'Booking #' . $booking->booking_number . ' cancelled successfully.';
            if ($ledger !== null) {
                $message .= ' ' . number_format((float) $ledger->amount, 2) . ' AED credited to your wallet.';
            }

            return redirect()
                ->route('user.bookings.hotels')
                ->with('notify_success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('TBO booking cancellation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('notify_error', 'Unable to cancel booking: ' . $e->getMessage());
        }
    }

    public function releaseHold(int $id, FlightService $flightService)
    {
        $booking = B2bFlightBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($id);

        if ($booking->booking_status === 'cancelled') {
            return back()->with('notify_error', 'Booking is already cancelled.');
        }

        if ($booking->booking_status !== 'hold' || $booking->isPaid()) {
            return back()->with('notify_error', 'This action is only available for held bookings.');
        }

        if ($booking->isTravelport() && $booking->hasAirlinePnr() && $booking->travelportUniversalLocator() === '') {
            return back()->with('notify_error', 'Unable to release hold at the airline: Travelport cancel reference is missing. Please contact support.');
        }

        DB::beginTransaction();

        try {
            $cancelResponse = [];
            $cancellationType = 'flight_hold_release_local';

            if (! empty($booking->sabre_record_locator)) {
                if ($booking->isTravelport()) {
                    $cancelResponse = $flightService->cancelTravelportBooking($booking);
                    $cancellationType = 'flight_hold_release_travelport';
                } else {
                    $cancelResponse = $flightService->cancelSabreBooking($booking);
                    $cancellationType = 'flight_hold_release_sabre';
                }
            } else {
                $cancelResponse = [
                    'note' => 'No airline PNR on record; hold cleared locally only.',
                ];
            }

            $booking->update([
                'booking_status' => 'cancelled',
                'cancelled_at'   => now(),
                'cancelled_by'   => 'vendor_release',
                'cancel_response' => BookingCancellationRecorder::envelope($cancellationType, $cancelResponse, 'vendor_release'),
            ]);

            DB::commit();

            app(BookingCancellationNotifier::class)->notifyFlightHoldReleased($booking->fresh());

            $pnr = trim((string) ($booking->sabre_record_locator ?? ''));
            $successMsg = flightHoldReleaseSuccessMessage($booking);

            return redirect()
                ->route('user.bookings.flights')
                ->with('notify_success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Hold release failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('notify_error', 'Unable to release hold: ' . $e->getMessage());
        }
    }

    public function cancelFlightBooking(int $id, FlightService $flightService)
    {
        $booking = B2bFlightBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($id);

        if ($booking->booking_status === 'cancelled') {
            return back()->with('notify_error', 'Booking already cancelled.');
        }

        if ($booking->payment_status !== 'paid') {
            return back()->with('notify_error', 'Only paid bookings can be cancelled.');
        }

        DB::beginTransaction();

        try {
            BookingCancellationEligibility::assertFlightCanCancel($booking);
            $cancelResponse = $booking->isTravelport()
                ? $flightService->cancelTravelportBooking($booking)
                : $flightService->cancelSabreBooking($booking);

            $before = $booking->only(['payment_status', 'booking_status']);
            $isRefundable = BookingCancellationEligibility::flightIsRefundableForWalletRefund($booking);

            $update = [
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'vendor',
                'cancel_response' => BookingCancellationRecorder::envelope(
                    $booking->isTravelport() ? 'flight_travelport_cancel' : 'flight_sabre_cancel',
                    $cancelResponse,
                    'vendor'
                ),
            ];

            if ($refundedPayment = $this->bookingWalletRefundService->paymentStatusAfterCancellationRefund($before, $isRefundable)) {
                $update['payment_status'] = $refundedPayment;
            }

            $booking->update($update);

            $ledger = $this->bookingWalletRefundService->processAfterCancellation($booking->fresh(), $before, $isRefundable);

            DB::commit();

            app(BookingCancellationNotifier::class)->notifyFlightCancelled($booking->fresh());

            $message = 'Booking #' . $booking->booking_number . ' cancelled successfully.';
            if ($ledger !== null) {
                $message .= ' ' . number_format((float) $ledger->amount, 2) . ' AED credited to your wallet.';
            }

            return redirect()
                ->route('user.bookings.flights')
                ->with('notify_success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Flight booking cancellation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('notify_error', 'Unable to cancel booking: ' . $e->getMessage());
        }
    }
}
