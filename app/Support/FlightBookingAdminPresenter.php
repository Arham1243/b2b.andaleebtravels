<?php

namespace App\Support;

use App\Models\B2bFlightBooking;

final class FlightBookingAdminPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(B2bFlightBooking $booking): array
    {
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $fareRules = is_array($itinerary['fare_rules'] ?? null) ? $itinerary['fare_rules'] : [];
        $fareTags = is_array($itinerary['fare_tags'] ?? null) ? $itinerary['fare_tags'] : [];
        $baggage = is_array($itinerary['baggage_details'] ?? null) ? $itinerary['baggage_details'] : [];

        $fareBases = [];
        foreach ($fareRules['components'] ?? [] as $component) {
            if (! is_array($component)) {
                continue;
            }

            $basis = trim((string) ($component['fare_basis'] ?? ''));
            if ($basis === '') {
                continue;
            }

            $fareBases[] = flightFareBasisListingLabel($basis, $fareTags);
        }

        $fareBases = array_values(array_unique($fareBases));

        $baggageSummary = trim((string) ($baggage['summary'] ?? ($itinerary['baggage_notes'] ?? '')));

        $paymentRef = trim((string) ($booking->payment_reference ?? ''));
        if ($paymentRef === '') {
            $paymentRef = trim((string) ($booking->tabby_payment_id ?? ''));
        }

        $fareTypeTags = [];
        foreach ($fareTags as $tag) {
            $normalized = strtolower(trim((string) $tag));
            if ($normalized === '') {
                continue;
            }

            $fareTypeTags[] = match ($normalized) {
                'ndc' => 'NDC',
                'published' => 'Published',
                default => ucfirst($normalized),
            };
        }

        return [
            'trip_type' => $booking->return_date ? 'Round trip' : 'One way',
            'ticket_numbers' => FlightBookingTicketResolver::forBooking($booking),
            'fare_brand' => trim((string) ($itinerary['fare_brand'] ?? ($fareRules['fare_brand'] ?? ''))),
            'fare_basis_labels' => $fareBases,
            'fare_type_tags' => array_values(array_unique($fareTypeTags)),
            'validating_carrier' => strtoupper(trim((string) (
                $itinerary['validating_carrier']
                ?? ($fareRules['validating_carrier'] ?? '')
            ))),
            'booking_code' => strtoupper(trim((string) (
                $itinerary['booking_code']
                ?? data_get($itinerary, 'legs.0.segments.0.booking_code')
                ?? ''
            ))),
            'cabin' => trim((string) (
                data_get($itinerary, 'legs.0.segments.0.cabin_code')
                ?? data_get($fareRules, 'components.0.cabin')
                ?? ''
            )),
            'baggage_summary' => $baggageSummary,
            'base_price' => self::money($itinerary['basePrice'] ?? $itinerary['supplierBasePrice'] ?? null),
            'taxes' => self::money($itinerary['taxes'] ?? $itinerary['supplierTaxes'] ?? null),
            'supplier_base_price' => self::money($itinerary['supplierBasePrice'] ?? null),
            'supplier_taxes' => self::money($itinerary['supplierTaxes'] ?? null),
            'last_ticket_date' => trim((string) ($fareRules['last_ticket_display'] ?? '')),
            'payment_reference' => $paymentRef,
            'confirmation_email_sent_at' => $booking->confirmation_email_sent_at,
            'travelport_universal_locator' => $booking->isTravelport() ? $booking->travelportUniversalLocator() : '',
        ];
    }

    private static function money(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
