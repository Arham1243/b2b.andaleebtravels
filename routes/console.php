<?php

use App\Models\Province;
use App\Services\HotelProviders\TboHotelProvider;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('tbo:cache-catalogue {city : TBO city code or province name (e.g. Dubai)}', function (string $city) {
    if (function_exists('ini_set')) {
        @ini_set('memory_limit', '512M');
    }

    $tboCode = $city;
    $province = Province::query()
        ->where('name', $city)
        ->orWhere('tbo_code', $city)
        ->first();

    if ($province && !empty($province->tbo_code)) {
        $tboCode = $province->tbo_code;
        $this->info("Province: {$province->name} → TBO city code: {$tboCode}");
    }

    $count = (new TboHotelProvider())->warmCatalogueCache($tboCode);
    $this->info("Cached {$count} hotels for city code {$tboCode}.");

    return $count > 0 ? 0 : 1;
})->purpose('Warm TBO HotelCodeList disk cache (run on server via SSH when web memory is capped)');
