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

$issues = [];

foreach ($cards as $card) {
    foreach ($card['legs'] ?? [] as $leg) {
        foreach ($leg['segments'] ?? [] as $seg) {
            $dep = \Carbon\Carbon::parse($seg['departure_datetime']);
            $arr = \Carbon\Carbon::parse($seg['arrival_datetime']);
            $tzDiff = (int) $dep->diffInMinutes($arr, false);
            $elapsed = (int) ($seg['elapsedTime'] ?? 0);

            [$dh, $dm] = array_map('intval', explode(':', $seg['departure_clock']));
            [$ah, $am] = array_map('intval', explode(':', $seg['arrival_clock']));
            $clockDiff = ($ah * 60 + $am) - ($dh * 60 + $dm);
            if ($clockDiff < 0) {
                $clockDiff += 24 * 60;
            }

            if ($elapsed > 0 && abs($elapsed - $tzDiff) > 2) {
                $issues[] = [
                    'type' => 'elapsed_vs_tz',
                    'route' => ($seg['from'] ?? '') . '->' . ($seg['to'] ?? ''),
                    'carrier' => ($seg['carrier'] ?? '') . ($seg['flight_number'] ?? ''),
                    'elapsed' => $elapsed,
                    'tz_diff' => $tzDiff,
                    'clock_diff' => $clockDiff,
                ];
            }

            if ($tzDiff > 0 && abs($tzDiff - $clockDiff) > 10 && abs($elapsed - $clockDiff) <= 2) {
                $issues[] = [
                    'type' => 'looks_like_clock_diff',
                    'route' => ($seg['from'] ?? '') . '->' . ($seg['to'] ?? ''),
                    'carrier' => ($seg['carrier'] ?? '') . ($seg['flight_number'] ?? ''),
                    'elapsed' => $elapsed,
                    'tz_diff' => $tzDiff,
                    'clock_diff' => $clockDiff,
                ];
            }
        }
    }
}

echo json_encode($issues, JSON_PRETTY_PRINT) . PHP_EOL;
echo 'Issues: ' . count($issues) . PHP_EOL;
