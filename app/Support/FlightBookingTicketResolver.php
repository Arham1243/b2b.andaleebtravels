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
        $response = is_array($booking->ticket_response) ? $booking->ticket_response : null;

        return self::fromResponse($response);
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
        ] as $path) {
            $val = data_get($ticketResponse, $path);
            if (is_string($val) && trim($val) !== '') {
                $numbers[] = trim($val);
            }
        }

        foreach (self::asList(data_get($ticketResponse, 'AirTicketRS.TicketNumberInfo')) as $info) {
            if (! is_array($info)) {
                continue;
            }

            $num = data_get($info, 'TicketNumber');
            if (is_string($num) && trim($num) !== '') {
                $numbers[] = trim($num);
            }
        }

        foreach (self::asList(data_get($ticketResponse, 'ETR')) as $etr) {
            if (! is_array($etr)) {
                continue;
            }

            $num = self::nodeAttr($etr, 'TicketNumber')
                ?? data_get($etr, 'Ticket.@attributes.Number')
                ?? data_get($etr, 'Ticket.Number');

            if (is_string($num) && trim($num) !== '') {
                $numbers[] = trim($num);
            }
        }

        $raw = data_get($ticketResponse, 'raw');
        if (is_string($raw)) {
            if (preg_match_all('/<(?:[\w-]+:)?TicketNumber[^>]*>([^<]+)</i', $raw, $matches)) {
                foreach ($matches[1] as $num) {
                    $num = trim($num);
                    if ($num !== '') {
                        $numbers[] = $num;
                    }
                }
            }

            if (preg_match_all('/TicketNumber="([^"]+)"/i', $raw, $attrMatches)) {
                foreach ($attrMatches[1] as $num) {
                    $num = trim($num);
                    if ($num !== '') {
                        $numbers[] = $num;
                    }
                }
            }
        }

        return array_values(array_unique($numbers));
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
