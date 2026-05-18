<?php

namespace App\Services;

use App\Models\B2bFlightBooking;
use App\Models\B2bHotelBooking;
use App\Models\Config;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingCancellationNotifier
{
    protected string $adminEmail;

    public function __construct()
    {
        $config = Config::pluck('config_value', 'config_key')->toArray();
        $this->adminEmail = (string) (
            $config['ADMIN_NOTIFICATION_EMAIL']
            ?? $config['ADMINEMAIL']
            ?? 'info@andaleebtours.com'
        );
    }

    public function notifyHotelCancelled(B2bHotelBooking $booking): void
    {
        $detailUrl = route('user.bookings.hotels.detail', $booking->id);

        try {
            Mail::send('user.emails.hotel-booking-cancelled', [
                'booking' => $booking,
                'forAdmin' => false,
                'detailUrl' => $detailUrl,
            ], function ($message) use ($booking) {
                $message->to($booking->lead_email)
                    ->subject('Hotel booking cancelled - ' . $booking->booking_number);
            });
        } catch (Exception $e) {
            Log::error('Hotel cancellation email (user) failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            Mail::send('user.emails.hotel-booking-cancelled', [
                'booking' => $booking,
                'forAdmin' => true,
                'detailUrl' => $detailUrl,
            ], function ($message) use ($booking) {
                $message->to($this->adminEmail)
                    ->subject('[B2B] Hotel cancelled - ' . $booking->booking_number);
            });
        } catch (Exception $e) {
            Log::error('Hotel cancellation email (admin) failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyFlightCancelled(B2bFlightBooking $booking): void
    {
        $this->sendFlightMailPair($booking, 'flight-booking-cancelled', 'Flight booking cancelled - ');
    }

    public function notifyFlightHoldReleased(B2bFlightBooking $booking): void
    {
        $this->sendFlightMailPair($booking, 'flight-hold-released', 'Flight hold released - ');
    }

    protected function sendFlightMailPair(B2bFlightBooking $booking, string $viewBasename, string $subjectPrefix): void
    {
        $booking->loadMissing('vendor');
        $detailUrl = route('user.bookings.flights.detail', $booking->id);
        $lead = $booking->passengers_data['lead'] ?? [];
        $userEmail = filter_var($lead['email'] ?? '', FILTER_VALIDATE_EMAIL)
            ? $lead['email']
            : ($booking->vendor?->email);

        $vars = [
            'booking' => $booking,
            'forAdmin' => false,
            'detailUrl' => $detailUrl,
        ];

        if ($userEmail) {
            try {
                Mail::send("user.emails.{$viewBasename}", $vars, function ($message) use ($booking, $userEmail, $subjectPrefix) {
                    $message->to($userEmail)
                        ->subject($subjectPrefix . $booking->booking_number);
                });
            } catch (Exception $e) {
                Log::error('Flight notification email (user) failed', [
                    'booking_id' => $booking->id,
                    'view' => $viewBasename,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            Mail::send("user.emails.{$viewBasename}", [
                'booking' => $booking,
                'forAdmin' => true,
                'detailUrl' => $detailUrl,
            ], function ($message) use ($booking, $subjectPrefix) {
                $message->to($this->adminEmail)
                    ->subject('[B2B] ' . $subjectPrefix . $booking->booking_number);
            });
        } catch (Exception $e) {
            Log::error('Flight notification email (admin) failed', [
                'booking_id' => $booking->id,
                'view' => $viewBasename,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
