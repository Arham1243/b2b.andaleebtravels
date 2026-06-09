<?php

namespace App\Support;

use App\Models\B2bFlightBooking;

final class FlightBookingTicketResolver
{
    /**
     * @return list<string>
     */
    public static function forBooking(B2bFlightBooking $booking): array
    {
        $stored = self::normalizeList($booking->ticket_numbers);
        if ($stored !== []) {
            return $stored;
        }

        $response = is_array($booking->ticket_response) ? $booking->ticket_response : null;
        $numbers = self::fromResponse($response);

        if ($numbers !== [] && $booking->ticket_status === 'issued' && $booking->exists) {
            $booking->forceFill(['ticket_numbers' => $numbers])->saveQuietly();
        }

        return $numbers;
    }

    /**
     * @param  array<string, mixed>|null  $ticketResponse
     * @return list<string>
     */
    public static function fromResponse(?array $ticketResponse): array
    {
        if ($ticketResponse === null || $ticketResponse === []) {
            return [];
        }

        $numbers = [];

        foreach ([
            'AirTicketRS.ETicketNumber',
            'eTicketNumber',
            'ETR.TicketNumber',
            'ETR.@attributes.TicketNumber',
            'AirTicketRS.Summary.DocumentNumber',
            'Summary.DocumentNumber',
        ] as $path) {
            $val = data_get($ticketResponse, $path);
            self::pushNumber($numbers, $val);
        }

        foreach (self::asList(data_get($ticketResponse, 'AirTicketRS.Summary')) as $summary) {
            if (! is_array($summary)) {
                continue;
            }

            self::pushNumber($numbers, data_get($summary, 'DocumentNumber'));
        }

        foreach (self::asList(data_get($ticketResponse, 'AirTicketRS.TicketNumberInfo')) as $info) {
            if (! is_array($info)) {
                continue;
            }

            self::pushNumber($numbers, data_get($info, 'TicketNumber'));
        }

        foreach (self::asList(data_get($ticketResponse, 'ETR')) as $etr) {
            if (! is_array($etr)) {
                continue;
            }

            $num = self::nodeAttr($etr, 'TicketNumber')
                ?? data_get($etr, 'Ticket.@attributes.Number')
                ?? data_get($etr, 'Ticket.Number');

            self::pushNumber($numbers, $num);
        }

        self::collectFromTree($ticketResponse, $numbers);

        $raw = data_get($ticketResponse, 'raw');
        if (is_string($raw) && $raw !== '') {
            if (preg_match_all('/<(?:[\w-]+:)?TicketNumber[^>]*>([^<]+)</i', $raw, $matches)) {
                foreach ($matches[1] as $num) {
                    self::pushNumber($numbers, $num);
                }
            }

            if (preg_match_all('/TicketNumber="([^"]+)"/i', $raw, $attrMatches)) {
                foreach ($attrMatches[1] as $num) {
                    self::pushNumber($numbers, $num);
                }
            }

            if (preg_match_all('/<(?:[\w-]+:)?DocumentNumber[^>]*>([^<]+)</i', $raw, $docMatches)) {
                foreach ($docMatches[1] as $num) {
                    self::pushNumber($numbers, $num);
                }
            }

            if (preg_match_all('/<(?:[\w-]+:)?Ticket[^>]*\bNumber="([^"]+)"/i', $raw, $ticketAttrMatches)) {
                foreach ($ticketAttrMatches[1] as $num) {
                    self::pushNumber($numbers, $num);
                }
            }
        }

        return array_values(array_unique($numbers));
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    public static function normalizeList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $numbers = [];
        foreach ($value as $item) {
            self::pushNumber($numbers, $item);
        }

        return array_values(array_unique($numbers));
    }

    /**
     * @param  list<string>  $numbers
     */
    private static function collectFromTree(mixed $node, array &$numbers): void
    {
        if (! is_array($node)) {
            return;
        }

        if (isset($node['@attributes']) && is_array($node['@attributes'])) {
            foreach (['TicketNumber', 'Number'] as $attr) {
                self::pushNumber($numbers, $node['@attributes'][$attr] ?? null);
            }
        }

        foreach (['DocumentNumber', 'TicketNumber', 'eTicketNumber'] as $key) {
            if (array_key_exists($key, $node)) {
                self::pushNumber($numbers, $node[$key]);
            }
        }

        if (isset($node['Ticket']) && is_array($node['Ticket'])) {
            self::pushNumber($numbers, data_get($node, 'Ticket.@attributes.Number'));
            self::pushNumber($numbers, data_get($node, 'Ticket.Number'));
        }

        foreach ($node as $key => $child) {
            if ($key === 'raw' || $key === '@attributes') {
                continue;
            }

            if (is_array($child)) {
                self::collectFromTree($child, $numbers);
            }
        }
    }

    /**
     * @param  list<string>  $numbers
     */
    private static function pushNumber(array &$numbers, mixed $value): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return;
        }

        $normalized = self::normalizeTicketNumber((string) $value);
        if ($normalized === null) {
            return;
        }

        $numbers[] = $normalized;
    }

    private static function normalizeTicketNumber(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) < 10 || strlen($digits) > 14) {
            return null;
        }

        return $digits;
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

        return array_is_list($value) ? $value : [$value];
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function nodeAttr(array $node, string $name): ?string
    {
        $val = $node['@attributes'][$name] ?? $node[$name] ?? null;

        return is_string($val) && trim($val) !== '' ? trim($val) : null;
    }
}
