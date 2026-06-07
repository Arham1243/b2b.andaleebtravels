<?php

namespace App\Support;

use Carbon\Carbon;

class FlightListingMetaBuilder
{
    /**
     * @param  list<array<string, mixed>>  $legs
     * @param  array{tags: list<string>}  $pricingTags
     * @return array<string, mixed>
     */
    public static function fromLegs(array $legs, float $price, array $pricingTags = ['tags' => ['published']]): array
    {
        $defaults = [
            'stops_tier' => 0,
            'connections' => 0,
            'within_stops' => 0,
            'first_dep_airports' => [],
            'middle_airports' => [],
            'dep_buckets' => [],
            'arr_buckets' => [],
            'carriers' => [],
        ];

        $o = isset($legs[0]['filter_axes']) && is_array($legs[0]['filter_axes'])
            ? array_merge($defaults, $legs[0]['filter_axes'])
            : $defaults;

        $r = isset($legs[1]['filter_axes']) && is_array($legs[1]['filter_axes'])
            ? array_merge($defaults, $legs[1]['filter_axes'])
            : null;

        $airlines = [];

        foreach ($legs as $leg) {
            foreach (($leg['segments'] ?? []) as $seg) {
                $c = strtoupper(trim((string) ($seg['carrier'] ?? '')));

                if ($c !== '') {
                    $airlines[$c] = true;
                }
            }
        }

        $outSegs = $legs[0]['segments'] ?? [];
        $firstOutSeg = $outSegs[0] ?? [];
        $lastOutSeg = $outSegs !== [] ? $outSegs[array_key_last($outSegs)] : [];
        $airlinePrimary = strtoupper(trim((string) ($firstOutSeg['carrier'] ?? '')));
        $airlineName = trim((string) ($firstOutSeg['carrier_display'] ?? $airlinePrimary));

        $airlineCodes = array_keys($airlines);
        usort($airlineCodes, static function (string $a, string $b) use ($airlinePrimary): int {
            if ($a === $airlinePrimary && $b !== $airlinePrimary) {
                return -1;
            }
            if ($b === $airlinePrimary && $a !== $airlinePrimary) {
                return 1;
            }

            return strcasecmp($a, $b);
        });

        $durOutbound = (int) data_get($legs, '0.elapsedTime', 0);
        $durReturn = (int) data_get($legs, '1.elapsedTime', 0);

        return [
            'price' => $price,
            'st_o' => $o['stops_tier'],
            'st_r' => $r ? ($r['stops_tier'] ?? null) : null,
            'dba_o' => $o['dep_buckets'],
            'aba_o' => $o['arr_buckets'],
            'dba_r' => $r ? ($r['dep_buckets'] ?? []) : [],
            'aba_r' => $r ? ($r['arr_buckets'] ?? []) : [],
            'dep_o' => $o['first_dep_airports'],
            'dep_r' => $r ? $r['first_dep_airports'] : [],
            'conn_o' => $o['middle_airports'],
            'conn_r' => $r ? $r['middle_airports'] : [],
            'al' => $airlineCodes,
            'airline_primary' => $airlinePrimary,
            'airline_name' => $airlineName,
            'fare' => $pricingTags['tags'],
            'first_dep_iso' => data_get($legs, '0.segments.0.departure_datetime'),
            'first_arr_iso' => $lastOutSeg['arrival_datetime'] ?? null,
            'dep_ts' => self::listingTimestamp(data_get($legs, '0.segments.0.departure_datetime')),
            'arr_ts' => self::listingTimestamp($lastOutSeg['arrival_datetime'] ?? null),
            'dur_o' => $durOutbound,
            'dur_r' => $durReturn,
            'dur_total' => $durOutbound + $durReturn,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $segmentsOut
     * @return array<string, mixed>
     */
    public static function axisForLegSegments(array $segmentsOut): array
    {
        if ($segmentsOut === []) {
            return [
                'stops_tier' => 0,
                'connections' => 0,
                'within_stops' => 0,
                'first_dep_airports' => [],
                'middle_airports' => [],
                'dep_buckets' => [],
                'arr_buckets' => [],
                'carriers' => [],
            ];
        }

        $conn = max(0, count($segmentsOut) - 1);
        $within = 0;

        foreach ($segmentsOut as $seg) {
            $within += (int) ($seg['stop_count'] ?? 0);
        }

        $total = $conn + $within;
        $stopsTier = $total === 0 ? 0 : ($total === 1 ? 1 : 2);

        $first = reset($segmentsOut);
        $middles = [];

        foreach ($segmentsOut as $idx => $seg) {
            if ($idx !== count($segmentsOut) - 1) {
                $arr = strtoupper(trim((string) ($seg['to'] ?? '')));
                if ($arr !== '') {
                    $middles[] = $arr;
                }
            }
        }

        $depBuckets = [];
        foreach ($segmentsOut as $seg) {
            $b = self::timeBucket((string) ($seg['departure_clock'] ?? ''));
            if ($b > 0) {
                $depBuckets[$b] = true;
            }
        }
        $arrBuckets = [];
        foreach ($segmentsOut as $seg) {
            $b = self::timeBucket((string) ($seg['arrival_clock'] ?? ''));
            if ($b > 0) {
                $arrBuckets[$b] = true;
            }
        }

        return [
            'stops_tier' => $stopsTier,
            'connections' => $conn,
            'within_stops' => $within,
            'first_dep_airports' => [strtoupper((string) ($first['from'] ?? ''))],
            'middle_airports' => array_values(array_unique($middles)),
            'dep_buckets' => array_map('intval', array_keys($depBuckets)),
            'arr_buckets' => array_map('intval', array_keys($arrBuckets)),
            'carriers' => array_values(array_unique(array_filter(array_map(
                static fn ($s) => strtoupper(trim((string) ($s['carrier'] ?? ''))),
                $segmentsOut,
            )))),
        ];
    }

    private static function timeBucket(?string $hhmm): int
    {
        if (!$hhmm || !preg_match('/^(\d{1,2}):/', $hhmm, $m)) {
            return 0;
        }

        $h = (int) $m[1];

        if ($h >= 0 && $h < 6) {
            return 4;
        }
        if ($h >= 6 && $h < 12) {
            return 1;
        }
        if ($h >= 12 && $h < 18) {
            return 2;
        }

        return 3;
    }

    private static function listingTimestamp(mixed $iso): int
    {
        if (! is_string($iso) || trim($iso) === '') {
            return 0;
        }

        try {
            return (int) Carbon::parse($iso)->timestamp;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
