<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FlightProviders\FlightProviderManager;
use App\Services\FlightProviders\SabreFlightProvider;
use App\Services\FlightProviders\TravelportFlightProvider;
use App\Support\FlightCabinPreference;

function auditSearch(array $searchData, string $label, array $base): void
{
    $manager = new FlightProviderManager([
        new SabreFlightProvider(),
        new TravelportFlightProvider(),
    ]);

    $mergedSearch = array_merge($base, $searchData);
    $out = $manager->search($mergedSearch);
    echo "\n=== {$label} ===\n";
    echo 'Total: ' . count($out['results']) . "\n";

    foreach (['sabre', 'travelport'] as $supplier) {
        $cards = array_values(array_filter($out['results'], fn ($r) => ($r['supplier'] ?? '') === $supplier));
        echo strtoupper($supplier) . ': ' . count($cards) . "\n";

        $nonDirect = 0;
        $wrongCabin = 0;
        $onwardCabin = FlightCabinPreference::normalizeUiLabel($mergedSearch['onward_cabin_class'] ?? 'Economy');
        $returnCabin = FlightCabinPreference::normalizeUiLabel($mergedSearch['return_cabin_class'] ?? $onwardCabin);
        $tripType = (string) ($mergedSearch['trip_type'] ?? 'one_way');
        $directOnly = ! empty($mergedSearch['direct_flight']);

        foreach ($cards as $card) {
            if ($directOnly) {
                foreach ($card['legs'] ?? [] as $leg) {
                    if (count($leg['segments'] ?? []) !== 1) {
                        $nonDirect++;
                        break;
                    }
                }
            }

            $fare = $card['fare_options'][0] ?? $card;
            if (! FlightCabinPreference::fareMatchesSearch($fare, $onwardCabin, $returnCabin, $tripType)) {
                $wrongCabin++;
                echo "  wrong cabin: {$card['validating_carrier']} basis=" . ($fare['fare_basis'] ?? '') . ' cabin=' . ($fare['cabin_code'] ?? '') . "\n";
            }
        }

        if ($directOnly && $nonDirect > 0) {
            echo "  direct filter leaks: {$nonDirect}\n";
        }
        if ($wrongCabin > 0) {
            echo "  cabin filter leaks: {$wrongCabin}\n";
        }
    }
}

$base = [
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'children' => 0,
    'infants' => 0,
    'nearby_airports' => false,
    'student_fare' => false,
    'onward_cabin_class' => 'Economy',
    'return_cabin_class' => 'Economy',
];

auditSearch(['trip_type' => 'one_way', 'direct_flight' => false, 'return_date' => null], 'OW Economy all stops', $base);
auditSearch(['trip_type' => 'one_way', 'direct_flight' => true, 'return_date' => null], 'OW Economy direct', $base);
auditSearch(['trip_type' => 'one_way', 'direct_flight' => false, 'return_date' => null, 'onward_cabin_class' => 'Business', 'return_cabin_class' => 'Business'], 'OW Business', $base);
auditSearch(['trip_type' => 'round_trip', 'direct_flight' => false, 'return_date' => '2026-06-25'], 'RT Economy', $base);
auditSearch(['trip_type' => 'round_trip', 'direct_flight' => true, 'return_date' => '2026-06-25'], 'RT Economy direct', $base);
