<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProvinceSyncController extends Controller
{
    private const TBO_API_CITY_URL = 'http://api.tbotechnology.in/TBOHolidays_HotelAPI/CityList';
    private const TBO_API_USERNAME = 'SkylineexperienceTest';
    private const TBO_API_PASSWORD = 'Sky@69774762';

    public function syncFromTbo(Request $request)
    {
        $countries = Country::whereNotNull('iso_code')
            ->where('iso_code', '!=', '')
            ->get(['id', 'name', 'iso_code']);

        if ($countries->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No countries with iso_code found.',
            ], 404);
        }

        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($countries as $country) {
            try {
                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->retry(2, 1000)
                    ->withBasicAuth(self::TBO_API_USERNAME, self::TBO_API_PASSWORD)
                    ->post(self::TBO_API_CITY_URL, [
                        'CountryCode' => $country->iso_code,
                    ]);

                if ($response->failed()) {
                    Log::error('TBO CityList API failed', [
                        'country' => $country->name,
                        'country_code' => $country->iso_code,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    $failed++;
                    continue;
                }

                $payload = $response->json();
                $statusCode = $payload['Status']['Code'] ?? null;
                if ($statusCode !== 200) {
                    Log::error('TBO CityList API status not ok', [
                        'country' => $country->name,
                        'country_code' => $country->iso_code,
                        'status' => $payload['Status'] ?? null,
                    ]);
                    $failed++;
                    continue;
                }

                $cityList = $payload['CityList'] ?? [];
            } catch (\Exception $e) {
                Log::error('TBO CityList API error', [
                    'country' => $country->name,
                    'country_code' => $country->iso_code,
                    'message' => $e->getMessage(),
                ]);
                $failed++;
                continue;
            }

            foreach ($cityList as $city) {
                $name = trim((string) ($city['Name'] ?? ''));
                $code = trim((string) ($city['Code'] ?? ''));
                if ($name === '' || $code === '') {
                    continue;
                }

                $province = Province::where('country_id', $country->id)
                    ->where(function ($query) use ($name, $code) {
                        $query->where('tbo_code', $code)
                            ->orWhere('name', $name);
                    })
                    ->first();

                if ($province) {
                    $province->name = $name;
                    $province->tbo_code = $code;
                    if (isset($province->status) && $province->status === null) {
                        $province->status = 'active';
                    }
                    $province->save();
                    $updated++;
                } else {
                    Province::create([
                        'country_id' => $country->id,
                        'name' => $name,
                        'tbo_code' => $code,
                        'status' => 'active',
                    ]);
                    $created++;
                }
            }
        }

        $provinces = Province::with('country')
            ->orderBy('name')
            ->get(['id', 'country_id', 'name', 'tbo_code']);

        $export = $provinces->map(function ($province) {
            return [
                'id' => $province->id,
                'country_id' => $province->country_id,
                'country_name' => $province->country?->name,
                'name' => $province->name,
                'tbo_code' => $province->tbo_code,
            ];
        })->values();

        $jsonPath = public_path('user/mocks/provinces.json');
        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, $export->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json([
            'ok' => true,
            'countries' => $countries->count(),
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'total' => $provinces->count(),
            'json_path' => 'public/user/mocks/provinces.json',
        ]);
    }
}
