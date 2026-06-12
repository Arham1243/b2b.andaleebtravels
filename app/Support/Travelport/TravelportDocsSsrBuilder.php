<?php

namespace App\Support\Travelport;

use Carbon\Carbon;

final class TravelportDocsSsrBuilder
{
    /**
     * APIS date format for SSR DOCS (e.g. 12JUL76).
     *
     * @see https://support.travelport.com/webhelp/Formats/Content/SSRsOSIs/ManualSSRs.htm
     */
    public static function formatApisDate(?string $date): string
    {
        $date = trim((string) $date);
        if ($date === '') {
            return '';
        }

        try {
            return strtoupper(Carbon::parse($date)->format('dMy'));
        } catch (\Throwable) {
            return '';
        }
    }

    public static function formatNamePart(string $name): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($name)) ?? '');
    }

    public static function genderCode(string $gender, string $travelerType): string
    {
        $type = strtoupper(trim($travelerType));
        $gender = strtoupper(trim($gender));

        if ($type === 'INF') {
            return $gender === 'F' ? 'FI' : 'MI';
        }

        return $gender === 'F' ? 'F' : 'M';
    }

    /**
     * Build SSR DOCS free text:
     * P/[issuing country]/[passport]/[nationality]/[DOB]/[gender]/[expiry]/[surname]/[firstname]
     *
     * @param  array<string, mixed>  $traveler
     * @return array{type: string, free_text: string}|null
     */
    public static function docsSsr(array $traveler): ?array
    {
        $passport = strtoupper(preg_replace('/\s+/', '', trim((string) (
            $traveler['passport_no']
            ?? $traveler['passportNumber']
            ?? ''
        ))) ?? '');

        if ($passport === '') {
            return null;
        }

        $issuing = strtoupper(trim((string) ($traveler['issuing_country'] ?? $traveler['issuingCountry'] ?? '')));
        $nationality = strtoupper(trim((string) ($traveler['nationality'] ?? '')));
        $dob = self::formatApisDate((string) ($traveler['dob'] ?? ''));
        $expiry = self::formatApisDate((string) ($traveler['passport_exp'] ?? $traveler['passportExpiry'] ?? ''));
        $last = self::formatNamePart((string) ($traveler['lastName'] ?? $traveler['last_name'] ?? ''));
        $first = self::formatNamePart((string) ($traveler['firstName'] ?? $traveler['first_name'] ?? ''));
        $type = strtoupper(trim((string) ($traveler['traveler_type'] ?? $traveler['traveler_type_code'] ?? 'ADT')));
        $gender = self::genderCode((string) ($traveler['gender'] ?? 'M'), $type);

        if ($issuing === '' || $nationality === '' || $dob === '' || $expiry === '' || $last === '' || $first === '') {
            return null;
        }

        $freeText = implode('/', [
            'P',
            $issuing,
            $passport,
            $nationality,
            $dob,
            $gender,
            $expiry,
            $last,
            $first,
        ]);

        return [
            'type' => 'DOCS',
            'free_text' => $freeText,
        ];
    }
}
