<?php

namespace App\Services;

use App\Models\B2bHotelBooking;
use Illuminate\Support\Facades\Http;

class TboBookingDetailTestService
{
    private string $username = 'andaleebTest';

    private string $password = 'And@30524459';

    private string $bookingDetailUrl = 'http://api.tbotechnology.in/TBOHolidays_HotelAPI/BookingDetail';

    /**
     * Fetch TBO booking detail for admin testing (ConfirmationNumber / BookingReferenceId).
     *
     * @return array{
     *     ok: bool,
     *     error: string|null,
     *     payload: array<string, mixed>|null,
     *     http_status: int|null,
     *     response: array<string, mixed>|string|null
     * }
     */
    public function fetch(B2bHotelBooking $booking): array
    {
        $reference = $booking->yalago_booking_reference
            ?? data_get($booking->booking_response, 'BookingReferenceId')
            ?? data_get($booking->booking_response, 'ConfirmationNumber')
            ?? data_get($booking->booking_response, 'BookingRef');

        if ($reference === null || trim((string) $reference) === '') {
            return [
                'ok' => false,
                'error' => 'Missing TBO confirmation / BookingReferenceId on this booking.',
                'payload' => null,
                'http_status' => null,
                'response' => null,
            ];
        }

        $reference = (string) $reference;
        $attempts = [
            ['ConfirmationNumber' => $reference],
            ['BookingReferenceId' => $reference],
        ];

        $last = null;

        foreach ($attempts as $payload) {
            try {
                $response = Http::timeout(20)
                    ->connectTimeout(10)
                    ->withBasicAuth($this->username, $this->password)
                    ->post($this->bookingDetailUrl, $payload);

                $body = $response->json();
                $statusCode = data_get($body, 'Status.Code');

                $last = [
                    'ok' => $response->successful() && (int) $statusCode === 200,
                    'error' => (int) $statusCode === 200
                        ? null
                        : (data_get($body, 'Status.Description') ?? 'TBO BookingDetail request failed.'),
                    'payload' => $payload,
                    'http_status' => $response->status(),
                    'response' => is_array($body) ? $body : $response->body(),
                ];

                if ($last['ok']) {
                    return $last;
                }
            } catch (\Throwable $e) {
                $last = [
                    'ok' => false,
                    'error' => $e->getMessage(),
                    'payload' => $payload,
                    'http_status' => null,
                    'response' => null,
                ];
            }
        }

        return $last ?? [
            'ok' => false,
            'error' => 'TBO BookingDetail could not be fetched.',
            'payload' => null,
            'http_status' => null,
            'response' => null,
        ];
    }

    /**
     * Probe multiple TBO detail endpoints (manual test page).
     */
    public function runProbe(B2bHotelBooking $booking): array
    {
        $clientRef = data_get($booking->booking_request, 'ClientReferenceId')
            ?? data_get($booking->booking_request, 'ClientReferenceNumber')
            ?? $booking->booking_number;

        $confirmation = $booking->yalago_booking_reference
            ?? data_get($booking->booking_response, 'BookingReferenceId')
            ?? data_get($booking->booking_response, 'ConfirmationNumber')
            ?? data_get($booking->booking_response, 'BookingRef');

        $payloads = [
            'ConfirmationNumber' => ['ConfirmationNumber' => $confirmation],
            'BookingReferenceId' => ['BookingReferenceId' => $confirmation],
            'ClientReferenceId' => ['ClientReferenceId' => $clientRef],
        ];

        $results = [];

        foreach ($payloads as $label => $body) {
            $filteredBody = array_filter($body, fn ($v) => $v !== null && $v !== '');

            if ($filteredBody === []) {
                continue;
            }

            try {
                $response = Http::timeout(20)
                    ->withBasicAuth($this->username, $this->password)
                    ->post($this->bookingDetailUrl, $filteredBody);

                $results[] = [
                    'endpoint' => 'BookingDetail',
                    'label' => $label,
                    'url' => $this->bookingDetailUrl,
                    'payload' => $filteredBody,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'success' => $response->successful() && (int) data_get($response->json(), 'Status.Code') === 200,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'endpoint' => 'BookingDetail',
                    'label' => $label,
                    'url' => $this->bookingDetailUrl,
                    'payload' => $filteredBody,
                    'status' => null,
                    'body' => $e->getMessage(),
                    'success' => false,
                    'error' => true,
                ];
            }
        }

        return [
            'meta' => [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'supplier' => $booking->supplier,
                'client_ref' => $clientRef,
                'confirmation' => $confirmation,
            ],
            'results' => $results,
        ];
    }
}
