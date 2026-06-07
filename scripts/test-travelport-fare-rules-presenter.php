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

$card = null;
foreach ($cards as $c) {
    if ((float) ($c['totalPrice'] ?? 0) === 515.0) {
        $card = $c;
        break;
    }
}

if ($card === null) {
    $card = $cards[0] ?? null;
}

if ($card === null) {
    echo "No cards\n";
    exit(1);
}

$fare = $card['fare_options'][0] ?? [];
echo "fare_brand summary: " . ($fare['fare_rules']['fare_brand'] ?? '') . PHP_EOL;
echo "refund_label: " . ($fare['fare_rules']['refund_label'] ?? '') . PHP_EOL;
echo "policy_sections: " . count($fare['fare_rules']['policy_sections'] ?? []) . PHP_EOL;
echo "components: " . count($fare['fare_rules']['components'] ?? []) . PHP_EOL;
echo "has travelport_fare_rule: " . (is_array($fare['travelport_fare_rule'] ?? null) ? 'yes' : 'no') . PHP_EOL;

$ruleRequest = $fare['travelport_fare_rule'] ?? null;
if (is_array($ruleRequest)) {
    $response = (new App\Services\Travelport\TravelportApiClient())->airFareRules($ruleRequest);
    $components = App\Support\Travelport\TravelportFareRulesResponseParser::toComponents(
        (string) ($response['raw'] ?? ''),
        $ruleRequest,
    );
    echo "full rule sections: " . count($components[0]['sections'] ?? []) . PHP_EOL;
    echo "first section: " . ($components[0]['sections'][0]['title'] ?? '') . PHP_EOL;
}
