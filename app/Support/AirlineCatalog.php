<?php

namespace App\Support;

final class AirlineCatalog
{
    /** @var array<string, string> */
    private const NAMES = [
        'EK' => 'Emirates',
        'QR' => 'Qatar Airways',
        'EY' => 'Etihad Airways',
        'FZ' => 'flydubai',
        'WY' => 'Oman Air',
        'GF' => 'Gulf Air',
        'SV' => 'Saudia',
        'PK' => 'PIA',
        'AI' => 'Air India',
        '6E' => 'IndiGo',
        'SG' => 'SpiceJet',
        'UK' => 'Vistara',
        'BA' => 'British Airways',
        'LH' => 'Lufthansa',
        'AF' => 'Air France',
        'KL' => 'KLM',
        'TK' => 'Turkish Airlines',
        'SQ' => 'Singapore Airlines',
        'CX' => 'Cathay Pacific',
        'TG' => 'Thai Airways',
        'MH' => 'Malaysia Airlines',
        'UL' => 'SriLankan Airlines',
        'BG' => 'Biman Bangladesh Airlines',
        'RJ' => 'Royal Jordanian',
        'MS' => 'EgyptAir',
        'KU' => 'Kuwait Airways',
    ];

    public static function name(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        return self::NAMES[$code] ?? null;
    }
}
