<?php

namespace App\Support;

final class SabreAirRulesResponseParser
{
    /**
     * Sabre returns DuplicateFareInfo when several routings match one fare basis.
     * A follow-up RuleReqInfo@RPH request is required to load Paragraph rules.
     */
    public static function needsRoutingSelection(string $xml): bool
    {
        if (self::hasDuplicateFareInfo($xml)) {
            return true;
        }

        return ! self::hasRuleParagraphs($xml);
    }

    public static function hasDuplicateFareInfo(string $xml): bool
    {
        return (bool) preg_match('/<DuplicateFareInfo\b/i', $xml);
    }

    public static function hasRuleParagraphs(string $xml): bool
    {
        return (bool) preg_match('/<Rules\b[^>]*>.*?<Paragraph\b/is', $xml);
    }

    /**
     * Pick the duplicate-fare line number (RPH) to request full rules for.
     */
    public static function resolveRoutingRph(string $xml, ?string $fareRuleCode = null): string
    {
        if (! preg_match('/<DuplicateFareInfo\b[^>]*>.*?<Text>(.*?)<\/Text>/is', $xml, $match)) {
            return '1';
        }

        $text = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $fareRuleCode = strtoupper(trim((string) ($fareRuleCode ?? '')));

        if ($fareRuleCode !== '') {
            foreach (preg_split('/\r\n|\n/', $text) ?: [] as $line) {
                if (stripos($line, $fareRuleCode) === false) {
                    continue;
                }

                if (preg_match('/^\s*(\d+)\s+/', $line, $lineMatch)) {
                    return $lineMatch[1];
                }
            }
        }

        if (preg_match('/^\s*1\s+/m', $text)) {
            return '1';
        }

        return '1';
    }

    /**
     * @return list<array{title: string, paragraphs: list<string>}>
     */
    public static function parse(string $xml): array
    {
        if (self::hasDuplicateFareInfo($xml)) {
            return [];
        }

        if (! preg_match('/<Rules\b[^>]*>(.*?)<\/Rules>/is', $xml, $rulesMatch)) {
            return [];
        }

        $sections = [];

        if (preg_match_all('/<Paragraph\b([^>]*)>(.*?)<\/Paragraph>/is', $rulesMatch[1], $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $title = self::attributeValue($match[1], 'Title') ?? 'Fare Rules';
                $body = self::extractTextBlock($match[2]);

                if ($body === '') {
                    continue;
                }

                $sections[] = [
                    'title' => trim(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                    'paragraphs' => self::splitParagraphs($body),
                ];
            }
        }

        return $sections;
    }

    private static function extractTextBlock(string $block): string
    {
        if (preg_match_all('/<Text\b[^>]*>(.*?)<\/Text>/is', $block, $matches)) {
            $lines = [];

            foreach ($matches[1] as $rawLine) {
                $line = trim(html_entity_decode(strip_tags($rawLine), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($line !== '') {
                    $lines[] = $line;
                }
            }

            return implode("\n", $lines);
        }

        return trim(html_entity_decode(strip_tags($block), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * @return list<string>
     */
    private static function splitParagraphs(string $body): array
    {
        $chunks = preg_split("/\n{2,}/", $body) ?: [];
        $paragraphs = [];

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);

            if ($chunk !== '') {
                $paragraphs[] = $chunk;
            }
        }

        return $paragraphs !== [] ? $paragraphs : [$body];
    }

    private static function attributeValue(string $attributeString, string $name): ?string
    {
        $pattern = '/' . preg_quote($name, '/') . '="([^"]*)"/i';

        if (preg_match($pattern, $attributeString, $match)) {
            return trim($match[1]);
        }

        return null;
    }
}
