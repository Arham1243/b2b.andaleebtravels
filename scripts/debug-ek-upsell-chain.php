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
])['parsed'] ?? null;

$rsp = data_get($raw, 'Body.LowFareSearchRsp');
$brands = data_get($rsp, 'BrandList.Brand');
$brands = is_array($brands) && array_is_list($brands) ? $brands : [$brands];

echo "=== All EK BrandList entries (full attrs) ===\n";
foreach ($brands as $b) {
    $a = $b['@attributes'] ?? $b;
    if (($a['Carrier'] ?? '') !== 'EK') {
        continue;
    }
    echo json_encode($a, JSON_PRETTY_PRINT) . "\n\n";
}

// Map brandId -> fare basis from FareInfo
$brandToBasis = [];
$fareList = data_get($rsp, 'FareInfoList.FareInfo');
$fareList = is_array($fareList) && array_is_list($fareList) ? $fareList : [$fareList];
foreach ($fareList as $f) {
    $fa = $f['@attributes'] ?? $f;
    if (($fa['Origin'] ?? '') !== 'DXB' || ($fa['Destination'] ?? '') !== 'KHI') {
        continue;
    }
    $bid = data_get($f, 'Brand.@attributes.BrandID') ?? data_get($f, 'Brand.BrandID') ?? '';
    if ($bid !== '') {
        $brandToBasis[$bid][] = $fa['FareBasis'] ?? '';
    }
}
echo "=== BrandID -> FareBasis (DXB-KHI) ===\n";
print_r($brandToBasis);

// Try LFS without cabin restriction
$client = new App\Services\Travelport\TravelportApiClient();
$ref = new ReflectionClass($client);
$send = $ref->getMethod('sendRequest');
$send->setAccessible(true);

$traceId = 'test_' . uniqid();
$targetBranch = 'P7250866';
$airNs = 'http://www.travelport.com/schema/air_v52_0';
$comNs = 'http://www.travelport.com/schema/common_v52_0';

$soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:LowFareSearchReq
            TraceId="{$traceId}"
            AuthorizedBy="Zeeshan"
            TargetBranch="{$targetBranch}"
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
            </air:AirSearchModifiers>
            <com:SearchPassenger Code="ADT"/>
        </air:LowFareSearchReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

$result = $send->invoke($client, 'AirService', $soap);
$rsp2 = data_get($result['parsed'], 'Body.LowFareSearchRsp');

$pps = data_get($rsp2, 'AirPricePointList.AirPricePoint');
$pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];

$segList = data_get($rsp2, 'AirSegmentList.AirSegment');
$segList = is_array($segList) && array_is_list($segList) ? $segList : [$segList];
$segByKey = [];
foreach ($segList as $s) {
    $k = $s['@attributes']['Key'] ?? $s['Key'] ?? '';
    if ($k !== '') {
        $segByKey[$k] = $s;
    }
}

$fareByKey = [];
foreach (data_get($rsp2, 'FareInfoList.FareInfo', []) as $f) {
    if (! is_array($f)) {
        continue;
    }
    $k = $f['@attributes']['Key'] ?? $f['Key'] ?? '';
    if ($k !== '') {
        $fareByKey[$k] = $f;
    }
}

echo "\n=== EK600 fares WITHOUT cabin modifier ===\n";
foreach ($pps as $pp) {
    $pi = data_get($pp, 'AirPricingInfo');
    if (is_array($pi) && array_is_list($pi)) {
        $pi = $pi[0];
    }
    $opt = data_get($pi, 'FlightOptionsList.FlightOption.0.Option.0');
    $bis = data_get($opt, 'BookingInfo');
    $bis = is_array($bis) && array_is_list($bis) ? $bis : [$bis];
    if (count($bis) !== 1) {
        continue;
    }
    $biA = $bis[0]['@attributes'] ?? $bis[0];
    $seg = $segByKey[$biA['SegmentRef'] ?? ''] ?? null;
    if (! $seg) {
        continue;
    }
    $sa = $seg['@attributes'] ?? $seg;
    if (($sa['Carrier'] ?? '') !== 'EK' || ($sa['FlightNumber'] ?? '') !== '600') {
        continue;
    }
    $fb = $fareByKey[$biA['FareInfoRef'] ?? '']['@attributes']['FareBasis'] ?? '';
    echo ($pp['@attributes']['TotalPrice'] ?? '') . ' | ' . $fb . PHP_EOL;
}

echo "\nTotal PP count no cabin: " . count($pps) . "\n";
