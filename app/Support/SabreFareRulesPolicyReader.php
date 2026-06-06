<?php

namespace App\Support;

final class SabreFareRulesPolicyReader
{
    /**
     * Parse ATPCO fare-rule text for refundability. Returns null when unclear.
     */
    public static function refundabilityFromRuleText(string $text): ?bool
    {
        $normalized = strtoupper(preg_replace('/\s+/', ' ', trim($text)) ?? '');

        if ($normalized === '') {
            return null;
        }

        foreach (self::nonRefundablePhrases() as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return false;
            }
        }

        foreach (self::refundablePhrases() as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $components
     */
    public static function refundabilityFromComponents(array $components): ?bool
    {
        $results = [];

        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            if (array_key_exists('refundable', $component) && is_bool($component['refundable'])) {
                $results[] = $component['refundable'];

                continue;
            }

            $text = trim((string) ($component['text'] ?? ''));

            if ($text === '' && ! empty($component['sections']) && is_array($component['sections'])) {
                $chunks = [];

                foreach ($component['sections'] as $section) {
                    if (! is_array($section)) {
                        continue;
                    }

                    if (! empty($section['title'])) {
                        $chunks[] = (string) $section['title'];
                    }

                    foreach ($section['paragraphs'] ?? [] as $paragraph) {
                        $chunks[] = (string) $paragraph;
                    }
                }

                $text = implode("\n", $chunks);
            }

            $parsed = self::refundabilityFromRuleText($text);

            if ($parsed !== null) {
                $results[] = $parsed;
            }
        }

        if ($results === []) {
            return null;
        }

        if (in_array(false, $results, true)) {
            return false;
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private static function nonRefundablePhrases(): array
    {
        return [
            'TICKET IS NON-REFUNDABLE',
            'NON-REFUNDABLE IN CASE OF CANCEL',
            'NON-REFUNDABLE IN CASE OF CANCEL/REFUND',
            'CANCELLATIONS ARE NOT PERMITTED',
            'CANCELLATION IS NOT PERMITTED',
            'CANCEL/REFUND IS NOT PERMITTED',
            'REFUND IS NOT PERMITTED',
            'NO REFUND',
            'NOT REFUNDABLE',
        ];
    }

    /**
     * @return list<string>
     */
    private static function refundablePhrases(): array
    {
        return [
            'FULLY REFUNDABLE',
            'REFUND PERMITTED',
            'CANCEL/REFUND PERMITTED',
            'CANCELLATIONS PERMITTED',
        ];
    }
}
