<?php

namespace App\Support\Travelport;

use App\Support\FlightPassengerDobValidator;
use App\Support\PhoneDialCodeCatalog;
use Carbon\Carbon;

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
     * Build BookingTravelers in Travelport order: each adult, then their lap infant(s), then children.
     * Uses plain CNN (never CHD or CNN{age}) with DOB for child/infant passengers.
     *
     * @param  array<string, mixed>  $passengersData
     * @return list<array<string, mixed>>
     */
    public static function buildTravelers(array $passengersData, array $searchData = []): array
    {
        $lead = is_array($passengersData['lead'] ?? null) ? $passengersData['lead'] : [];
        $passengers = is_array($passengersData['passengers'] ?? null) ? $passengersData['passengers'] : [];
        $phone = self::resolvePhoneFromLead($lead);
        $email = trim((string) ($lead['email'] ?? ''));
        $referenceDate = FlightPassengerDobValidator::resolveReferenceDate($searchData);

        $adults = [];
        $children = [];
        $infants = [];

        foreach ($passengers as $pax) {
            if (! is_array($pax)) {
                continue;
            }

            $rawType = strtoupper(trim((string) ($pax['type'] ?? 'ADT')));
            if (in_array($rawType, ['C06', 'CNN', 'CHD'], true)) {
                $children[] = $pax;
            } elseif ($rawType === 'INF') {
                $infants[] = $pax;
            } else {
                $adults[] = $pax;
            }
        }

        if ($adults === []) {
            throw new \InvalidArgumentException('At least one adult traveler is required.');
        }

        if (count($infants) > count($adults)) {
            throw new \InvalidArgumentException('Each infant must travel with an adult. Number of infants cannot exceed number of adults.');
        }

        $infantsByAdult = array_fill(0, count($adults), []);
        foreach ($infants as $infantIndex => $infantPax) {
            $adultIndex = (int) ($infantPax['accompanying_adult'] ?? $infantIndex);
            $adultIndex = max(0, min(count($adults) - 1, $adultIndex));
            $infantsByAdult[$adultIndex][] = $infantPax;
        }

        $travelers = [];
        $idx = 1;

        foreach ($adults as $adultIndex => $adultPax) {
            $adultKey = 'traveler_' . $idx;
            $adultTraveler = self::mapPassengerToTraveler($adultPax, $adultKey, $phone, $email, $referenceDate);
            $lapInfantRemarks = [];

            foreach ($infantsByAdult[$adultIndex] as $infantPax) {
                $lapInfantRemarks[] = self::lapInfantNameRemarkForAdult($infantPax);
            }

            if ($lapInfantRemarks !== []) {
                $adultTraveler['lap_infant_name_remarks'] = $lapInfantRemarks;
            }

            $travelers[] = $adultTraveler;
            $idx++;

            foreach ($infantsByAdult[$adultIndex] as $infantPax) {
                $infantKey = 'traveler_' . $idx;
                $travelers[] = self::mapPassengerToTraveler(
                    $infantPax,
                    $infantKey,
                    $phone,
                    $email,
                    $referenceDate,
                    $adultKey,
                );
                $idx++;
            }
        }

        foreach ($children as $childPax) {
            $travelers[] = self::mapPassengerToTraveler(
                $childPax,
                'traveler_' . $idx,
                $phone,
                $email,
                $referenceDate,
            );
            $idx++;
        }

        return $travelers;
    }

    /**
     * @param  array<string, mixed>  $pax
     * @param  array{country: string, area: string, number: string}  $phone
     * @return array<string, mixed>
     */
    private static function mapPassengerToTraveler(
        array $pax,
        string $key,
        array $phone,
        string $email,
        Carbon $referenceDate,
        ?string $accompanyingAdultKey = null,
    ): array {
        $type = match (strtoupper(trim((string) ($pax['type'] ?? 'ADT')))) {
            'C06', 'CNN', 'CHD' => 'CNN',
            'INF' => 'INF',
            default => 'ADT',
        };
        $dob = trim((string) ($pax['dob'] ?? ''));

        if (in_array($type, ['CNN', 'INF'], true) && $dob === '') {
            throw new \InvalidArgumentException('Date of birth is required for child and infant passengers.');
        }

        $traveler = [
            'key' => $key,
            'traveler_type' => $type,
            'traveler_type_code' => self::travelportHoldPassengerTypeCode($type, $dob, $referenceDate),
            'firstName' => trim((string) ($pax['first_name'] ?? '')),
            'lastName' => trim((string) ($pax['last_name'] ?? '')),
            'dob' => $dob,
            'gender' => self::genderFromTitle((string) ($pax['title'] ?? 'Mr')),
            'phoneCountryCode' => $phone['country'],
            'phoneAreaCode' => $phone['area'],
            'phoneNumber' => $phone['number'],
            'email' => $email,
        ];

        if ($accompanyingAdultKey !== null && $accompanyingAdultKey !== '') {
            $traveler['accompanying_adult_key'] = $accompanyingAdultKey;
        }

        if ($type === 'INF' && $dob !== '') {
            $traveler['name_remark'] = self::infantDobNameRemark($dob);
        }

        return $traveler;
    }

    /**
     * @param  array<string, mixed>  $infantPax
     */
    private static function lapInfantNameRemarkForAdult(array $infantPax): string
    {
        $last = strtoupper(preg_replace('/\s+/', '', (string) ($infantPax['last_name'] ?? '')) ?? '');
        $first = strtoupper(preg_replace('/\s+/', '', (string) ($infantPax['first_name'] ?? '')) ?? '');
        $dob = trim((string) ($infantPax['dob'] ?? ''));

        return 'INFT/' . $last . '/' . $first . ' ' . self::infantDobNameRemark($dob);
    }

    private static function infantDobNameRemark(string $dob): string
    {
        return strtoupper(Carbon::parse($dob)->format('dMy'));
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
                'code' => (string) ($traveler['traveler_type'] ?? $traveler['traveler_type_code'] ?? 'ADT'),
                'traveler_ref' => (string) ($traveler['key'] ?? ''),
            ];
        }

        return $types;
    }

    /**
     * Guarantee CNN SearchPassenger ages for LFS / Air Price when children are in the search.
     *
     * @param  array<string, mixed>  $searchData
     * @return array<string, mixed>
     */
    public static function ensureChildAgesInSearchData(array $searchData): array
    {
        $children = max(0, (int) ($searchData['children'] ?? 0));
        if ($children <= 0) {
            return $searchData;
        }

        $childAges = [];
        if (isset($searchData['child_ages']) && is_array($searchData['child_ages'])) {
            foreach ($searchData['child_ages'] as $age) {
                $age = (int) $age;
                if ($age >= 2 && $age <= 11) {
                    $childAges[] = $age;
                }
            }
        }

        while (count($childAges) < $children) {
            $childAges[] = 8;
        }

        $searchData['child_ages'] = array_slice($childAges, 0, $children);
        $searchData['child_age'] = $searchData['child_ages'][0] ?? 8;

        $infants = max(0, (int) ($searchData['infants'] ?? 0));
        if ($infants > 0) {
            $infantAges = [];
            if (isset($searchData['infant_ages']) && is_array($searchData['infant_ages'])) {
                foreach ($searchData['infant_ages'] as $age) {
                    $infantAges[] = max(0, min(1, (int) $age));
                }
            }

            while (count($infantAges) < $infants) {
                $infantAges[] = 1;
            }

            $searchData['infant_ages'] = array_slice($infantAges, 0, $infants);
            $searchData['infant_age'] = $searchData['infant_ages'][0] ?? 1;
        }

        return $searchData;
    }

    /**
     * @param  array<string, mixed>  $searchData
     * @param  array<string, mixed>  $passengersData
     * @return array<string, mixed>
     */
    public static function enrichSearchDataWithPassengerAges(array $searchData, array $passengersData): array
    {
        $passengers = is_array($passengersData['passengers'] ?? null) ? $passengersData['passengers'] : [];
        $referenceDate = FlightPassengerDobValidator::resolveReferenceDate($searchData);
        $childAges = [];
        $infantAges = [];

        foreach ($passengers as $passenger) {
            if (! is_array($passenger)) {
                continue;
            }

            $type = strtoupper(trim((string) ($passenger['type'] ?? 'ADT')));
            $dob = trim((string) ($passenger['dob'] ?? ''));
            if ($dob === '') {
                continue;
            }

            $age = FlightPassengerDobValidator::ageOnDate($dob, $referenceDate);

            if (in_array($type, ['C06', 'CNN', 'CHD'], true)) {
                $childAges[] = max(2, min(11, $age));
            } elseif ($type === 'INF') {
                $infantAges[] = max(0, min(1, $age));
            }
        }

        if ($childAges !== []) {
            $searchData['child_ages'] = $childAges;
            $searchData['child_age'] = $childAges[0];
        }

        if ($infantAges !== []) {
            $searchData['infant_ages'] = $infantAges;
            $searchData['infant_age'] = $infantAges[0];
        }

        return self::ensureChildAgesInSearchData($searchData);
    }

    /**
     * PTC for AirCreateReservation — plain CNN/INF/ADT only (age is on BookingTraveler DOB).
     * This PCC rejects age-qualified codes such as CNN04 or CNN08.
     */
    public static function travelportHoldPassengerTypeCode(
        string $type,
        string $dob = '',
        ?Carbon $referenceDate = null,
    ): string {
        unset($referenceDate);

        $normalized = strtoupper(trim($type));

        if (in_array($normalized, ['C06', 'CNN', 'CHD'], true)) {
            if ($dob === '') {
                throw new \InvalidArgumentException('Date of birth is required for child passengers.');
            }

            return 'CNN';
        }

        if ($normalized === 'INF') {
            if ($dob === '') {
                throw new \InvalidArgumentException('Date of birth is required for infant passengers.');
            }

            return 'INF';
        }

        return 'ADT';
    }

    public static function normalizeHoldPassengerTypeCode(string $code): string
    {
        $normalized = strtoupper(trim($code));

        if (preg_match('/^CNN\d{2}$/', $normalized) === 1 || in_array($normalized, ['CHD', 'C06'], true)) {
            return 'CNN';
        }

        return $normalized !== '' ? $normalized : 'ADT';
    }

    /**
     * Normalize checkout/hold lead contact fields for Travelport.
     *
     * @param  array<string, mixed>  $lead
     * @return array<string, mixed>
     */
    public static function normalizeLeadPhone(array $lead): array
    {
        $dialCode = preg_replace('/\D+/', '', (string) ($lead['phone_dial_code'] ?? '')) ?? '';
        $local = preg_replace('/\D+/', '', (string) ($lead['phone_local'] ?? '')) ?? '';
        $local = ltrim($local, '0');

        if ($dialCode !== '' && $local !== '') {
            $parts = self::splitLocalNumber($dialCode, $local);
            $lead['phone_dial_code'] = $parts['country'];
            $lead['phone_area_code'] = $parts['area'];
            $lead['phone_number'] = $parts['number'];
            $lead['phone'] = '+' . $parts['country'] . $local;

            return $lead;
        }

        $parts = self::parsePhone((string) ($lead['phone'] ?? ''));
        $lead['phone_dial_code'] = $parts['country'];
        $lead['phone_area_code'] = $parts['area'];
        $lead['phone_number'] = $parts['number'];
        $lead['phone'] = $lead['phone'] !== ''
            ? (string) $lead['phone']
            : '+' . $parts['country'] . $parts['area'] . $parts['number'];

        return $lead;
    }

    /**
     * @return array{dial_code: string, local: string, iso: string}
     */
    public static function parseLeadPhoneForForm(?string $phone, ?string $dialCode = null, ?string $local = null): array
    {
        $dialCode = preg_replace('/\D+/', '', (string) $dialCode) ?? '';
        $local = preg_replace('/\D+/', '', (string) $local) ?? '';
        $local = ltrim($local, '0');

        if ($dialCode !== '' && $local !== '') {
            return [
                'dial_code' => $dialCode,
                'local' => $local,
                'iso' => PhoneDialCodeCatalog::isoFromDialCode($dialCode),
            ];
        }

        $parts = self::parsePhone((string) $phone);
        $combinedLocal = ltrim((string) ($parts['area'] . $parts['number']), '0');

        return [
            'dial_code' => $parts['country'],
            'local' => $combinedLocal !== '' ? $combinedLocal : ltrim(preg_replace('/\D+/', '', (string) $phone) ?? '', '0'),
            'iso' => PhoneDialCodeCatalog::isoFromDialCode($parts['country']),
        ];
    }

    /**
     * @param  array<string, mixed>  $lead
     * @return array{country: string, area: string, number: string}
     */
    public static function resolvePhoneFromLead(array $lead): array
    {
        $dialCode = preg_replace('/\D+/', '', (string) ($lead['phone_dial_code'] ?? '')) ?? '';
        $area = preg_replace('/\D+/', '', (string) ($lead['phone_area_code'] ?? '')) ?? '';
        $number = preg_replace('/\D+/', '', (string) ($lead['phone_number'] ?? '')) ?? '';
        $local = preg_replace('/\D+/', '', (string) ($lead['phone_local'] ?? '')) ?? '';
        $local = ltrim($local, '0');

        if ($dialCode !== '' && $area !== '' && $number !== '') {
            return [
                'country' => $dialCode,
                'area' => $area,
                'number' => $number,
            ];
        }

        if ($dialCode !== '' && $local !== '') {
            return self::splitLocalNumber($dialCode, $local);
        }

        return self::parsePhone((string) ($lead['phone'] ?? ''));
    }

    /**
     * @return array{country: string, area: string, number: string}
     */
    public static function splitLocalNumber(string $dialCode, string $local): array
    {
        $dialCode = ltrim(preg_replace('/\D+/', '', $dialCode) ?? '', '0');
        $local = ltrim(preg_replace('/\D+/', '', $local) ?? '', '0');

        if ($dialCode === '' || $local === '') {
            return [
                'country' => $dialCode !== '' ? $dialCode : '971',
                'area' => '50',
                'number' => $local !== '' ? $local : '0000000',
            ];
        }

        $areaLength = self::defaultAreaLength($dialCode, $local);
        $area = substr($local, 0, $areaLength);
        $number = substr($local, $areaLength);

        if ($number === '') {
            $area = substr($local, 0, max(1, min(3, strlen($local) - 4)));
            $number = substr($local, strlen($area));
        }

        return [
            'country' => $dialCode,
            'area' => $area !== '' ? $area : '0',
            'number' => $number !== '' ? $number : $local,
        ];
    }

    private static function defaultAreaLength(string $dialCode, string $local): int
    {
        if ($dialCode === '971') {
            return strlen($local) >= 2 && $local[0] === '5' ? 2 : 1;
        }

        if ($dialCode === '966') {
            return 2;
        }

        if (in_array($dialCode, ['974', '973', '968', '965'], true)) {
            return 2;
        }

        if ($dialCode === '92') {
            return min(3, max(2, strlen($local) - 7));
        }

        if ($dialCode === '91') {
            return min(5, max(3, strlen($local) - 5));
        }

        if ($dialCode === '1') {
            return 3;
        }

        if ($dialCode === '44') {
            return strlen($local) >= 2 && $local[0] === '7' ? 5 : 4;
        }

        return strlen($local) >= 8 ? 3 : 2;
    }

    /**
     * @return array{country: string, area: string, number: string}
     */
    private static function parsePhone(string $phone): array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        foreach (PhoneDialCodeCatalog::dialCodesLongestFirst() as $dialCode) {
            if (! str_starts_with($digits, $dialCode)) {
                continue;
            }

            $local = substr($digits, strlen($dialCode));
            if (strlen($local) >= 5) {
                return self::splitLocalNumber($dialCode, $local);
            }
        }

        if (strlen($digits) >= 10) {
            return self::splitLocalNumber('971', $digits);
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
