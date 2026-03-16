<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProvinceSyncController extends Controller
{
    private const TBO_API_CITY_URL = 'http://api.tbotechnology.in/TBOHolidays_HotelAPI/CityList';
    private const TBO_API_USERNAME = 'SkylineexperienceTest';
    private const TBO_API_PASSWORD = 'Sky@69774762';
    private const NAME_NORMALIZE_PATTERN = '/[^a-z0-9\\s]/u';
    private const DEBUG_LOG_UNMATCHED = true;

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
        $name = preg_replace('/\\p{Pd}+/u', ' ', $name);
        $name = str_replace(['-', '_', '/', '\\'], ' ', $name);
        $name = mb_strtolower($name);
        $name = preg_replace(self::NAME_NORMALIZE_PATTERN, '', $name);
        $name = preg_replace('/\\s+/', ' ', $name);

        return trim($name);
    }

    private function normalizeLoose(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $name = str_replace(["’", "'", "`", "´", '-', '/', '\\', '.', ','], ' ', $name);
        $name = mb_strtolower($name);
        $name = preg_replace('/\\s+/', ' ', $name);

        return trim($name);
    }

    private function resolveProvinceByCity(
        Country $country,
        string $name,
        string $code,
        \Illuminate\Support\Collection $provinceByCode,
        \Illuminate\Support\Collection $provinceByName,
        bool $allowGlobalMatch = false
    ): ?Province {
        $province = $provinceByCode->get($code);
        if ($province) {
            return $province;
        }

        $normalizedName = $this->normalizeCityName($name);
        if ($normalizedName !== '' && $provinceByName->has($normalizedName)) {
            return $provinceByName->get($normalizedName);
        }

        $likeName = mb_strtolower(trim($name));
        $altName = $this->normalizeLoose($name);

        if ($likeName !== '' || $altName !== '') {
            $province = Province::where('country_id', $country->id)
                ->where(function ($query) use ($likeName, $altName) {
                    if ($likeName !== '') {
                        $query->orWhere(DB::raw('LOWER(name)'), 'LIKE', '%' . $likeName . '%');
                    }
                    if ($altName !== '' && $altName !== $likeName) {
                        $query->orWhere(DB::raw('LOWER(name)'), 'LIKE', '%' . $altName . '%');
                    }
                })
                ->first();

            if ($province) {
                return $province;
            }
        }

        if ($allowGlobalMatch && $normalizedName !== '') {
            $globalMatches = Province::query()
                ->get(['id', 'country_id', 'name', 'tbo_code', 'status'])
                ->filter(function ($province) use ($normalizedName) {
                    $norm = $this->normalizeCityName((string) $province->name);
                    return $norm !== '' && $norm === $normalizedName;
                })
                ->values();

            if ($globalMatches->count() === 1) {
                return $globalMatches->first();
            }
        }

        return null;
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
        $errors = [];

        $jsonPath = public_path('user/mocks/provinces.json');
        File::ensureDirectoryExists(dirname($jsonPath));

        $allowGlobalMatch = $request->boolean('global', false);
        $collectUnmatched = $request->boolean('unmatched', false);
        $unmatched = [];

        $countriesQuery->orderBy('id')->chunkById(25, function ($countries) use (
            &$created,
            &$updated,
            &$failed,
            &$processed,
            $jsonPath,
            &$errors,
            $allowGlobalMatch,
            $collectUnmatched,
            &$unmatched
        ) {
            foreach ($countries as $country) {
                $result = $this->syncCountryCities($country, true, $jsonPath, $allowGlobalMatch, $collectUnmatched);
                $created += $result['created'];
                $updated += $result['updated'];
                $failed += $result['failed'];
                $processed += $result['processed'];
                if (!empty($result['error'])) {
                    $errors[] = $result['error'];
                }
                if (!empty($result['unmatched']) && $collectUnmatched) {
                    $unmatched = array_merge($unmatched, $result['unmatched']);
                }
            }
        });

        return response()->json([
            'ok' => true,
            'countries' => $countriesQuery->count(),
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'processed' => $processed,
            'errors' => $errors,
            'unmatched' => $collectUnmatched ? array_slice($unmatched, 0, 50) : [],
            'total' => Province::count(),
            'json_path' => 'public/user/mocks/provinces.json',
        ]);
    }

    public function updateTboCodes(Request $request)
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
        $errors = [];

        $allowGlobalMatch = $request->boolean('global', false);
        $collectUnmatched = $request->boolean('unmatched', false);
        $unmatched = [];

        $countriesQuery->orderBy('id')->chunkById(25, function ($countries) use (
            &$created,
            &$updated,
            &$failed,
            &$processed,
            &$errors,
            $allowGlobalMatch,
            $collectUnmatched,
            &$unmatched
        ) {
            foreach ($countries as $country) {
                $result = $this->syncCountryCities($country, false, null, $allowGlobalMatch, $collectUnmatched);
                $created += $result['created'];
                $updated += $result['updated'];
                $failed += $result['failed'];
                $processed += $result['processed'];
                if (!empty($result['error'])) {
                    $errors[] = $result['error'];
                }
                if (!empty($result['unmatched']) && $collectUnmatched) {
                    $unmatched = array_merge($unmatched, $result['unmatched']);
                }
            }
        });

        return response()->json([
            'ok' => true,
            'countries' => $countriesQuery->count(),
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'processed' => $processed,
            'errors' => $errors,
            'unmatched' => $collectUnmatched ? array_slice($unmatched, 0, 50) : [],
            'total' => Province::count(),
        ]);
    }

    private function syncCountryCities(
        Country $country,
        bool $writeJson,
        ?string $jsonPath,
        bool $allowGlobalMatch = false,
        bool $collectUnmatched = false
    ): array
    {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $unmatched = [];

        try {
            $response = Http::timeout(60)
                ->connectTimeout(15)
                ->retry(3, 2000)
                ->withBasicAuth(self::TBO_API_USERNAME, self::TBO_API_PASSWORD)
                ->post(self::TBO_API_CITY_URL, [
                    'CountryCode' => $country->iso_code,
                ]);

            Log::info('TBO CityList API response meta', [
                'country' => $country->name,
                'country_code' => $country->iso_code,
                'status' => $response->status(),
                'ok' => $response->ok(),
            ]);

            if ($response->failed()) {
                Log::error('TBO CityList API failed', [
                    'country' => $country->name,
                    'country_code' => $country->iso_code,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'created' => 0,
                    'updated' => 0,
                    'failed' => 1,
                    'processed' => 1,
                    'error' => [
                        'country' => $country->name,
                        'country_code' => $country->iso_code,
                        'type' => 'http_failed',
                        'status' => $response->status(),
                    ],
                ];
            }

            $payload = $response->json();
            $statusCode = $payload['Status']['Code'] ?? null;
            if ($statusCode !== 200) {
                Log::error('TBO CityList API status not ok', [
                    'country' => $country->name,
                    'country_code' => $country->iso_code,
                    'status' => $payload['Status'] ?? null,
                ]);
                return [
                    'created' => 0,
                    'updated' => 0,
                    'failed' => 1,
                    'processed' => 1,
                    'error' => [
                        'country' => $country->name,
                        'country_code' => $country->iso_code,
                        'type' => 'status_not_ok',
                        'status' => $payload['Status'] ?? null,
                    ],
                ];
            }

            $cityList = $payload['CityList'] ?? [];
            Log::info('TBO CityList API city count', [
                'country' => $country->name,
                'country_code' => $country->iso_code,
                'count' => is_array($cityList) ? count($cityList) : 0,
            ]);
        } catch (\Exception $e) {
            Log::error('TBO CityList API error', [
                'country' => $country->name,
                'country_code' => $country->iso_code,
                'message' => $e->getMessage(),
            ]);
            return [
                'created' => 0,
                'updated' => 0,
                'failed' => 1,
                'processed' => 1,
                'error' => [
                    'country' => $country->name,
                    'country_code' => $country->iso_code,
                    'type' => 'exception',
                    'message' => $e->getMessage(),
                ],
            ];
        }

        $provinces = Province::where('country_id', $country->id)
            ->get(['id', 'country_id', 'name', 'tbo_code', 'status']);

        $provinceByCode = $provinces
            ->filter(fn($province) => !empty($province->tbo_code))
            ->keyBy(fn($province) => (string) $province->tbo_code);

                $provinceByName = $provinces
                    ->flatMap(function ($province) {
                        $name = (string) $province->name;
                        $normalized = $this->normalizeCityName($name);
                        $loose = $this->normalizeLoose($name);
                        $keys = array_filter([$normalized, $loose]);
                        return collect($keys)->mapWithKeys(fn($key) => [$key => $province]);
                    });

        foreach ($cityList as $city) {
            $name = trim((string) ($city['Name'] ?? ''));
            $code = trim((string) ($city['Code'] ?? ''));
            if ($name === '' || $code === '') {
                continue;
            }

            $province = $this->resolveProvinceByCity(
                $country,
                $name,
                $code,
                $provinceByCode,
                $provinceByName,
                $allowGlobalMatch
            );

            if ($province) {
                Log::info('TBO CityList match', [
                    'country' => $country->name,
                    'country_code' => $country->iso_code,
                    'city' => $name,
                    'code' => $code,
                    'matched_province_id' => $province->id,
                    'matched_province_name' => $province->name,
                ]);
                $province->name = $name;
                $province->tbo_code = $code;
                if (isset($province->status) && $province->status === null) {
                    $province->status = 'active';
                }
                $province->save();
                $updated++;
            } else {
                if (self::DEBUG_LOG_UNMATCHED) {
                    Log::warning('TBO CityList no match, creating province', [
                        'country' => $country->name,
                        'country_code' => $country->iso_code,
                        'city' => $name,
                        'code' => $code,
                        'normalized' => $this->normalizeCityName($name),
                        'loose' => $this->normalizeLoose($name),
                    ]);
                }
                Province::create([
                    'country_id' => $country->id,
                    'name' => $name,
                    'tbo_code' => $code,
                    'status' => 'active',
                ]);
                $created++;
                if ($collectUnmatched) {
                    $unmatched[] = ['name' => $name, 'code' => $code, 'country' => $country->iso_code];
                }
            }
        }

        if ($writeJson && $jsonPath) {
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

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'processed' => 1,
            'unmatched' => $collectUnmatched ? $unmatched : [],
        ];
    }
}
