<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = new App\Services\Travelport\TravelportApiClient();
$ref = new ReflectionClass($client);
$send = $ref->getMethod('sendRequest');
$send->setAccessible(true);

function lfsXml(string $reqAttrs, string $modifiersBody): string
{
    $airNs = 'http://www.travelport.com/schema/air_v52_0';
    $comNs = 'http://www.travelport.com/schema/common_v52_0';
    $traceId = 'probe_' . uniqid();

    return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:LowFareSearchReq TraceId="{$traceId}" AuthorizedBy="Zeeshan" TargetBranch="P7250866"
            ReturnBrandedFares="true" ReturnUpsellFare="true" {$reqAttrs}
            xmlns:air="{$airNs}" xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:SearchAirLeg>
                <air:SearchOrigin><com:CityOrAirport Code="DXB"/></air:SearchOrigin>
                <air:SearchDestination><com:CityOrAirport Code="KHI"/></air:SearchDestination>
                <air:SearchDepTime PreferredTime="2026-06-18"/>
            </air:SearchAirLeg>
            <air:AirSearchModifiers>
{$modifiersBody}
            </air:AirSearchModifiers>
            <com:SearchPassenger Code="ADT"/>
        </air:LowFareSearchReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;
}

function ek600FromPricePoints(?array $rsp): array
{
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
    $brandNames = [];
    foreach (data_get($rsp, 'BrandList.Brand', []) as $b) {
        $a = $b['@attributes'] ?? $b;
        $brandNames[$a['BrandID'] ?? ''] = $a['Name'] ?? '';
    }

    $out = [];
    $pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
    $pps = is_array($pps) && array_is_list($pps) ? $pps : ($pps ? [$pps] : []);
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
        $seg = $segByKey[$bi['FareInfoRef'] ?? ''] ?? $segByKey[$bi['SegmentRef'] ?? ''] ?? null;
        $seg = $segByKey[$bi['SegmentRef'] ?? ''] ?? null;
        if (! $seg) {
            continue;
        }
        $sa = $seg['@attributes'] ?? $seg;
        if (($sa['Carrier'] ?? '') !== 'EK' || trim($sa['FlightNumber'] ?? '') !== '600') {
            continue;
        }
        $fb = $fareByKey[$bi['FareInfoRef'] ?? ''] ?? '';
        $out[] = ($pp['@attributes']['TotalPrice'] ?? '') . ' ' . $fb;
    }

    return $out;
}

function ek600FromSolutions(?array $rsp): array
{
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

    $out = [];
    $sols = data_get($rsp, 'AirPricingSolution');
    $sols = is_array($sols) && array_is_list($sols) ? $sols : ($sols ? [$sols] : []);
    foreach ($sols as $sol) {
        $pi = data_get($sol, 'AirPricingInfo');
        if (is_array($pi) && array_is_list($pi)) {
            $pi = $pi[0];
        }
        $refs = data_get($pi, 'FlightOptionsList.FlightOption.0.Option.0.BookingInfo');
        $refs = is_array($refs) && array_is_list($refs) ? $refs : [$refs];
        $isEk600 = false;
        $fb = '';
        foreach ($refs as $biNode) {
            $bi = $biNode['@attributes'] ?? $biNode;
            $seg = $segByKey[$bi['SegmentRef'] ?? ''] ?? null;
            if (! $seg) {
                continue;
            }
            $sa = $seg['@attributes'] ?? $seg;
            if (($sa['Carrier'] ?? '') === 'EK' && trim($sa['FlightNumber'] ?? '') === '600') {
                $isEk600 = true;
            }
            $fb = $fareByKey[$bi['FareInfoRef'] ?? ''] ?? $fb;
        }
        if ($isEk600) {
            $out[] = ($sol['@attributes']['TotalPrice'] ?? '') . ' ' . $fb;
        }
    }

    return $out;
}

$variants = [
    'baseline' => lfsXml('', '                <air:PreferredProviders><com:Provider Code="1G"/></air:PreferredProviders>
                <air:PermittedCabins><com:CabinClass Type="Economy"/></air:PermittedCabins>
                <air:FlightType MaxConnections="1"/>'),
    'maxsol_after_providers' => lfsXml('', '                <air:PreferredProviders><com:Provider Code="1G"/></air:PreferredProviders>
                <air:MaxSolutions>200</air:MaxSolutions>
                <air:PermittedCabins><com:CabinClass Type="Economy"/></air:PermittedCabins>
                <air:FlightType MaxConnections="1"/>'),
    'maxsol_after_flighttype' => lfsXml('', '                <air:PreferredProviders><com:Provider Code="1G"/></air:PreferredProviders>
                <air:PermittedCabins><com:CabinClass Type="Economy"/></air:PermittedCabins>
                <air:FlightType MaxConnections="1"/>
                <air:MaxSolutions>200</air:MaxSolutions>'),
    'solutions' => lfsXml('SolutionResult="true"', '                <air:PreferredProviders><com:Provider Code="1G"/></air:PreferredProviders>
                <air:PermittedCabins><com:CabinClass Type="Economy"/></air:PermittedCabins>
                <air:FlightType MaxConnections="1"/>'),
];

foreach ($variants as $label => $soap) {
    $result = $send->invoke($client, 'AirService', $soap);
    echo "\n=== {$label} ===\n";
    if (! ($result['success'] ?? false)) {
        echo 'ERROR: ' . ($result['error'] ?? '') . PHP_EOL;
        continue;
    }
    $rsp = data_get($result['parsed'], 'Body.LowFareSearchRsp');
    $ekBrands = [];
    foreach (data_get($rsp, 'BrandList.Brand', []) as $b) {
        $a = $b['@attributes'] ?? $b;
        if (($a['Carrier'] ?? '') === 'EK') {
            $ekBrands[] = $a['Name'] ?? '';
        }
    }
    echo 'EK brands: ' . implode(', ', $ekBrands) . PHP_EOL;
    $pp = ek600FromPricePoints($rsp);
    $sol = ek600FromSolutions($rsp);
    echo 'EK600 price points (' . count($pp) . '): ' . implode(' | ', $pp) . PHP_EOL;
    echo 'EK600 solutions (' . count($sol) . '): ' . implode(' | ', $sol) . PHP_EOL;
}

echo "\nBranch: P7250866 (sandbox/preprod)\n";
echo "Sabre same search has 3 tiers including XLOWFAE1 (Flex Plus)\n";
