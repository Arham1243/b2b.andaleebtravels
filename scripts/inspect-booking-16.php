<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$b = App\Models\B2bFlightBooking::find(16);
if (! $b) {
    echo "not found\n";
    exit(1);
}

$ticketNums = App\Support\SupplierFlightBookingDetailsPresenter::class;
// use reflection to call private method - skip, grep ticket_response instead

echo json_encode([
    'provider' => $b->provider,
    'booking_status' => $b->booking_status,
    'payment_status' => $b->payment_status,
    'ticket_status' => $b->ticket_status,
    'payment_method' => $b->payment_method,
    'payment_reference' => $b->payment_reference,
    'pnr' => $b->sabre_record_locator,
    'travelport_universal' => $b->travelportUniversalLocator(),
    'confirmation_email_sent_at' => optional($b->confirmation_email_sent_at)->toDateTimeString(),
    'itinerary_keys' => array_keys($b->itinerary_data ?? []),
    'fare_brand' => data_get($b->itinerary_data, 'fare_brand'),
    'fare_basis' => data_get($b->itinerary_data, 'fare_rules.components.0.fare_basis'),
    'validating_carrier' => data_get($b->itinerary_data, 'validating_carrier'),
    'booking_code' => data_get($b->itinerary_data, 'booking_code'),
    'baggage_notes' => data_get($b->itinerary_data, 'baggage_notes'),
    'fare_tags' => data_get($b->itinerary_data, 'fare_tags'),
    'basePrice' => data_get($b->itinerary_data, 'basePrice'),
    'taxes' => data_get($b->itinerary_data, 'taxes'),
    'supplierBasePrice' => data_get($b->itinerary_data, 'supplierBasePrice'),
    'supplierTaxes' => data_get($b->itinerary_data, 'supplierTaxes'),
    'last_ticket_display' => data_get($b->itinerary_data, 'fare_rules.last_ticket_display'),
    'ticket_response_keys' => is_array($b->ticket_response) ? array_keys($b->ticket_response) : [],
    'e_ticket' => data_get($b->ticket_response, 'eTicketNumber') ?? data_get($b->ticket_response, 'AirTicketRS.ETicketNumber'),
], JSON_PRETTY_PRINT) . PHP_EOL;
