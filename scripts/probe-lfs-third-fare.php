<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = new App\Services\Travelport\TravelportApiClient();
$ref = new ReflectionClass($client);
$send = $ref->getMethod('sendRequest');
$send->setAccessible(true);

function probeLfs($send, $client, string $label, string $reqAttrs = '', string $modifiersInner = ''): void
{
    $traceId = 'probe_' . uniqid();
    $airNs = 'http://www.travelport.com/schema/air_v52_0';
    $comNs = 'http://www.travelport.com/schema/common_v52_0';

    $defaultModifiers = <<<XML
                <air:PreferredProviders><com:Provider Code="1G"/></air:PreferredProviders>
                <air:PermittedCabins><com:CabinClass Type="Economy"/></air:PermittedCabins>
                <air:FlightType MaxConnections="1"/>
XML;

    $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:LowFareSearchReq
            TraceId="{$traceId}"
            AuthorizedBy="Zeeshan"
            TargetBranch="P7250866"
            ReturnBrandedFares="true"
            ReturnUpsellFare="true"
            {$reqAttrs}
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:SearchAirLeg>
                <air:SearchOrigin><com:CityOrAirport Code="DXB"/></air:SearchOrigin>
                <air:SearchDestination><com:CityOrAirport Code="KHI"/></air:SearchDestination>
                <air:SearchDepTime PreferredTime="2026-06-18"/>
            </air:SearchAirLeg>
            <air:AirSearchModifiers>
                {$modifiersInner}
                {$defaultModifiers}
            </air:AirSearchModifiers>
            <com:SearchPassenger Code="ADT"/>
        </air:LowFareSearchReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    $result = $send->invoke($client, 'AirService', $soap);
    if (! ($result['success'] ?? false)) {
        echo "\n=== {$label} ERROR ===\n" . ($result['error'] ?? 'unknown') . "\n";
        return;
    }

    $rsp = data_get($result['parsed'], 'Body.LowFareSearchRsp');
    $brandNames = [];
    foreach (data_get($rsp, 'BrandList.Brand', []) as $b) {
        $a = $b['@attributes'] ?? $b;
        if (($a['Carrier'] ?? '') === 'EK') {
            $brandNames[$a['BrandID'] ?? ''] = $a['Name'] ?? '';
        }
    }

    $segByKey = [];
    foreach (data_get($rsp, 'AirSegmentList.AirSegment', []) as $s) {
        $a = $s['@attributes'] ?? $s;
        $segByKey[$a['Key'] ?? ''] = $a;
    }
    $fareByKey = [];
    foreach (data_get($rsp, 'FareInfoList.FareInfo', []) as $f) {
        $a = $f['@attributes'] ?? $f;
        $fareByKey[$a['Key'] ?? ''] = $a['FareBasis'] ?? '';
    }

    echo "\n=== {$label} ===\n";
    echo 'EK brands: ' . implode(', ', $brandNames) . PHP_EOL;

    $pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
    $pps = is_array($pps) && array_is_list($pps) ? $pps : ($pps ? [$pps] : []);
    echo 'Price points: ' . count($pps) . PHP_EOL;

    $ek600 = [];
    foreach ($pps as $pp) {
        $pi = data_get($pp, 'AirPricingInfo');
        if (is_array($pi) && array_is_list($pi)) {
            $pi = $pi[0];
        }
        $fo = data_get($pi, 'FlightOptionsList.FlightOption');
        if (is_array($fo) && array_is_list($fo)) {
            $fo = $fo[0];
        }
        $opt = data_get($fo, 'Option');
        if (is_array($opt) && array_is_list($opt)) {
            $opt = $opt[0];
        }
        $bis = data_get($opt, 'BookingInfo');
        $bis = is_array($bis) && array_is_list($bis) ? $bis : [$bis];
        if (count($bis) !== 1) {
            continue;
        }
        $bi = $bis[0]['@attributes'] ?? $bis[0];
        $seg = $segByKey[$bi['SegmentRef'] ?? ''] ?? null;
        if (! $seg) {
            continue;
        }
        $sa = $seg['@attributes'] ?? $seg;
        if (($sa['Carrier'] ?? '') !== 'EK' || trim($sa['FlightNumber'] ?? '') !== '600') {
            continue;
        }
        $fb = $fareByKey[$bi['FareInfoRef'] ?? ''] ?? '';
        $ek600[] = ($pp['@attributes']['TotalPrice'] ?? '') . ' ' . $fb;
    }
    echo 'EK600 fares (' . count($ek600) . '): ' . implode(' | ', $ek600) . PHP_EOL;

    // presenter count
    $cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($result['parsed'], []);
    foreach ($cards as $card) {
        $s0 = $card['legs'][0]['segments'][0] ?? [];
        if (($s0['carrier'] ?? '') !== 'EK' || ($s0['flight_number'] ?? '') !== '600') {
            continue;
        }
        echo 'Presenter opts: ' . count($card['fare_options'] ?? []) . PHP_EOL;
        foreach ($card['fare_options'] ?? [] as $fo) {
            echo '  ' . ($fo['fare_brand'] ?? '') . ' | ' . ($fo['fare_basis'] ?? '') . PHP_EOL;
        }
    }
}

probeLfs($send, $client, 'Baseline (current)');
probeLfs($send, $client, 'MaxSolutions=200 first', '', '<air:MaxSolutions>200</air:MaxSolutions>');
probeLfs($send, $client, 'IncludeExtraSolutions', '', '<air:IncludeExtraSolutions>true</air:IncludeExtraSolutions>');
probeLfs($send, $client, 'MaxSolutions + IncludeExtraSolutions', '', '<air:MaxSolutions>200</air:MaxSolutions><air:IncludeExtraSolutions>true</air:IncludeExtraSolutions>');
probeLfs($send, $client, 'SolutionResult=true', 'SolutionResult="true"', '');
