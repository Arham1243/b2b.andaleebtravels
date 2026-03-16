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
    private const NAME_NORMALIZE_PATTERN = '/[^a-z0-9\\s]/u';

    private function normalizeCityName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        if (class_exists(\Normalizer::class)) {
            $name = \Normalizer::normalize($name, \Normalizer::FORM_D);
            $name = preg_replace('/\\p{Mn}/u', '', $name);
        } else {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
            if ($transliterated !== false) {
                $name = $transliterated;
            }
        }

        $name = str_replace(["’", "'", "`", "´"], '', $name);
        $name = str_replace(['-', '_'], ' ', $name);
        $name = mb_strtolower($name);
        $name = preg_replace(self::NAME_NORMALIZE_PATTERN, '', $name);
        $name = preg_replace('/\\s+/', ' ', $name);

        return trim($name);
    }

    public function syncFromTbo(Request $request)
    {
        $countriesQuery = Country::whereNotNull('iso_code')
            ->where('iso_code', '!=', '')
            ->select(['id', 'name', 'iso_code']);

        $requestedCountry = strtoupper(trim((string) $request->query('country', '')));
        if ($requestedCountry !== '') {
            $countriesQuery->where('iso_code', $requestedCountry);
        }

        if (!$countriesQuery->exists()) {
            return response()->json([
                'ok' => false,
                'message' => $requestedCountry !== ''
                    ? 'No country found for provided iso_code.'
                    : 'No countries with iso_code found.',
            ], 404);
        }

        $created = 0;
        $updated = 0;
        $failed = 0;
        $processed = 0;

        $jsonPath = public_path('user/mocks/provinces.json');
        File::ensureDirectoryExists(dirname($jsonPath));

        $countriesQuery->orderBy('id')->chunkById(25, function ($countries) use (
            &$created,
            &$updated,
            &$failed,
            &$processed,
            $jsonPath
        ) {
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

                $provinces = Province::where('country_id', $country->id)
                    ->get(['id', 'country_id', 'name', 'tbo_code', 'status']);

                $provinceByCode = $provinces
                    ->filter(fn($province) => !empty($province->tbo_code))
                    ->keyBy(fn($province) => (string) $province->tbo_code);

                $provinceByName = $provinces
                    ->mapWithKeys(function ($province) {
                        return [$this->normalizeCityName((string) $province->name) => $province];
                    });

                foreach ($cityList as $city) {
                    $name = trim((string) ($city['Name'] ?? ''));
                    $code = trim((string) ($city['Code'] ?? ''));
                    if ($name === '' || $code === '') {
                        continue;
                    }

                    $province = $provinceByCode->get($code);
                    if (!$province) {
                        $normalizedName = $this->normalizeCityName($name);
                        $province = $normalizedName !== '' ? $provinceByName->get($normalizedName) : null;
                    }

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

                $processed++;

                $existing = [];
                if (File::exists($jsonPath)) {
                    $decoded = json_decode(File::get($jsonPath), true);
                    if (is_array($decoded)) {
                        $existing = $decoded;
                    }
                }

                $existing = array_values(array_filter($existing, function ($item) use ($country) {
                    return (int) ($item['country_id'] ?? 0) !== (int) $country->id;
                }));

                $countryProvinces = Province::where('country_id', $country->id)
                    ->orderBy('name')
                    ->get(['id', 'country_id', 'name', 'tbo_code'])
                    ->map(function ($province) use ($country) {
                        return [
                            'id' => $province->id,
                            'country_id' => $province->country_id,
                            'country_name' => $country->name,
                            'name' => $province->name,
                            'tbo_code' => $province->tbo_code,
                        ];
                    })
                    ->values()
                    ->all();

                $merged = array_merge($existing, $countryProvinces);
                File::put($jsonPath, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        });

        return response()->json([
            'ok' => true,
            'countries' => $countriesQuery->count(),
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'processed' => $processed,
            'total' => Province::count(),
            'json_path' => 'public/user/mocks/provinces.json',
        ]);
    }
}
