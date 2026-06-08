<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$raw = (new App\Services\Travelport\TravelportApiClient())->lowFareSearch([
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
]);

file_put_contents(__DIR__ . '/travelport-lfs-raw.xml', (string) ($raw['raw'] ?? ''));

$rsp = data_get($raw['parsed'], 'Body.LowFareSearchRsp');
$fareList = data_get($rsp, 'FareInfoList.FareInfo');
$fareList = is_array($fareList) && array_is_list($fareList) ? $fareList : [$fareList];
$fareByKey = [];

foreach ($fareList as $f) {
    if (! is_array($f)) {
        continue;
    }
    $k = $f['@attributes']['Key'] ?? $f['Key'] ?? '';
    if ($k !== '') {
        $fareByKey[$k] = $f;
    }
}

foreach ($fareList as $f) {
    $a = $f['@attributes'] ?? $f;
    if (($a['FareBasis'] ?? '') === 'LLEOPAE1') {
        echo "FareInfo LLEOPAE1:\n" . json_encode($a, JSON_PRETTY_PRINT) . "\n\n";
        echo "FareInfo keys: " . implode(', ', array_keys($f)) . "\n\n";
    }
}

$pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
$pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];

foreach ($pps as $pp) {
    if (! is_array($pp)) {
        continue;
    }

    $pricingInfos = $pp['AirPricingInfo'] ?? null;
    $pricingInfos = is_array($pricingInfos) && array_is_list($pricingInfos) ? $pricingInfos : [$pricingInfos];

    foreach ($pricingInfos as $pi) {
        if (! is_array($pi)) {
            continue;
        }

        $bis = data_get($pi, 'FlightOptionsList.FlightOption');
        $flightOptions = is_array($bis) && array_is_list($bis) ? $bis : [$bis];

        foreach ($flightOptions as $fo) {
            $opts = data_get($fo, 'Option');
            $opts = is_array($opts) && array_is_list($opts) ? $opts : [$opts];

            foreach ($opts as $opt) {
                $bis = data_get($opt, 'BookingInfo');
                $bis = is_array($bis) && array_is_list($bis) ? $bis : [$bis];

                foreach ($bis as $bi) {
                    if (! is_array($bi)) {
                        continue;
                    }

                    $fir = $bi['@attributes']['FareInfoRef'] ?? $bi['FareInfoRef'] ?? '';
                    $fb = $fareByKey[$fir]['@attributes']['FareBasis'] ?? $fareByKey[$fir]['FareBasis'] ?? '';

                    if ($fb === 'LLEOPAE1') {
                        echo "=== LLEOPAE1 price point ===\n";
                        echo 'AirPricePoint: ' . json_encode($pp['@attributes'] ?? [], JSON_PRETTY_PRINT) . "\n";
                        echo 'AirPricingInfo: ' . json_encode($pi['@attributes'] ?? [], JSON_PRETTY_PRINT) . "\n";
                        echo 'BookingInfo: ' . json_encode($bi['@attributes'] ?? $bi, JSON_PRETTY_PRINT) . "\n\n";
                    }
                }
            }
        }
    }
}

// Compare WDLIT3AE (published GDS)
foreach ($fareList as $f) {
    $a = $f['@attributes'] ?? $f;
    if (($a['FareBasis'] ?? '') === 'WDLIT3AE') {
        echo "FareInfo WDLIT3AE:\n" . json_encode($a, JSON_PRETTY_PRINT) . "\n\n";
    }
}

echo "=== PricingMethod by FareBasis ===\n";
$methods = [];
foreach ($pps as $pp) {
    $pricingInfos = $pp['AirPricingInfo'] ?? null;
    $pricingInfos = is_array($pricingInfos) && array_is_list($pricingInfos) ? $pricingInfos : [$pricingInfos];
    foreach ($pricingInfos as $pi) {
        if (! is_array($pi)) {
            continue;
        }
        $attrs = $pi['@attributes'] ?? $pi;
        $method = (string) ($attrs['PricingMethod'] ?? '');
        $flightOptions = data_get($pi, 'FlightOptionsList.FlightOption');
        $flightOptions = is_array($flightOptions) && array_is_list($flightOptions) ? $flightOptions : [$flightOptions];
        foreach ($flightOptions as $fo) {
            $opts = data_get($fo, 'Option');
            $opts = is_array($opts) && array_is_list($opts) ? $opts : [$opts];
            foreach ($opts as $opt) {
                $bis = data_get($opt, 'BookingInfo');
                $bis = is_array($bis) && array_is_list($bis) ? $bis : [$bis];
                foreach ($bis as $bi) {
                    $fir = $bi['@attributes']['FareInfoRef'] ?? $bi['FareInfoRef'] ?? '';
                    $fb = $fareByKey[$fir]['@attributes']['FareBasis'] ?? '';
                    if ($fb !== '') {
                        $methods[$fb] = $method;
                    }
                }
            }
        }
    }
}
asort($methods);
foreach ($methods as $fb => $m) {
    echo "{$fb} => {$m}\n";
}

// Raw XML snippet for LLEOPAE1
if (preg_match('/FareBasis="LLEOPAE1"[^>]*>/i', (string) ($raw['raw'] ?? ''), $m)) {
    echo "\nXML FareInfo tag: {$m[0]}\n";
}
$xml = (string) ($raw['raw'] ?? '');
$pos = strpos($xml, 'LLEOPAE1');
if ($pos !== false) {
    echo "\n=== XML context LLEOPAE1 ===\n" . substr($xml, max(0, $pos - 600), 2400) . "\n";
}
$pos2 = strpos($xml, 'WDLIT3AE');
if ($pos2 !== false) {
    echo "\n=== XML context WDLIT3AE ===\n" . substr($xml, max(0, $pos2 - 600), 2400) . "\n";
}
