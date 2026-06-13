<?php

namespace App\Support\Travelport;

use Carbon\Carbon;

/**
 * Resolve the airline ticketing deadline for a held Travelport PNR.
 *
 * Prefer ActionStatus/@TicketDate from the live reservation (GDS hold window).
 * Do not use pre-hold AirPrice LatestTicketingTime alone — that is a fare-rule date
 * (often near departure) and not the PNR time limit.
 */
class TravelportHoldExpiryResolver
{
    /**
     * @param  array<string, mixed>  $response  Travelport API response (parsed + raw)
     */
    public static function fromTravelportResponse(array $response): ?Carbon
    {
        $parsed = is_array($response['parsed'] ?? null) ? $response['parsed'] : [];
        $raw = (string) ($response['raw'] ?? '');

        if ($parsed === [] && is_array($response['Body'] ?? null)) {
            $parsed = $response;
        }

        if ($raw === '' && is_string($parsed['raw'] ?? null)) {
            $raw = (string) $parsed['raw'];
        }

        return self::fromActionStatusTicketDate($parsed, $raw)
            ?? self::fromPricingInfoDeadline($parsed, $raw);
    }

    /**
     * PNR hold window only — ActionStatus TicketDate (excludes fare-rule LatestTicketingTime).
     *
     * @param  array<string, mixed>  $response
     */
    public static function ticketDateFromResponse(array $response): ?Carbon
    {
        $parsed = is_array($response['parsed'] ?? null) ? $response['parsed'] : [];
        $raw = (string) ($response['raw'] ?? '');

        if ($parsed === [] && is_array($response['Body'] ?? null)) {
            $parsed = $response;
        }

        if ($raw === '' && is_string($parsed['raw'] ?? null)) {
            $raw = (string) $parsed['raw'];
        }

        return self::fromActionStatusTicketDate($parsed, $raw);
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private static function fromActionStatusTicketDate(array $parsed, string $raw): ?Carbon
    {
        foreach ([
            'Body.UniversalRecordRetrieveRsp.UniversalRecord.ActionStatus',
            'Body.AirCreateReservationRsp.UniversalRecord.ActionStatus',
            'Body.UniversalRecordModifyRsp.UniversalRecord.ActionStatus',
            'UniversalRecord.ActionStatus',
            'ActionStatus',
        ] as $path) {
            $expiry = self::ticketDateFromActionStatusNodes(data_get($parsed, $path));
            if ($expiry !== null) {
                return $expiry;
            }
        }

        if ($raw === '') {
            return null;
        }

        if (preg_match_all('/<(?:[\w-]+:)?ActionStatus\b([^>]*)\/?>/i', $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $expiry = self::parseTicketDateAttribute(self::attributeValue((string) ($match[1] ?? ''), 'TicketDate'));
                if ($expiry !== null) {
                    return $expiry;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private static function fromPricingInfoDeadline(array $parsed, string $raw): ?Carbon
    {
        $candidates = [];

        foreach (self::collectPricingInfoNodes($parsed) as $node) {
            if (! is_array($node)) {
                continue;
            }

            foreach (['TrueLastDateToTicket', 'LatestTicketingTime'] as $attr) {
                $parsedDate = self::parseTicketDateAttribute(self::attr($node, $attr));
                if ($parsedDate !== null) {
                    $candidates[] = $parsedDate;
                }
            }
        }

        if ($raw !== '') {
            foreach (['TrueLastDateToTicket', 'LatestTicketingTime'] as $attr) {
                if (preg_match_all('/\b' . preg_quote($attr, '/') . '="([^"]+)"/i', $raw, $matches)) {
                    foreach ($matches[1] as $value) {
                        $parsedDate = self::parseTicketDateAttribute((string) $value);
                        if ($parsedDate !== null) {
                            $candidates[] = $parsedDate;
                        }
                    }
                }
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static fn (Carbon $a, Carbon $b): int => $a->getTimestamp() <=> $b->getTimestamp());

        return $candidates[0];
    }

    /**
     * @return list<mixed>
     */
    private static function collectPricingInfoNodes(array $parsed): array
    {
        $nodes = [];
        $paths = [
            'Body.UniversalRecordRetrieveRsp.UniversalRecord.AirReservation.AirPricingInfo',
            'Body.AirCreateReservationRsp.UniversalRecord.AirReservation.AirPricingInfo',
            'Body.UniversalRecordModifyRsp.UniversalRecord.AirReservation.AirPricingInfo',
            'UniversalRecord.AirReservation.AirPricingInfo',
            'AirReservation.AirPricingInfo',
            'AirPricingInfo',
        ];

        foreach ($paths as $path) {
            $value = data_get($parsed, $path);
            if ($value === null) {
                continue;
            }

            foreach (self::asList($value) as $node) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    private static function ticketDateFromActionStatusNodes(mixed $nodes): ?Carbon
    {
        foreach (self::asList($nodes) as $node) {
            if (! is_array($node)) {
                continue;
            }

            $expiry = self::parseTicketDateAttribute(self::attr($node, 'TicketDate'));
            if ($expiry !== null) {
                return $expiry;
            }
        }

        return null;
    }

    private static function parseTicketDateAttribute(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '' || strtoupper($value) === 'T*') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $node
     */
    private static function attr(?array $node, string $name, string $default = ''): string
    {
        if (! is_array($node)) {
            return $default;
        }

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

        if (array_is_list($value)) {
            return $value;
        }

        return [$value];
    }
}
