<?php

namespace App\Services;

use App\Models\B2bFlightBooking;
use App\Models\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FlightBookingConfirmationNotifier
{
    public function sendOnce(B2bFlightBooking $booking): bool
    {
        $booking->refresh();

        if ($booking->confirmation_email_sent_at) {
            return false;
        }

        if ($booking->payment_status !== 'paid' || ! $booking->hasVerifiedTicketIssue()) {
            return false;
        }

        $leadEmail = (string) data_get($booking->passengers_data, 'lead.email', '');
        $adminEmail = (string) Config::where('config_key', 'ADMIN_NOTIFICATION_EMAIL')->value('config_value');

        $detailUrl = route('user.bookings.flights.detail', $booking->id);

        $dataFor = function (string $intro, bool $forAdmin) use ($booking, $leadEmail, $detailUrl) {
            return [
                'booking' => $booking,
                'intro' => $intro,
                'leadEmail' => $leadEmail,
                'detailUrl' => $detailUrl,
                'forAdmin' => $forAdmin,
            ];
        };

        try {
            if ($leadEmail !== '' && filter_var($leadEmail, FILTER_VALIDATE_EMAIL)) {
                Mail::send(
                    'user.emails.flight-booking-confirmed',
                    $dataFor('Your flight is confirmed and your ticket has been issued. Your reservation summary is below.', false),
                    function ($message) use ($leadEmail, $booking) {
                        $message->to($leadEmail)->subject('Flight confirmed - ' . $booking->booking_number);
                    }
                );
            }

            if ($adminEmail !== '' && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                Mail::send(
                    'user.emails.flight-booking-confirmed',
                    $dataFor('A B2B flight booking was confirmed and ticketed. Summary for your records.', true),
                    function ($message) use ($adminEmail, $booking) {
                        $message->to($adminEmail)->subject('[B2B] Flight ticketed - ' . $booking->booking_number);
                    }
                );
            }

            $booking->update(['confirmation_email_sent_at' => now()]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Flight confirmation email failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
