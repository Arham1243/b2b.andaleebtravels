<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$search = [
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-12',
    'adults' => 1,
    'children' => 1,
    'infants' => 1,
    'child_ages' => [8],
    'direct_flight' => false,
    'onward_cabin_class' => 'Economy',
];

$client = new App\Services\Travelport\TravelportApiClient();
$response = $client->lowFareSearch($search, true, true);
if (! ($response['success'] ?? false)) {
    echo 'LFS failed: ' . ($response['error'] ?? 'unknown') . "\n";
    exit(1);
}

$cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($response['parsed'], $search);
$card = $cards[0] ?? null;
if ($card === null) {
    echo "No cards\n";
    exit(1);
}

echo "=== LFS card lines ===\n";
foreach ($card['passenger_fare_lines'] ?? [] as $line) {
    printf("  %s x%d base=%.2f\n", $line['label'] ?? '?', (int) ($line['count'] ?? 0), (float) ($line['base_per_pax'] ?? 0));
}

$booking = new App\Services\Travelport\TravelportBookingService();
$refresh = $booking->refreshFareBreakdown($card, $search);

echo "\n=== Air Price refresh ===\n";
echo 'success=' . (($refresh['success'] ?? false) ? 'yes' : 'no') . "\n";
if (! ($refresh['success'] ?? false)) {
    echo 'error: ' . ($refresh['error'] ?? 'unknown') . "\n";
    exit(1);
}

$updates = $refresh['itinerary_updates'] ?? [];
foreach ($updates['passenger_fare_lines'] ?? [] as $line) {
    printf("  %s x%d base=%.2f\n", $line['label'] ?? '?', (int) ($line['count'] ?? 0), (float) ($line['base_per_pax'] ?? 0));
}

$adult = $child = null;
foreach ($updates['passenger_fare_lines'] ?? [] as $line) {
    if (($line['type_key'] ?? '') === 'adult') {
        $adult = (float) ($line['base_per_pax'] ?? 0);
    }
    if (($line['type_key'] ?? '') === 'child') {
        $child = (float) ($line['base_per_pax'] ?? 0);
    }
}
if ($adult !== null && $child !== null) {
    echo $child < $adult ? "\nOK: child < adult after Air Price\n" : "\nFAIL: child >= adult after Air Price\n";
}
