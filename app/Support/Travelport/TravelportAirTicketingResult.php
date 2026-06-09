<?php

namespace App\Support\Travelport;

final class TravelportAirTicketingResult
{
    /**
     * @param  array<string, mixed>  $ticketingRsp
     */
    public static function hasFailure(array $ticketingRsp): bool
    {
        if (isset($ticketingRsp['TicketFailureInfo'])) {
            return true;
        }

        $raw = $ticketingRsp['raw'] ?? null;

        return is_string($raw) && str_contains($raw, 'TicketFailureInfo');
    }

    /**
     * @param  array<string, mixed>  $ticketingRsp
     */
    public static function failureMessage(array $ticketingRsp): string
    {
        $info = $ticketingRsp['TicketFailureInfo'] ?? null;
        if (is_array($info)) {
            $attrs = $info['@attributes'] ?? $info;
            $code = trim((string) ($attrs['Code'] ?? ''));
            $message = trim((string) ($attrs['Message'] ?? ''));

            if ($message !== '' && $code !== '') {
                return "Travelport ticketing failed ({$code}): {$message}";
            }

            if ($message !== '') {
                return "Travelport ticketing failed: {$message}";
            }
        }

        $raw = $ticketingRsp['raw'] ?? null;
        if (is_string($raw) && preg_match('/TicketFailureInfo[^>]+Message="([^"]+)"/i', $raw, $m)) {
            $code = '';
            if (preg_match('/TicketFailureInfo[^>]+Code="([^"]+)"/i', $raw, $codeMatch)) {
                $code = trim($codeMatch[1]);
            }

            $message = trim($m[1]);

            return $code !== ''
                ? "Travelport ticketing failed ({$code}): {$message}"
                : "Travelport ticketing failed: {$message}";
        }

        return 'Travelport ticketing failed.';
    }

    /**
     * @param  array<string, mixed>  $ticketingRsp
     */
    public static function warningMessages(array $ticketingRsp): array
    {
        $messages = [];

        foreach (self::asList($ticketingRsp['ResponseMessage'] ?? null) as $responseMessage) {
            if (! is_array($responseMessage)) {
                continue;
            }

            $attrs = $responseMessage['@attributes'] ?? $responseMessage;
            $text = trim((string) ($responseMessage[0] ?? $responseMessage['#text'] ?? $attrs['#text'] ?? ''));
            if ($text === '' && is_string($attrs['Type'] ?? null)) {
                $text = trim((string) ($attrs['Type'] ?? ''));
            }

            if ($text !== '') {
                $messages[] = $text;
            }
        }

        $raw = $ticketingRsp['raw'] ?? null;
        if (is_string($raw) && preg_match_all('/<!\[CDATA\[([^\]]+)\]\]>/', $raw, $cdataMatches)) {
            foreach ($cdataMatches[1] as $text) {
                $text = trim($text);
                if ($text !== '') {
                    $messages[] = $text;
                }
            }
        }

        return array_values(array_unique($messages));
    }

    /**
     * @param  array<string, mixed>  $ticketingRsp
     * @param  list<string>  $ticketNumbers
     */
    public static function isSuccessful(array $ticketingRsp, array $ticketNumbers): bool
    {
        if (self::hasFailure($ticketingRsp)) {
            return false;
        }

        return $ticketNumbers !== [] || isset($ticketingRsp['ETR']);
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
}
