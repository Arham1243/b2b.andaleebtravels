<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = new App\Services\Travelport\TravelportApiClient();
$ref = new ReflectionClass($client);
$send = $ref->getMethod('sendRequest');
$send->setAccessible(true);

function runLfs($send, $client, string $label, string $modifiersExtra = ''): void
{
    $traceId = 'test_' . uniqid();
    $airNs = 'http://www.travelport.com/schema/air_v52_0';
    $comNs = 'http://www.travelport.com/schema/common_v52_0';

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
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:SearchAirLeg>
                <air:SearchOrigin><com:CityOrAirport Code="DXB"/></air:SearchOrigin>
                <air:SearchDestination><com:CityOrAirport Code="KHI"/></air:SearchDestination>
                <air:SearchDepTime PreferredTime="2026-06-18"/>
            </air:SearchAirLeg>
            <air:AirSearchModifiers>
                <air:PreferredProviders><com:Provider Code="1G"/></air:PreferredProviders>
                <air:PermittedCabins><com:CabinClass Type="Economy"/></air:PermittedCabins>
                {$modifiersExtra}
            </air:AirSearchModifiers>
            <com:SearchPassenger Code="ADT"/>
        </air:LowFareSearchReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    $result = $send->invoke($client, 'AirService', $soap);
    if (! ($result['success'] ?? false)) {
        echo "\n=== {$label} FAILED: " . ($result['error'] ?? 'unknown') . " ===\n";
        return;
    }
    $rsp = data_get($result['parsed'], 'Body.LowFareSearchRsp');
    if (! is_array($rsp)) {
        echo "\n=== {$label} NO RSP ===\n";
        return;
    }

    $brandNames = [];
    foreach (data_get($rsp, 'BrandList.Brand', []) as $b) {
        $a = $b['@attributes'] ?? $b;
        $brandNames[$a['BrandID'] ?? ''] = $a['Name'] ?? '';
    }

    $segByKey = [];
    foreach (data_get($rsp, 'AirSegmentList.AirSegment', []) as $s) {
        if (! is_array($s)) {
            continue;
        }
        $a = $s['@attributes'] ?? $s;
        $segByKey[$a['Key'] ?? ''] = $a;
    }

    $fareByKey = [];
    foreach (data_get($rsp, 'FareInfoList.FareInfo', []) as $f) {
        if (! is_array($f)) {
            continue;
        }
        $a = $f['@attributes'] ?? $f;
        $fareByKey[$a['Key'] ?? ''] = $a;
    }

    echo "\n=== {$label} ===\n";
    echo 'EK brands: ';
    foreach ($brandNames as $id => $name) {
        if (stripos($name, 'ECO') !== false || stripos($name, 'FLEX') !== false || stripos($name, 'SAVER') !== false) {
            echo "{$name}({$id}) ";
        }
    }
    echo PHP_EOL;

    $pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
    $pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];
    echo 'PP count: ' . count($pps) . PHP_EOL;

    foreach ($pps as $pp) {
        $pi = data_get($pp, 'AirPricingInfo');
        if (is_array($pi) && array_is_list($pi)) {
            $pi = $pi[0];
        }
        $opt = data_get($pi, 'FlightOptionsList.FlightOption.0.Option.0');
        $bis = data_get($opt, 'BookingInfo');
        $bis = is_array($bis) && array_is_list($bis) ? $bis : [$bis];
        if (($sa['Carrier'] ?? '') !== 'EK') {
            continue;
        }
        if (count($bis) !== 1) {
            continue;
        }
        $bia = $bis[0]['@attributes'] ?? $bis[0];
        $seg = $segByKey[$bia['SegmentRef'] ?? ''] ?? null;
        if (! $seg) {
            continue;
        }
        $sa = $seg['@attributes'] ?? $seg;
        $fn = trim((string) ($sa['FlightNumber'] ?? ''));
        $bid = '';
        foreach (data_get($rsp, 'FareInfoList.FareInfo', []) as $f) {
            $fa = $f['@attributes'] ?? $f;
            if (($fa['Key'] ?? '') === ($bia['FareInfoRef'] ?? '')) {
                $bid = data_get($f, 'Brand.@attributes.BrandID') ?? '';
                break;
            }
        }
        $fi = $fareByKey[$bia['FareInfoRef'] ?? ''] ?? [];
        echo '  EK' . $fn . '@' . substr($sa['DepartureTime'] ?? '', 11, 5) . ' | ' . ($pp['@attributes']['TotalPrice'] ?? '') . ' | ' . ($fi['FareBasis'] ?? '') . ' | ' . ($brandNames[$bid] ?? '-') . ' | class=' . ($bia['BookingCode'] ?? '') . PHP_EOL;
    }
}

runLfs($send, $client, 'Default');
runLfs($send, $client, 'MaxSolutions=200', '<air:MaxSolutions>200</air:MaxSolutions>');
runLfs($send, $client, 'Preferred EK', '<air:PreferredCarriers><com:Carrier Code="EK"/></air:PreferredCarriers>');
runLfs($send, $client, 'MaxSolutions + EK', '<air:MaxSolutions>200</air:MaxSolutions><air:PreferredCarriers><com:Carrier Code="EK"/></air:PreferredCarriers>');
