<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = (int) ($argv[1] ?? 93);
$b = App\Models\B2bFlightBooking::find($id);
if (! $b) {
    echo "booking $id not found\n";
    exit(1);
}

$raw = (string) data_get($b->booking_response, 'raw', '');
echo "id=$id carrier=" . data_get($b->itinerary_data, 'validating_carrier', '?')
    . ' route=' . data_get($b->search_request, 'from', '?') . '-' . data_get($b->search_request, 'to', '?')
    . " pax={$b->adults}/{$b->children}/{$b->infants}\n";
echo 'booking_code=' . data_get($b->itinerary_data, 'booking_code', '?') . "\n";
echo 'cabin=' . data_get($b->search_request, 'onward_cabin_class', '?') . "\n";
echo 'raw_len=' . strlen($raw) . ' StoredFare=' . (str_contains($raw, 'StoredFare') ? 'yes' : 'no') . "\n";
echo 'AirPricingInfo=' . (preg_match('/AirPricingInfo/i', $raw) ? 'yes' : 'no') . "\n";
echo 'AirSolutionChangedInfo=' . (preg_match('/AirSolutionChangedInfo/i', $raw) ? 'yes' : 'no') . "\n";

if (preg_match_all('/<(?:[\w-]+:)?AirPricingInfo\b[^>]*>/i', $raw, $m)) {
    foreach ($m[0] as $tag) {
        echo 'API: ' . substr($tag, 0, 250) . "\n";
    }
}

$keys = App\Support\Travelport\TravelportHoldPricingInfoParser::extractReservationKeys(['raw' => $raw]);
echo 'extracted keys: ' . json_encode($keys) . "\n";
