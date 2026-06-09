<?php

namespace App\Support;

final class SabrePriceQuoteResolver
{
    /**
     * @param  array<string, mixed>|null  $response
     * @return list<int>
     */
    public static function fromBookingResponse(?array $response): array
    {
        if ($response === null || $response === []) {
            return [];
        }

        $quotes = data_get($response, 'CreatePassengerNameRecordRS.TravelItineraryRead.TravelItinerary.ItineraryInfo.ItineraryPricing.PriceQuote');
        $records = self::extractFromPriceQuoteNodes($quotes);
        if ($records !== []) {
            return $records;
        }

        foreach (self::asList(data_get($response, 'CreatePassengerNameRecordRS.AirPrice')) as $airPrice) {
            if (! is_array($airPrice)) {
                continue;
            }

            $records = self::extractFromPriceQuoteNodes($airPrice['PriceQuote'] ?? null);
            if ($records !== []) {
                return $records;
            }
        }

        foreach (self::asList(data_get($response, 'UpdatePassengerNameRecordRS.AirPrice')) as $airPrice) {
            if (! is_array($airPrice)) {
                continue;
            }

            $records = self::extractFromPriceQuoteNodes($airPrice['PriceQuote'] ?? null);
            if ($records !== []) {
                return $records;
            }
        }

        return [];
    }

    /**
     * @return list<int>
     */
    public static function fromXml(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        if (! preg_match_all('/<(?:[\w-]+:)?PriceQuote\b([^>]*)>(.*?)<\/(?:[\w-]+:)?PriceQuote>/is', $xml, $blocks, PREG_SET_ORDER)) {
            return [];
        }

        $records = [];
        $fallbackRecord = 1;

        foreach ($blocks as $block) {
            $attributes = $block[1];
            $body = $block[2];

            if (self::hasInactiveSignatureLine($body)) {
                continue;
            }

            $recordNumber = 0;
            if (preg_match('/\bRPH="(\d+)"/i', $attributes, $match)) {
                $recordNumber = (int) $match[1];
            }

            if ($recordNumber <= 0 && preg_match('/<(?:[\w-]+:)?PricedItinerary\b[^>]*\bRPH="(\d+)"/i', $body, $pricedMatch)) {
                $recordNumber = (int) $pricedMatch[1];
            }

            if ($recordNumber <= 0) {
                $recordNumber = $fallbackRecord;
            }

            if ($recordNumber > 0) {
                $records[] = $recordNumber;
            }

            $fallbackRecord++;
        }

        return self::normalizeRecords($records);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function buildAirTicketPriceQuotePayload(array $recordNumbers): array
    {
        $recordNumbers = self::normalizeRecords($recordNumbers);

        if ($recordNumbers === []) {
            return [
                [
                    'Record' => [
                        ['Number' => 1],
                    ],
                ],
            ];
        }

        if (count($recordNumbers) === 1) {
            return [
                [
                    'Record' => [
                        ['Number' => $recordNumbers[0]],
                    ],
                ],
            ];
        }

        $isConsecutive = true;
        for ($i = 1; $i < count($recordNumbers); $i++) {
            if ($recordNumbers[$i] !== $recordNumbers[$i - 1] + 1) {
                $isConsecutive = false;
                break;
            }
        }

        if ($isConsecutive) {
            return [
                [
                    'Record' => [
                        [
                            'Number' => $recordNumbers[0],
                            'EndNumber' => $recordNumbers[count($recordNumbers) - 1],
                        ],
                    ],
                ],
            ];
        }

        return [
            [
                'Record' => array_map(
                    static fn (int $number): array => ['Number' => $number],
                    $recordNumbers,
                ),
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private static function extractFromPriceQuoteNodes(mixed $quotes): array
    {
        $records = [];

        foreach (self::asList($quotes) as $quote) {
            if (! is_array($quote)) {
                continue;
            }

            $status = self::priceQuoteStatus($quote);
            if ($status !== null && strtoupper($status) !== 'ACTIVE') {
                continue;
            }

            $rph = (int) ($quote['RPH'] ?? 0);
            if ($rph <= 0) {
                foreach (self::asList($quote['PricedItinerary'] ?? null) as $pricedItinerary) {
                    if (! is_array($pricedItinerary)) {
                        continue;
                    }

                    $rph = (int) ($pricedItinerary['RPH'] ?? 0);
                    if ($rph > 0) {
                        break;
                    }
                }
            }

            if ($rph > 0) {
                $records[] = $rph;
            }
        }

        return self::normalizeRecords($records);
    }

    private static function hasInactiveSignatureLine(string $body): bool
    {
        if (! preg_match_all('/<(?:[\w-]+:)?SignatureLine\b([^>]*)>/i', $body, $matches)) {
            return false;
        }

        foreach ($matches[1] as $attributes) {
            if (preg_match('/\bStatus="([^"]+)"/i', $attributes, $statusMatch)) {
                if (strtoupper($statusMatch[1]) !== 'ACTIVE') {
                    return true;
                }
            }
        }

        return false;
    }

    private static function priceQuoteStatus(array $quote): ?string
    {
        $signatureLines = self::asList($quote['MiscInformation']['SignatureLine'] ?? null);

        foreach ($signatureLines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $status = trim((string) ($line['Status'] ?? ''));
            if ($status !== '') {
                return $status;
            }
        }

        return null;
    }

    /**
     * @param  list<int>  $records
     * @return list<int>
     */
    private static function normalizeRecords(array $records): array
    {
        $records = array_values(array_unique(array_filter(
            array_map(static fn ($value): int => (int) $value, $records),
            static fn (int $value): bool => $value > 0,
        )));

        sort($records, SORT_NUMERIC);

        return $records;
    }

    /**
     * @return list<mixed>
     */
    private static function asList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            return [$value];
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
