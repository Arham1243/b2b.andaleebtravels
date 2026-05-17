<?php

namespace App\Services;

/**
 * Wraps supplier/API payloads so cancel_response always stores metadata + body.
 */
class BookingCancellationRecorder
{
    /**
     * @param  array<string, mixed>  $apiPayload  Raw supplier response (or structured note when no API call).
     * @return array<string, mixed>
     */
    public static function envelope(string $cancellationType, array $apiPayload, ?string $cancelledBy = null): array
    {
        return [
            'recorded_at' => now()->toIso8601String(),
            'cancellation_type' => $cancellationType,
            'cancelled_by' => $cancelledBy,
            'api_response' => $apiPayload,
        ];
    }
}
