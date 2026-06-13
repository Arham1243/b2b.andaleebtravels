<?php

namespace App\Support\Travelport;

use Carbon\Carbon;

/**
 * Extract airline vendor remarks (Galileo *VR) from Travelport reservation responses.
 *
 * @see https://support.travelport.com/webhelp/uapi/Content/Shared_Topics/General_Remarks.htm
 */
final class TravelportVendorRemarksParser
{
    /**
     * @param  array<string, mixed>  $response
     * @return list<string>
     */
    public static function fromTravelportResponse(array $response): array
    {
        $parsed = is_array($response['parsed'] ?? null) ? $response['parsed'] : [];
        $raw = (string) ($response['raw'] ?? '');

        if ($parsed === [] && is_array($response['Body'] ?? null)) {
            $parsed = $response;
        }

        if ($raw === '' && is_string($parsed['raw'] ?? null)) {
            $raw = (string) $parsed['raw'];
        }

        $remarks = [];

        foreach (self::generalRemarkPaths() as $path) {
            foreach (self::asList(data_get($parsed, $path)) as $node) {
                $text = self::remarkTextFromNode($node);
                if ($text !== null) {
                    $remarks[] = $text;
                }
            }
        }

        if ($raw !== '') {
            foreach (self::remarkTextsFromRawXml($raw) as $text) {
                $remarks[] = $text;
            }
        }

        return self::normalizeRemarks($remarks);
    }

    /**
     * Parse ADTK ticketing deadline from vendor remark text, e.g.
     * "PLS ADTK OR CNL PK FLIGHT BY 13JUN 12:40 DXB LT".
     *
     * @param  array<string, mixed>  $response
     */
    public static function adtkDeadlineFromResponse(array $response, ?Carbon $reference = null): ?Carbon
    {
        $reference ??= now();

        foreach (self::fromTravelportResponse($response) as $remark) {
            $deadline = self::adtkDeadlineFromRemark($remark, $reference);
            if ($deadline !== null) {
                return $deadline;
            }
        }

        return null;
    }

    public static function adtkDeadlineFromRemark(string $remark, ?Carbon $reference = null): ?Carbon
    {
        $reference ??= now();
        $remark = trim($remark);

        if ($remark === '' || ! preg_match('/\bADTK\b/i', $remark)) {
            return null;
        }

        if (! preg_match(
            '/\bBY\s+(\d{1,2})([A-Z]{3})(?:\s+(\d{1,2}):(\d{2})(?:\s+([A-Z]{3})\s+LT)?)?/i',
            $remark,
            $match,
        )) {
            return null;
        }

        $day = (int) $match[1];
        $month = strtoupper($match[2]);
        $hour = isset($match[3]) && $match[3] !== '' ? (int) $match[3] : 23;
        $minute = isset($match[4]) && $match[4] !== '' ? (int) $match[4] : 59;
        $timezone = self::resolveRemarkTimezone($match[5] ?? '', $reference);

        $year = (int) $reference->format('Y');
        $candidate = self::buildRemarkDate($year, $month, $day, $hour, $minute, $timezone, $reference);
        if ($candidate === null) {
            return null;
        }

        if ($candidate->lt($reference->copy()->subDays(2))) {
            $candidate = self::buildRemarkDate($year + 1, $month, $day, $hour, $minute, $timezone, $reference);
        }

        return $candidate;
    }

    /**
     * @return list<string>
     */
    private static function generalRemarkPaths(): array
    {
        return [
            'Body.UniversalRecordRetrieveRsp.UniversalRecord.GeneralRemark',
            'Body.AirCreateReservationRsp.UniversalRecord.GeneralRemark',
            'Body.UniversalRecordModifyRsp.UniversalRecord.GeneralRemark',
            'UniversalRecord.GeneralRemark',
            'UniversalRecord.AirReservation.GeneralRemark',
            'GeneralRemark',
        ];
    }

    /**
     * @param  mixed  $node
     */
    private static function remarkTextFromNode(mixed $node): ?string
    {
        if (! is_array($node)) {
            return null;
        }

        $text = self::nodeText($node, 'RemarkData');
        if ($text === '') {
            return null;
        }

        if (! self::isVendorRemarkNode($node, $text)) {
            return null;
        }

        return self::cleanRemarkText($text);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function isVendorRemarkNode(array $node, string $text): bool
    {
        $typeInGds = strtoupper(self::attr($node, 'TypeInGds'));
        if ($typeInGds === 'VENDOR') {
            return true;
        }

        return self::looksLikeVendorRemarkText($text);
    }

    private static function looksLikeVendorRemarkText(string $text): bool
    {
        $upper = strtoupper(trim($text));
        if ($upper === '' || str_contains($upper, 'API TEST REMARK')) {
            return false;
        }

        foreach ([
            'VRMK',
            'VRNK',
            'ADTK',
            'CTCE',
            'CTCM',
            'PLS ADV',
            'PLS ADTK',
            'SSR OTHS',
            'OTHS',
        ] as $needle) {
            if (str_contains($upper, $needle)) {
                return true;
            }
        }

        return (bool) preg_match('/\b[A-Z0-9]{2}\/[A-Z0-9]{3}\b/', $upper);
    }

    /**
     * @return list<string>
     */
    private static function remarkTextsFromRawXml(string $raw): array
    {
        $remarks = [];

        if (! preg_match_all(
            '/<(?:[\w-]+:)?GeneralRemark\b([^>]*)>(.*?)<\/(?:[\w-]+:)?GeneralRemark>/is',
            $raw,
            $blocks,
            PREG_SET_ORDER,
        )) {
            return [];
        }

        foreach ($blocks as $block) {
            $attrs = (string) ($block[1] ?? '');
            $inner = (string) ($block[2] ?? '');

            if (! preg_match('/<(?:[\w-]+:)?RemarkData\b[^>]*>(.*?)<\/(?:[\w-]+:)?RemarkData>/is', $inner, $dataMatch)) {
                continue;
            }

            $text = html_entity_decode(strip_tags((string) ($dataMatch[1] ?? '')), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $text = self::cleanRemarkText($text);
            if ($text === '') {
                continue;
            }

            $typeInGds = self::attributeValue($attrs, 'TypeInGds');
            if (strtoupper($typeInGds) !== 'VENDOR' && ! self::looksLikeVendorRemarkText($text)) {
                continue;
            }

            $remarks[] = $text;
        }

        return $remarks;
    }

    /**
     * @param  list<string>  $remarks
     * @return list<string>
     */
    private static function normalizeRemarks(array $remarks): array
    {
        $normalized = [];

        foreach ($remarks as $remark) {
            $remark = self::cleanRemarkText($remark);
            if ($remark === '') {
                continue;
            }

            $normalized[$remark] = $remark;
        }

        return array_values($normalized);
    }

    private static function cleanRemarkText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);

        return preg_replace('/^\d+\.\s*/', '', $text) ?? $text;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function nodeText(array $node, string $key): string
    {
        $value = $node[$key] ?? null;

        if (is_array($value)) {
            $value = $value['#text'] ?? $value[0] ?? '';
        }

        return trim((string) $value);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function attr(array $node, string $name, string $default = ''): string
    {
        if (isset($node['@attributes'][$name])) {
            return trim((string) $node['@attributes'][$name]);
        }

        if (isset($node[$name]) && ! is_array($node[$name])) {
            return trim((string) $node[$name]);
        }

        return $default;
    }

    private static function attributeValue(string $attrs, string $name): string
    {
        if (preg_match('/\b' . preg_quote($name, '/') . '="([^"]*)"/i', $attrs, $match)) {
            return trim($match[1]);
        }

        return '';
    }

    private static function resolveRemarkTimezone(string $code, Carbon $reference): string
    {
        $code = strtoupper(trim($code));

        return match ($code) {
            'DXB', 'AUH', 'SHJ' => 'Asia/Dubai',
            'DOH' => 'Asia/Qatar',
            'RUH', 'JED', 'DMM' => 'Asia/Riyadh',
            'KWI' => 'Asia/Kuwait',
            'BAH' => 'Asia/Bahrain',
            'MCT' => 'Asia/Muscat',
            'CAI' => 'Africa/Cairo',
            'LON', 'LHR', 'LGW' => 'Europe/London',
            default => $reference->getTimezone()->getName(),
        };
    }

    private static function buildRemarkDate(
        int $year,
        string $month,
        int $day,
        int $hour,
        int $minute,
        string $timezone,
        Carbon $reference,
    ): ?Carbon {
        try {
            $date = Carbon::createFromFormat(
                'Y-M-d H:i',
                sprintf('%d-%s-%02d %02d:%02d', $year, $month, $day, $hour, $minute),
                $timezone,
            );
        } catch (\Throwable) {
            return null;
        }

        if (! $date instanceof Carbon) {
            return null;
        }

        if ($date->lt($reference->copy()->subDays(2))) {
            return null;
        }

        return $date;
    }

    /**
     * @return list<mixed>
     */
    private static function asList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        if ($value === []) {
            return [];
        }

        return array_is_list($value) ? $value : [$value];
    }
}
