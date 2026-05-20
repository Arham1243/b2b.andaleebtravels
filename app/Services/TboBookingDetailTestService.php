<?php

namespace App\Services;

use App\Models\B2bHotelBooking;
use Illuminate\Support\Facades\Http;

class TboBookingDetailTestService
{
    private string $username = 'andaleebTest';

    private string $password = 'And@30524459';

    private string $baseUrl = 'http://api.tbotechnology.in/TBOHolidays_HotelAPI';

    public function run(B2bHotelBooking $booking): array
    {
        $clientRef = data_get($booking->booking_request, 'ClientReferenceId')
            ?? data_get($booking->booking_request, 'ClientReferenceNumber')
            ?? $booking->booking_number;

        $confirmation = $booking->yalago_booking_reference
            ?? data_get($booking->booking_response, 'BookingReferenceId')
            ?? data_get($booking->booking_response, 'ConfirmationNumber')
            ?? data_get($booking->booking_response, 'BookingRef');

        $bookingIdTbo = data_get($booking->booking_response, 'BookingId');

        $payloads = [
            'BookingDetail/ClientReferenceId' => ['ClientReferenceId' => $clientRef],
            'BookingDetail/ClientReferenceNumber' => ['ClientReferenceNumber' => $clientRef],
            'BookingDetail/ConfirmationNumber' => ['ConfirmationNumber' => $confirmation],
            'BookingDetail/BookingId' => ['BookingId' => $bookingIdTbo],
            'HotelBookingDetail/ClientReferenceId' => ['ClientReferenceId' => $clientRef],
            'GetBookingDetail/ClientReferenceId' => ['ClientReferenceId' => $clientRef],
        ];

        $endpoints = [
            'BookingDetail',
            'HotelBookingDetail',
            'GetBookingDetail',
            'BookingDetails',
        ];

        $results = [];

        foreach ($endpoints as $endpoint) {
            foreach ($payloads as $label => $body) {
                if (str_contains($label, $endpoint) === false && ! in_array($endpoint, ['BookingDetail', 'HotelBookingDetail'], true)) {
                    continue;
                }
                if ($endpoint === 'BookingDetail' && ! str_starts_with($label, 'BookingDetail/')) {
                    continue;
                }
                if ($endpoint === 'HotelBookingDetail' && ! str_starts_with($label, 'HotelBookingDetail/')) {
                    continue;
                }
                if ($endpoint === 'GetBookingDetail' && ! str_starts_with($label, 'GetBookingDetail/')) {
                    continue;
                }
                if ($endpoint === 'BookingDetails' && ! str_contains($label, 'ClientReferenceId')) {
                    $body = ['ClientReferenceId' => $clientRef];
                }

                $filteredBody = array_filter($body, fn ($v) => $v !== null && $v !== '');
                $url = "{$this->baseUrl}/{$endpoint}";

                try {
                    $response = Http::timeout(20)
                        ->withBasicAuth($this->username, $this->password)
                        ->post($url, $filteredBody);

                    $results[] = [
                        'endpoint' => $endpoint,
                        'label' => $label,
                        'url' => $url,
                        'payload' => $filteredBody,
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'success' => $response->successful(),
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'endpoint' => $endpoint,
                        'label' => $label,
                        'url' => $url,
                        'payload' => $filteredBody,
                        'status' => null,
                        'body' => $e->getMessage(),
                        'success' => false,
                        'error' => true,
                    ];
                }
            }
        }

        return [
            'meta' => [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'supplier' => $booking->supplier,
                'client_ref' => $clientRef,
                'confirmation' => $confirmation,
                'tbo_booking_id' => $bookingIdTbo,
            ],
            'results' => $results,
        ];
    }
}
