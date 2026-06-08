<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Travelport\TravelportApiClient;
use App\Support\Travelport\TravelportFareRulesPresenter;
use App\Support\Travelport\TravelportSearchPresenter;

$parsed = (new TravelportApiClient())->lowFareSearch([
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
])['parsed'] ?? null;

$cards = TravelportSearchPresenter::toResultCards($parsed, []);
$client = new TravelportApiClient();

foreach ($cards as $card) {
    foreach ($card['fare_options'] ?? [] as $fare) {
        $basis = $fare['fare_basis'] ?? '';
        if (! in_array($basis, ['LLEOPAE1', 'WDLIT3AE', 'VOLP7AE1'], true)) {
            continue;
        }

        $req = $fare['travelport_fare_rule'] ?? null;
        $source = 'n/a';
        if (is_array($req)) {
            $rsp = $client->airFareRules($req);
            if (preg_match('/Source="([^"]+)"/i', (string) ($rsp['raw'] ?? ''), $m)) {
                $source = $m[1];
            }
        }

        $hasBrand = ! empty($fare['fare_brand']);
        $carrier = $card['validating_carrier'] ?? '';
        $tags = $fare['fare_tags'] ?? [];

        echo "{$basis} carrier={$carrier} brand=" . ($fare['fare_brand'] ?? '') . " ruleSource={$source} tags=" . json_encode($tags) . PHP_EOL;
    }
}
