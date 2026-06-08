<?php

namespace App\Support;

/**
 * When Sabre OTA_AirRulesLLS cannot resolve ATPCO rule text (common for NDC/branded/LCC fares),
 * return the structured penalties and policy summary captured at shop time.
 */
final class SabreStructuredFareRulesFallback
{
    /**
     * @return list<array{
     *     route: string,
     *     fare_basis: string,
     *     sections: list<array{title: string, paragraphs: list<string>}>,
     *     text: string
     * }>
     */
    public static function toComponents(?array $fareRules): array
    {
        if (! is_array($fareRules) || $fareRules === []) {
            return [];
        }

        $sharedSections = self::buildSharedSections($fareRules);
        $components = [];

        foreach ($fareRules['components'] ?? [] as $component) {
            if (! is_array($component)) {
                continue;
            }

            $route = trim((string) ($component['route'] ?? ''));
            $basis = trim((string) ($component['fare_basis'] ?? ''));
            $sections = $sharedSections;
            $segmentNotes = [];

            if ($basis !== '') {
                $segmentNotes[] = 'Fare basis: ' . $basis;
            }

            if (! empty($component['brand'])) {
                $segmentNotes[] = 'Brand: ' . (string) $component['brand'];
            }

            if (! empty($component['cabin'])) {
                $segmentNotes[] = 'Cabin: ' . (string) $component['cabin'];
            }

            if (! empty($component['valid_from_display']) || ! empty($component['valid_to_display'])) {
                $from = (string) ($component['valid_from_display'] ?? '');
                $to = (string) ($component['valid_to_display'] ?? '');
                $segmentNotes[] = trim('Travel validity: ' . $from . ($to !== '' ? ' – ' . $to : ''));
            }

            if ($segmentNotes !== []) {
                $sections = array_merge([[
                    'title' => 'Fare summary',
                    'paragraphs' => $segmentNotes,
                ]], $sections);
            }

            if ($sections === []) {
                continue;
            }

            $components[] = [
                'route' => $route,
                'fare_basis' => $basis,
                'sections' => $sections,
                'text' => self::flattenSections($sections),
            ];
        }

        if ($components === [] && $sharedSections !== []) {
            $components[] = [
                'route' => '',
                'fare_basis' => '',
                'sections' => $sharedSections,
                'text' => self::flattenSections($sharedSections),
            ];
        }

        return $components;
    }

    /**
     * @return list<array{title: string, paragraphs: list<string>}>
     */
    private static function buildSharedSections(array $fareRules): array
    {
        $sections = [];

        $overview = [];
        if (! empty($fareRules['fare_brand'])) {
            $overview[] = 'Product: ' . (string) $fareRules['fare_brand'];
        }
        if (! empty($fareRules['refund_label'])) {
            $overview[] = (string) $fareRules['refund_label'];
        }
        if (! empty($fareRules['validating_carrier'])) {
            $overview[] = 'Validating carrier: ' . (string) $fareRules['validating_carrier'];
        }
        if (! empty($fareRules['last_ticket_display'])) {
            $overview[] = 'Ticket by: ' . (string) $fareRules['last_ticket_display'];
        }

        if ($overview !== []) {
            $sections[] = [
                'title' => 'Overview',
                'paragraphs' => $overview,
            ];
        }

        foreach ($fareRules['policy_sections'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }

            $items = array_values(array_filter(array_map(
                static fn ($item): string => trim((string) $item),
                is_array($section['items'] ?? null) ? $section['items'] : [],
            )));

            if ($items === []) {
                continue;
            }

            $sections[] = [
                'title' => trim((string) ($section['title'] ?? 'Policy')) ?: 'Policy',
                'paragraphs' => $items,
            ];
        }

        $notes = array_values(array_filter(array_map(
            static fn ($note): string => trim((string) $note),
            is_array($fareRules['notes'] ?? null) ? $fareRules['notes'] : [],
        )));

        if ($notes !== []) {
            $sections[] = [
                'title' => 'Notes',
                'paragraphs' => $notes,
            ];
        }

        if ($sections !== []) {
            $sections[] = [
                'title' => 'Full ATPCO rule text',
                'paragraphs' => [
                    'Sabre could not retrieve the long-form tariff rule text for this fare basis on this city pair and date. The summary above is taken from the original shop result (penalties, refundability, and ticketing limits).',
                ],
            ];
        }

        return $sections;
    }

    /**
     * @param  list<array{title: string, paragraphs: list<string>}>  $sections
     */
    private static function flattenSections(array $sections): string
    {
        $chunks = [];

        foreach ($sections as $section) {
            $title = trim((string) ($section['title'] ?? ''));
            if ($title !== '') {
                $chunks[] = $title;
            }

            foreach ($section['paragraphs'] ?? [] as $paragraph) {
                $paragraph = trim((string) $paragraph);
                if ($paragraph !== '') {
                    $chunks[] = $paragraph;
                }
            }
        }

        return implode("\n\n", $chunks);
    }
}
