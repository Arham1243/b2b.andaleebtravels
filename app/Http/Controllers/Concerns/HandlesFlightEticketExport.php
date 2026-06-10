<?php

namespace App\Http\Controllers\Concerns;

use App\Models\B2bFlightBooking;
use App\Services\FlightEticketPdf;
use App\Services\FlightService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HandlesFlightEticketExport
{
    protected function flightEticketExportResponse(
        B2bFlightBooking $booking,
        FlightService $flightService,
        Request $request,
    ): Response|StreamedResponse {
        $validated = $request->validate([
            'scope' => 'nullable|in:combined,separate',
            'fare' => 'nullable|in:with,without',
            'disposition' => 'nullable|in:download,inline',
            'ticket' => 'nullable|string|max:20',
        ]);

        $scope = $validated['scope'] ?? 'combined';
        $includeFare = ($validated['fare'] ?? 'with') === 'with';
        $disposition = $validated['disposition'] ?? 'download';
        $ticketNumber = isset($validated['ticket']) ? preg_replace('/\D+/', '', $validated['ticket']) : null;

        $ticketDetails = $flightService->resolveTicketDetails($booking);

        if ($disposition === 'inline') {
            return FlightEticketPdf::inline(
                $booking,
                $ticketDetails,
                $scope,
                $includeFare,
                $ticketNumber !== '' ? $ticketNumber : null,
            );
        }

        return FlightEticketPdf::download($booking, $ticketDetails, $scope, $includeFare);
    }
}
