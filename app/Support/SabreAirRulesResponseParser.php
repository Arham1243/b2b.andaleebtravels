<?php

namespace App\Support;

final class SabreAirRulesResponseParser
{
    /**
     * @return list<array{title: string, paragraphs: list<string>}>
     */
    public static function parse(string $xml): array
    {
        $sections = [];
        $rulesBlock = '';

        if (preg_match('/<Rules\b[^>]*>(.*?)<\/Rules>/is', $xml, $rulesMatch)) {
            $rulesBlock = $rulesMatch[1];
        }

        $paragraphSource = $rulesBlock !== '' ? $rulesBlock : $xml;

        if (preg_match_all('/<Paragraph\b([^>]*)>(.*?)<\/Paragraph>/is', $paragraphSource, $matches, PREG_SET_ORDER)) {
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

        if ($sections === [] && preg_match('/<Header\b[^>]*>(.*?)<\/Header>/is', $xml, $headerMatch)) {
            $headerSections = self::parseHeaderLines($headerMatch[1]);
            if ($headerSections !== []) {
                return $headerSections;
            }
        }

        if ($sections === [] && preg_match_all('/<Text\b[^>]*>(.*?)<\/Text>/is', $paragraphSource, $textMatches)) {
            $lines = [];

            foreach ($textMatches[1] as $rawLine) {
                $line = trim(html_entity_decode(strip_tags($rawLine), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($line !== '') {
                    $lines[] = $line;
                }
            }

            if ($lines !== []) {
                $sections[] = [
                    'title' => 'Fare Rules',
                    'paragraphs' => $lines,
                ];
            }
        }

        return $sections;
    }

    /**
     * @return list<array{title: string, paragraphs: list<string>}>
     */
    private static function parseHeaderLines(string $headerXml): array
    {
        if (! preg_match_all('/<Line\b([^>]*)>(.*?)<\/Line>/is', $headerXml, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $sections = [];

        foreach ($matches as $match) {
            $title = self::attributeValue($match[1], 'Type') ?? 'Details';
            $text = self::extractTextBlock($match[2]);

            if ($text === '') {
                continue;
            }

            $sections[] = [
                'title' => trim(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'paragraphs' => self::splitParagraphs($text),
            ];
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
