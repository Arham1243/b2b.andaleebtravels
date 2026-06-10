<?php

namespace App\Support\Travelport;

class TravelportHoldPayloadBuilder
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function buildAirPriceSegments(array $itineraryData): array
    {
        $segments = [];
        $seenKeys = [];
        $rawSegments = $itineraryData['travelport_segments'] ?? [];
        $defaultBookingCode = strtoupper(trim((string) ($itineraryData['booking_code'] ?? '')));

        foreach ($rawSegments as $seg) {
            if (! is_array($seg)) {
                continue;
            }
            $attrs = self::segmentAttributes($seg);
            $segmentKey = (string) ($attrs['Key'] ?? '');
            if ($segmentKey === '' || isset($seenKeys[$segmentKey])) {
                continue;
            }
            $seenKeys[$segmentKey] = true;
            $bookingCode = strtoupper(trim((string) ($seg['booking_code'] ?? $defaultBookingCode)));
            $classOfService = $bookingCode !== '' ? $bookingCode : ($attrs['ClassOfService'] ?? '');

            $segments[] = [
                'Key' => $segmentKey,
                'Group' => (string) ($attrs['Group'] ?? '0'),
                'ProviderCode' => (string) ($attrs['ProviderCode'] ?? '1G'),
                'Carrier' => (string) ($attrs['Carrier'] ?? ''),
                'FlightNumber' => (string) ($attrs['FlightNumber'] ?? ''),
                'Origin' => (string) ($attrs['Origin'] ?? ''),
                'Destination' => (string) ($attrs['Destination'] ?? ''),
                'DepartureTime' => (string) ($attrs['DepartureTime'] ?? ''),
                'ArrivalTime' => (string) ($attrs['ArrivalTime'] ?? ''),
                'ClassOfService' => $classOfService,
                'Equipment' => (string) ($attrs['Equipment'] ?? '320'),
            ];
        }

        return $segments;
    }

    /**
     * @return array<string, int>
     */
    public static function passengerCounts(array $params): array
    {
        return [
            'ADT' => max(1, (int) ($params['adults'] ?? 1)),
            'CNN' => max(0, (int) ($params['children'] ?? 0)),
            'INF' => max(0, (int) ($params['infants'] ?? 0)),
        ];
    }

    /**
     * @param  array<string, mixed>  $passengersData
     * @return list<array<string, mixed>>
     */
    public static function buildTravelers(array $passengersData): array
    {
        $lead = is_array($passengersData['lead'] ?? null) ? $passengersData['lead'] : [];
        $passengers = is_array($passengersData['passengers'] ?? null) ? $passengersData['passengers'] : [];
        $phone = self::parsePhone((string) ($lead['phone'] ?? ''));
        $email = trim((string) ($lead['email'] ?? ''));

        $travelers = [];
        $idx = 1;
        foreach ($passengers as $pax) {
            if (! is_array($pax)) {
                continue;
            }
            $type = match ((string) ($pax['type'] ?? 'ADT')) {
                'C06' => 'CNN',
                'INF' => 'INF',
                default => 'ADT',
            };

            $travelers[] = [
                'key' => "traveler_{$idx}",
                'traveler_type' => $type,
                'firstName' => trim((string) ($pax['first_name'] ?? '')),
                'lastName' => trim((string) ($pax['last_name'] ?? '')),
                'dob' => trim((string) ($pax['dob'] ?? '')),
                'gender' => self::genderFromTitle((string) ($pax['title'] ?? 'Mr')),
                'phoneCountryCode' => $phone['country'],
                'phoneAreaCode' => $phone['area'],
                'phoneNumber' => $phone['number'],
                'email' => $email,
            ];
            $idx++;
        }

        return $travelers;
    }

    public static function genderFromTitle(string $title): string
    {
        $normalized = strtolower(trim($title));

        return in_array($normalized, ['mrs', 'ms', 'miss'], true) ? 'F' : 'M';
    }

    /**
     * @param  list<array<string, mixed>>  $travelers
     * @return list<array{code: string, traveler_ref: string}>
     */
    public static function passengerTypesFromTravelers(array $travelers): array
    {
        $types = [];
        foreach ($travelers as $traveler) {
            $types[] = [
                'code' => (string) ($traveler['traveler_type'] ?? 'ADT'),
                'traveler_ref' => (string) ($traveler['key'] ?? ''),
            ];
        }

        return $types;
    }

    /**
     * @return array{country: string, area: string, number: string}
     */
    private static function parsePhone(string $phone): array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) >= 12) {
            return [
                'country' => substr($digits, 0, 2),
                'area' => substr($digits, 2, 3),
                'number' => substr($digits, 5),
            ];
        }
        if (strlen($digits) >= 10) {
            return [
                'country' => '971',
                'area' => substr($digits, 0, 3),
                'number' => substr($digits, 3),
            ];
        }

        return [
            'country' => '971',
            'area' => '50',
            'number' => $digits !== '' ? $digits : '0000000',
        ];
    }

    /**
     * @param  array<string, mixed>  $seg
     * @return array<string, mixed>
     */
    private static function segmentAttributes(array $seg): array
    {
        if (isset($seg['@attributes']) && is_array($seg['@attributes'])) {
            return $seg['@attributes'];
        }

        return $seg;
    }
}
