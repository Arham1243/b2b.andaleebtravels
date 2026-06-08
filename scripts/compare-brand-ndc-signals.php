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

$rsp = data_get($raw['parsed'], 'Body.LowFareSearchRsp');
$brands = data_get($rsp, 'BrandList.Brand');
$brands = is_array($brands) && array_is_list($brands) ? $brands : [$brands];
$brandById = [];

foreach ($brands as $b) {
    $a = $b['@attributes'] ?? $b;
    $brandById[$a['BrandID'] ?? ''] = $a;
}

$fareList = data_get($rsp, 'FareInfoList.FareInfo');
$fareList = is_array($fareList) && array_is_list($fareList) ? $fareList : [$fareList];
$seen = [];

foreach ($fareList as $f) {
    $a = $f['@attributes'] ?? $f;
    $fb = $a['FareBasis'] ?? '';
    if ($fb === '' || isset($seen[$fb])) {
        continue;
    }
    $seen[$fb] = true;
    $hasBrandNode = isset($f['Brand']);
    $brandKey = '';
    $brandId = '';
    if ($hasBrandNode) {
        $bn = $f['Brand'];
        $ba = $bn['@attributes'] ?? $bn;
        $brandKey = $ba['Key'] ?? '';
        $brandId = $ba['BrandID'] ?? '';
    }
    $bl = $brandById[$brandId] ?? [];
    $ndc = $hasBrandNode
        && strtolower((string) ($bl['BrandedDetailsAvailable'] ?? '')) === 'true'
        ? 'Y'
        : 'N';
    printf(
        "%-12s brandNode=%s brandId=%-8s brandName=%-20s carrier=%-3s BrandedDetails=%-5s ndc=%s\n",
        $fb,
        $hasBrandNode ? 'Y' : 'N',
        $brandId,
        $bl['Name'] ?? '',
        $bl['Carrier'] ?? '',
        $bl['BrandedDetailsAvailable'] ?? 'n/a',
        $ndc,
    );
}
