<?php

/**
 * One-off probe: DXB→KHI Sabre shop — dump baggageAllowanceDescs + provisionType B rows.
 * Usage: php scripts/probe-sabre-baggage.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = app(App\Http\Controllers\User\FlightController::class);
$reflection = new ReflectionClass($controller);

$tokenMethod = $reflection->getMethod('getSabreToken');
$tokenMethod->setAccessible(true);
$payloadMethod = $reflection->getMethod('buildSabrePayload');
$payloadMethod->setAccessible(true);
$httpMethod = $reflection->getMethod('sabreHttp');
$httpMethod->setAccessible(true);

$searchData = [
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => now()->addDays(14)->format('Y-m-d'),
    'adults' => 1,
    'children' => 0,
    'infants' => 0,
    'onward_cabin_class' => 'Economy',
];

try {
    $token = $tokenMethod->invoke($controller);
    $payload = $payloadMethod->invoke($controller, $searchData);

    $response = $httpMethod->invoke($controller)
        ->withToken($token)
        ->withHeaders(['Accept' => 'application/json'])
        ->post('https://api.cert.platform.sabre.com/v5/offers/shop', $payload);

    if (! $response->successful()) {
        fwrite(STDERR, "HTTP {$response->status()}: {$response->body()}\n");
        exit(1);
    }

    $grouped = $response->json('groupedItineraryResponse') ?? [];
    $allowanceDescs = $grouped['baggageAllowanceDescs'] ?? [];

    echo "=== Baggage request in payload ===\n";
    echo json_encode($payload['OTA_AirLowFareSearchRQ']['TravelPreferences']['Baggage'] ?? [], JSON_PRETTY_PRINT) . "\n\n";

    echo "=== baggageAllowanceDescs count: " . count($allowanceDescs) . " ===\n";
    echo json_encode(array_slice($allowanceDescs, 0, 8), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

    $samples = [];
    foreach ($grouped['itineraryGroups'] ?? [] as $group) {
        foreach ($group['itineraries'] ?? [] as $itinerary) {
            foreach ($itinerary['pricingInformation'] ?? [] as $pricing) {
                $fare = $pricing['fare'] ?? [];
                $carrier = $fare['validatingCarrierCode'] ?? '';
                foreach ($fare['passengerInfoList'] ?? [] as $pax) {
                    $paxInfo = $pax['passengerInfo'] ?? [];
                    foreach ($paxInfo['baggageInformation'] ?? [] as $bag) {
                        $type = strtoupper((string) ($bag['provisionType'] ?? ''));
                        if ($type !== 'B' && $type !== 'A') {
                            continue;
                        }
                        $ref = data_get($bag, 'allowance.ref');
                        $desc = collect($allowanceDescs)->firstWhere('id', $ref);
                        $samples[] = [
                            'carrier' => $carrier,
                            'provisionType' => $type,
                            'allowance_ref' => $ref,
                            'allowance_desc' => $desc,
                            'segments' => $bag['segments'] ?? [],
                        ];
                        if (count($samples) >= 6) {
                            break 5;
                        }
                    }
                }
            }
        }
    }

    echo "=== Sample baggageInformation rows (A=checked, B=cabin) ===\n";
    echo json_encode($samples, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
