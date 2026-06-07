<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$search = (new App\Services\Travelport\TravelportApiClient())->lowFareSearch([
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
]);

$rsp = data_get($search['parsed'], 'Body.LowFareSearchRsp');
$fareInfos = data_get($rsp, 'FareInfoList.FareInfo');
$list = is_array($fareInfos) && array_is_list($fareInfos) ? $fareInfos : [$fareInfos];

$fareInfo = null;
foreach ($list as $fi) {
    if (($fi['@attributes']['FareBasis'] ?? '') === 'WDLIT3AE') {
        $fareInfo = $fi;
        break;
    }
}

if ($fareInfo === null) {
    echo "FareInfo not found\n";
    exit(1);
}

$fareInfoKey = $fareInfo['@attributes']['Key'] ?? '';
$fareRuleKey = is_array($fareInfo['FareRuleKey'] ?? null)
    ? ($fareInfo['FareRuleKey']['#text'] ?? $fareInfo['FareRuleKey'] ?? '')
    : ($fareInfo['FareRuleKey'] ?? '');

echo "FareInfoRef: $fareInfoKey\n";
echo "FareRuleKey len: " . strlen((string) $fareRuleKey) . "\n";

$client = new App\Services\Travelport\TravelportApiClient();
if (! method_exists($client, 'airFareRules')) {
    echo "airFareRules not implemented yet - will test via inline SOAP\n";
}

$traceId = 'trace_test_' . uniqid();
$authorizedBy = 'Zeeshan';
$targetBranch = 'P7250866';
$airNs = 'http://www.travelport.com/schema/air_v52_0';
$comNs = 'http://www.travelport.com/schema/common_v52_0';
$providerCode = '1G';
$fareRuleKeyEsc = htmlspecialchars((string) $fareRuleKey, ENT_XML1);
$fareInfoRefEsc = htmlspecialchars((string) $fareInfoKey, ENT_XML1);

$soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:AirFareRulesReq
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:FareRuleKey FareInfoRef="{$fareInfoRefEsc}" ProviderCode="{$providerCode}">{$fareRuleKeyEsc}</air:FareRuleKey>
        </air:AirFareRulesReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

$endpoint = 'https://emea.universal-api.pp.travelport.com/B2BGateway/connect/uAPI/AirService';
$base64Creds = base64_encode('Universal API/uAPI3803196999-ff9da8ef:sR-9}8Pjr+');
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $soap,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_HTTPHEADER => [
        'Content-Type: text/xml;charset=UTF-8',
        'Authorization: Basic ' . $base64Creds,
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP: $httpCode\n";
file_put_contents(__DIR__ . '/travelport-fare-rules-response.xml', (string) $response);
if (preg_match('/<air:FareRuleLong[^>]*Category="16"[^>]*>(.*?)<\/air:FareRuleLong>/s', (string) $response, $m)) {
    echo "Cat 16 sample: " . substr(strip_tags($m[1]), 0, 200) . "\n";
}
file_put_contents(__DIR__ . '/travelport-fare-rules-response.json', json_encode(
    json_decode(json_encode(simplexml_load_string(str_ireplace(
        ['soapenv:', 'SOAP:', 'soap:', 'air:', 'com:'],
        '',
        (string) $response
    ))), true),
    JSON_PRETTY_PRINT
));
echo "saved travelport-fare-rules-response.json\n";
