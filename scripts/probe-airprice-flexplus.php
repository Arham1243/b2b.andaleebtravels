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
$segByKey = [];
foreach (data_get($rsp, 'AirSegmentList.AirSegment', []) as $s) {
    $a = $s['@attributes'] ?? $s;
    $segByKey[$a['Key'] ?? ''] = $a;
}

$flexSeg = null;
$flexClass = 'K';
$pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
$pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];

$fareByKey = [];
foreach (data_get($rsp, 'FareInfoList.FareInfo', []) as $f) {
    if (! is_array($f)) {
        continue;
    }
    $fa = $f['@attributes'] ?? $f;
    $fareByKey[$fa['Key'] ?? ''] = $fa['FareBasis'] ?? '';
}

echo 'PP count: ' . count($pps) . PHP_EOL;

foreach ($pps as $pp) {
    if (! is_array($pp)) {
        continue;
    }
    $pi = $pp['AirPricingInfo'] ?? null;
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

    foreach ($bis as $biNode) {
        if (! is_array($biNode)) {
            continue;
        }
        $bi = $biNode['@attributes'] ?? $biNode;
        $seg = $segByKey[$bi['SegmentRef'] ?? ''] ?? null;
        if ($seg === null) {
            continue;
        }
        $sa = $seg['@attributes'] ?? $seg;
        $fn = trim((string) ($sa['FlightNumber'] ?? ''));
        $fb = $fareByKey[$bi['FareInfoRef'] ?? ''] ?? '';
        if (($sa['Carrier'] ?? '') === 'EK') {
            echo "debug PP: EK{$fn} basis={$fb}\n";
        }
        if (($sa['Carrier'] ?? '') !== 'EK' || $fn !== '600') {
            continue;
        }
        if ($fb === 'KLSOSAE1') {
            $flexSeg = $sa;
            $flexClass = $bi['BookingCode'] ?? 'K';
            break 2;
        }
    }
}

if ($flexSeg === null) {
    echo "No flex segment found\n";
    exit(1);
}

$ref = new ReflectionClass($client);
$send = $ref->getMethod('sendRequest');
$send->setAccessible(true);

foreach (['K', 'Y', 'R', 'X', 'L'] as $class) {
    echo "\n=== AirPrice class {$class} ===\n";
    runAirPrice($send, $client, $flexSeg, $class);
}

function runAirPrice($send, $client, array $flexSeg, string $flexClass): void
{
$traceId = 'test_' . uniqid();
$airNs = 'http://www.travelport.com/schema/air_v52_0';
$comNs = 'http://www.travelport.com/schema/common_v52_0';

$key = $flexSeg['Key'] ?? '';
$group = $flexSeg['Group'] ?? '0';
$dep = $flexSeg['DepartureTime'] ?? '';
$arr = $flexSeg['ArrivalTime'] ?? '';
$eq = $flexSeg['Equipment'] ?? '777';

$soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:AirPriceReq
            TraceId="{$traceId}"
            AuthorizedBy="Zeeshan"
            TargetBranch="P7250866"
            ReturnBrandedFares="true"
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:AirItinerary>
                <air:AirSegment
                    Key="{$key}"
                    Group="{$group}"
                    ProviderCode="1G"
                    Carrier="EK"
                    FlightNumber="600"
                    Origin="DXB"
                    Destination="KHI"
                    DepartureTime="{$dep}"
                    ArrivalTime="{$arr}"
                    ClassOfService="{$flexClass}"
                    Status="SS"
                    ETicketability="Yes"
                    Equipment="{$eq}"/>
            </air:AirItinerary>
            <air:AirPricingModifiers ETicketability="Required" FaresIndicator="AllFares"/>
            <com:SearchPassenger Code="ADT" BookingTravelerRef="traveler_1"/>
            <air:AirPricingCommand/>
        </air:AirPriceReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

$result = $send->invoke($client, 'AirService', $soap);
if (! ($result['success'] ?? false)) {
    echo 'AirPrice failed: ' . ($result['error'] ?? '') . PHP_EOL;
    exit(1);
}

$brandNames = [];
foreach (data_get($result['parsed'], 'Body.AirPriceRsp.BrandList.Brand', []) as $b) {
    $a = $b['@attributes'] ?? $b;
    $brandNames[$a['BrandID'] ?? ''] = $a['Name'] ?? '';
}

$solutions = data_get($result['parsed'], 'Body.AirPriceRsp.AirPriceResult.AirPricingSolution');
$solutions = is_array($solutions) && array_is_list($solutions) ? $solutions : [$solutions];

echo "AirPrice solutions: " . count($solutions) . PHP_EOL;
foreach ($solutions as $sol) {
    if (! is_array($sol)) {
        continue;
    }
    $pi = data_get($sol, 'AirPricingInfo');
    if (is_array($pi) && array_is_list($pi)) {
        $pi = $pi[0];
    }
    $total = $sol['@attributes']['TotalPrice'] ?? data_get($pi, '@attributes.TotalPrice') ?? '';
    $fb = data_get($pi, 'FareInfo.0.@attributes.FareBasis') ?? data_get($pi, 'FareInfo.@attributes.FareBasis') ?? '';
    if ($fb === '') {
        foreach (data_get($pi, 'FareInfo', []) as $fi) {
            $fa = $fi['@attributes'] ?? $fi;
            $fb = $fa['FareBasis'] ?? $fb;
        }
    }
    $bid = data_get($pi, 'FareInfo.0.Brand.@attributes.BrandID') ?? '';
    echo "  {$total} | {$fb} | " . ($brandNames[$bid] ?? '-') . PHP_EOL;
}

echo "\nEK brands in AirPrice:\n";
foreach ($brandNames as $id => $name) {
    if (stripos($name, 'FLEX') !== false || stripos($name, 'SAVER') !== false || stripos($name, 'PLUS') !== false) {
        echo "  {$name} ({$id})\n";
    }
}
