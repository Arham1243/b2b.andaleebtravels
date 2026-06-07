<?php

namespace App\Support\Travelport;

final class TravelportFareRulesResponseParser
{
    /**
     * @return list<array{
     *     route: string,
     *     fare_basis: string,
     *     sections: list<array{title: string, paragraphs: list<string>}>,
     *     text: string
     * }>
     */
    public static function toComponents(string $rawXml, array $request = []): array
    {
        $sections = self::parseSections($rawXml);

        if ($sections === []) {
            return [];
        }

        $route = trim((string) ($request['route_label'] ?? ''));
        $fareBasis = trim((string) ($request['fare_basis'] ?? ''));
        $ruleNumber = null;

        if (preg_match('/<(?:air:)?FareRule\b[^>]*\bRuleNumber="([^"]+)"/i', $rawXml, $match)) {
            $ruleNumber = trim($match[1]);
        }

        $text = self::flattenSections($sections);

        return [[
            'route' => $route !== '' ? $route : 'Fare Rules',
            'fare_basis' => $fareBasis,
            'fare_rule' => $ruleNumber,
            'sections' => $sections,
            'text' => $text,
        ]];
    }

    /**
     * @return list<array{title: string, paragraphs: list<string>}>
     */
    public static function parseSections(string $rawXml): array
    {
        $sections = [];

        if (! preg_match_all(
            '/<(?:air:)?FareRuleLong\b[^>]*\bCategory="([^"]*)"[^>]*\bType="([^"]*)"[^>]*><!\[CDATA\[(.*?)\]\]><\/(?:air:)?FareRuleLong>/s',
            $rawXml,
            $matches,
            PREG_SET_ORDER,
        )) {
            return [];
        }

        foreach ($matches as $match) {
            $category = trim((string) ($match[1] ?? ''));
            $cdata = trim((string) ($match[3] ?? ''));
            if ($cdata === '') {
                continue;
            }

            $lines = preg_split('/\r\n|\r|\n/', $cdata) ?: [];
            $title = trim((string) ($lines[0] ?? ''));
            if ($title === '') {
                $title = self::categoryTitle($category);
            }

            $body = trim(implode("\n", array_slice($lines, 1)));
            $paragraphs = self::splitParagraphs($body !== '' ? $body : $cdata);

            $sections[] = [
                'title' => $title,
                'paragraphs' => $paragraphs,
            ];
        }

        return $sections;
    }

    /**
     * @return list<string>
     */
    private static function splitParagraphs(string $text): array
    {
        $chunks = preg_split("/\n{2,}/", trim($text)) ?: [];
        $paragraphs = [];

        foreach ($chunks as $chunk) {
            $chunk = trim((string) $chunk);
            if ($chunk !== '') {
                $paragraphs[] = $chunk;
            }
        }

        if ($paragraphs === [] && trim($text) !== '') {
            $paragraphs[] = trim($text);
        }

        return $paragraphs;
    }

    /**
     * @param  list<array{title: string, paragraphs: list<string>}>  $sections
     */
    private static function flattenSections(array $sections): string
    {
        $parts = [];

        foreach ($sections as $section) {
            $title = trim((string) ($section['title'] ?? ''));
            $paragraphs = $section['paragraphs'] ?? [];
            $body = implode("\n\n", array_map('strval', $paragraphs));

            if ($title !== '' && $body !== '') {
                $parts[] = $title . "\n" . $body;
            } elseif ($body !== '') {
                $parts[] = $body;
            }
        }

        return implode("\n\n", $parts);
    }

    private static function categoryTitle(string $category): string
    {
        return match ($category) {
            '0' => 'Application and Other Conditions',
            '2' => 'Day/Time',
            '4' => 'Flight Application',
            '5' => 'Advance Reservation/Ticketing',
            '6' => 'Minimum Stay',
            '7' => 'Maximum Stay',
            '8' => 'Stopovers',
            '9' => 'Transfers',
            '10' => 'Permitted Combinations',
            '12' => 'Surcharges',
            '14' => 'Travel Restrictions',
            '15' => 'Sales Restrictions',
            '16' => 'Penalties',
            '17' => 'Higher Intermediate Point',
            '18' => 'Ticket Endorsements',
            '19' => 'Children Discounts',
            '23' => 'Miscellaneous Provisions',
            '31' => 'Voluntary Changes',
            '33' => 'Voluntary Refunds',
            default => 'Fare Rule Category ' . $category,
        };
    }
}
