<?php

namespace App\Services;

use App\Models\B2bFlightBooking;
use App\Support\FlightEticketPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

final class FlightEticketPdf
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function payloads(B2bFlightBooking $booking, array $ticketDetails, string $scope, bool $includeFare): array
    {
        if ($scope === 'separate') {
            return FlightEticketPresenter::separate($booking, $ticketDetails, $includeFare);
        }

        $combined = FlightEticketPresenter::combined($booking, $ticketDetails, $includeFare);

        return $combined !== null ? [$combined] : [];
    }

    public static function download(
        B2bFlightBooking $booking,
        array $ticketDetails,
        string $scope = 'combined',
        bool $includeFare = true,
    ): Response|StreamedResponse {
        self::assertCanExport($booking);

        $payloads = self::payloads($booking, $ticketDetails, $scope, $includeFare);
        if ($payloads === []) {
            abort(422, 'Ticket details are not available for this booking.');
        }

        if ($scope === 'separate' && count($payloads) > 1) {
            return self::zipDownload($payloads, $booking);
        }

        $payload = $payloads[0];

        return self::pdf($payload)
            ->download(self::filename($payload));
    }

    public static function inline(
        B2bFlightBooking $booking,
        array $ticketDetails,
        string $scope = 'combined',
        bool $includeFare = true,
        ?string $ticketNumber = null,
    ): Response {
        self::assertCanExport($booking);

        $payloads = self::payloads($booking, $ticketDetails, $scope, $includeFare);
        if ($payloads === []) {
            abort(422, 'Ticket details are not available for this booking.');
        }

        if ($ticketNumber !== null && $ticketNumber !== '') {
            $payloads = array_values(array_filter(
                $payloads,
                fn (array $payload) => (string) ($payload['filename_ticket_number'] ?? '') === preg_replace('/\D+/', '', $ticketNumber),
            ));
        }

        if ($payloads === []) {
            abort(404, 'Ticket not found for this booking.');
        }

        $payload = $payloads[0];

        return self::pdf($payload)->stream(self::filename($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function pdf(array $payload)
    {
        return Pdf::loadView('pdfs.flight-eticket', ['eticket' => $payload])
            ->setPaper('a4', 'portrait');
    }

    /**
     * @param  list<array<string, mixed>>  $payloads
     */
    private static function zipDownload(array $payloads, B2bFlightBooking $booking): StreamedResponse
    {
        $zipName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $booking->booking_number) . '-etickets.zip';

        return response()->streamDownload(function () use ($payloads) {
            $tmp = tempnam(sys_get_temp_dir(), 'eticket_zip_');
            if ($tmp === false) {
                return;
            }

            $zipPath = $tmp . '.zip';
            @unlink($tmp);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return;
            }

            foreach ($payloads as $payload) {
                $zip->addFromString(self::filename($payload), self::pdf($payload)->output());
            }

            $zip->close();
            readfile($zipPath);
            @unlink($zipPath);
        }, $zipName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function filename(array $payload): string
    {
        $number = preg_replace('/\D+/', '', (string) ($payload['filename_ticket_number'] ?? ''))
            ?: 'eticket';

        return $number . '.pdf';
    }

    private static function assertCanExport(B2bFlightBooking $booking): void
    {
        if (! $booking->hasIssuedTicketNumbers()) {
            abort(422, 'E-Ticket is not available until ticket numbers are issued.');
        }
    }
}
