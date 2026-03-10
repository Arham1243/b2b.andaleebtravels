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
    public function syncFromTbo(Request $request)
    {
        $countryCode = $request->input('country_code', 'AE');
        $countryName = $request->input('country_name', 'United Arab Emirates');

        $country = Country::where('name', $countryName)->first();
        if (!$country) {
            return response()->json([
                'ok' => false,
                'message' => "Country not found: {$countryName}",
            ], 404);
        }

        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->retry(2, 1000)
                ->post('http://api.tbotechnology.in/TBOHolidays_HotelAPI/CityList', [
                    'CountryCode' => $countryCode,
                ]);

            if ($response->failed()) {
                Log::error('TBO CityList API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'TBO API request failed.',
                ], 502);
            }

            $payload = $response->json();
            $statusCode = $payload['Status']['Code'] ?? null;
            if ($statusCode !== 200) {
                return response()->json([
                    'ok' => false,
                    'message' => $payload['Status']['Description'] ?? 'TBO API returned an error.',
                ], 502);
            }

            $cityList = $payload['CityList'] ?? [];
        } catch (\Exception $e) {
            Log::error('TBO CityList API error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'TBO API request error.',
            ], 502);
        }

        $created = 0;
        $updated = 0;

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

        $provinces = Province::where('country_id', $country->id)
            ->orderBy('name')
            ->get(['id', 'country_id', 'name', 'tbo_code']);

        $export = $provinces->map(function ($province) use ($country) {
            return [
                'id' => $province->id,
                'country_id' => $province->country_id,
                'country_name' => $country->name,
                'name' => $province->name,
                'tbo_code' => $province->tbo_code,
            ];
        })->values();

        $jsonPath = public_path('user/mocks/provinces.json');
        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, $export->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json([
            'ok' => true,
            'country' => $countryName,
            'country_code' => $countryCode,
            'created' => $created,
            'updated' => $updated,
            'total' => $provinces->count(),
            'json_path' => 'public/user/mocks/provinces.json',
        ]);
    }
}
