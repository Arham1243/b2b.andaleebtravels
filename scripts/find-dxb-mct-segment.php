<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards(
    (new App\Services\Travelport\TravelportApiClient())->lowFareSearch([
        'trip_type' => 'one_way',
        'from' => 'DXB',
        'to' => 'KHI',
        'departure_date' => '2026-06-18',
        'adults' => 1,
        'onward_cabin_class' => 'Economy',
    ])['parsed'] ?? null,
    []
);

foreach ($cards as $card) {
    foreach ($card['legs'] ?? [] as $leg) {
        foreach ($leg['segments'] ?? [] as $seg) {
            if (($seg['from'] ?? '') !== 'DXB' || ($seg['to'] ?? '') !== 'MCT') {
                continue;
            }

            $dep = \Carbon\Carbon::parse($seg['departure_datetime']);
            $arr = \Carbon\Carbon::parse($seg['arrival_datetime']);
            [$dh, $dm] = array_map('intval', explode(':', $seg['departure_clock']));
            [$ah, $am] = array_map('intval', explode(':', $seg['arrival_clock']));
            $clockDiff = ($ah * 60 + $am) - ($dh * 60 + $dm);
            if ($clockDiff < 0) {
                $clockDiff += 24 * 60;
            }

            echo json_encode([
                'carrier' => ($seg['carrier'] ?? '') . ($seg['flight_number'] ?? ''),
                'departure_time' => $seg['departure_time'] ?? '',
                'arrival_time' => $seg['arrival_time'] ?? '',
                'elapsedTime' => $seg['elapsedTime'] ?? null,
                'tz_diff_minutes' => $dep->diffInMinutes($arr, false),
                'clock_diff_minutes' => $clockDiff,
            ], JSON_PRETTY_PRINT) . PHP_EOL;
        }
    }
}
