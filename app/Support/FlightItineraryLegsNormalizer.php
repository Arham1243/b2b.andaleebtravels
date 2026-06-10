<?php

namespace App\Support;

use App\Models\B2bFlightBooking;
use Carbon\Carbon;

final class FlightItineraryLegsNormalizer
{
    /**
     * @param  list<array<string, mixed>>  $legs
     * @param  list<array<string, mixed>>  $coupons
     * @return list<array<string, mixed>>
     */
    public static function normalize(array $legs, B2bFlightBooking $booking, array $coupons = []): array
    {
        $normalized = self::dedupeExactLegs($legs);

        if ($booking->return_date === null) {
            return $normalized;
        }

        return self::ensureRoundTripLegs($normalized, $booking, $coupons);
    }

    /**
     * @param  list<array<string, mixed>>  $legs
     * @return list<array<string, mixed>>
     */
    private static function dedupeExactLegs(array $legs): array
    {
        $unique = [];
        $seen = [];

        foreach ($legs as $leg) {
            if (! is_array($leg)) {
                continue;
            }

            $signature = self::legSignature($leg);
            if ($signature !== '' && isset($seen[$signature])) {
                continue;
            }

            if ($signature !== '') {
                $seen[$signature] = true;
            }

            $unique[] = $leg;
        }

        return $unique;
    }

    /**
     * @param  list<array<string, mixed>>  $legs
     * @param  list<array<string, mixed>>  $coupons
     * @return list<array<string, mixed>>
     */
    private static function ensureRoundTripLegs(array $legs, B2bFlightBooking $booking, array $coupons): array
    {
        $onward = $legs[0] ?? null;
        if (! is_array($onward)) {
            return $legs;
        }

        $return = null;
        foreach (array_slice($legs, 1) as $leg) {
            if (! is_array($leg)) {
                continue;
            }

            if (self::legRouteDiffers($onward, $leg)) {
                $return = $leg;
                break;
            }
        }

        if ($return === null) {
            $return = self::buildReturnLeg($onward, $booking, $coupons);
        }

        if ($return === null) {
            return [$onward];
        }

        return [$onward, $return];
    }

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     */
    private static function legRouteDiffers(array $left, array $right): bool
    {
        if (self::legSignature($left) === self::legSignature($right)) {
            return false;
        }

        return self::legEndpoints($left) !== self::legEndpoints($right);
    }

    /**
     * @param  array<string, mixed>  $leg
     */
    private static function legEndpoints(array $leg): string
    {
        $segments = is_array($leg['segments'] ?? null) ? $leg['segments'] : [];
        $first = is_array($segments[0] ?? null) ? $segments[0] : [];
        $last = is_array($segments[array_key_last($segments)] ?? null) ? $segments[array_key_last($segments)] : [];

        $from = strtoupper(trim((string) ($first['from'] ?? '')));
        $to = strtoupper(trim((string) ($last['to'] ?? '')));

        return $from . ':' . $to;
    }

    /**
     * @param  array<string, mixed>  $leg
     */
    private static function legSignature(array $leg): string
    {
        $segments = is_array($leg['segments'] ?? null) ? $leg['segments'] : [];
        $parts = [];

        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $parts[] = implode(':', [
                strtoupper(trim((string) ($segment['carrier'] ?? ''))),
                trim((string) ($segment['flight_number'] ?? '')),
                trim((string) ($segment['departure_clock'] ?? '')),
                strtoupper(trim((string) ($segment['from'] ?? ''))),
                strtoupper(trim((string) ($segment['to'] ?? ''))),
                trim((string) ($segment['departure_datetime'] ?? '')),
            ]);
        }

        return implode('|', $parts);
    }

    /**
     * @param  array<string, mixed>  $onward
     * @param  list<array<string, mixed>>  $coupons
     * @return array<string, mixed>|null
     */
    private static function buildReturnLeg(array $onward, B2bFlightBooking $booking, array $coupons): ?array
    {
        $template = is_array($onward['segments'][0] ?? null) ? $onward['segments'][0] : null;
        if ($template === null) {
            return null;
        }

        foreach (array_slice($coupons, 1) as $coupon) {
            if (! is_array($coupon)) {
                continue;
            }

            $leg = self::legFromCoupon($coupon, $template, $onward, $booking->return_date);
            if ($leg !== null && self::legRouteDiffers($onward, $leg)) {
                return $leg;
            }
        }

        foreach ($coupons as $coupon) {
            if (! is_array($coupon)) {
                continue;
            }

            $leg = self::legFromCoupon($coupon, $template, $onward, $booking->return_date);
            if ($leg !== null && self::legRouteDiffers($onward, $leg)) {
                return $leg;
            }
        }

        return self::synthesizeReversedLeg($onward, $booking);
    }

    /**
     * @param  array<string, mixed>  $coupon
     * @param  array<string, mixed>  $template
     * @param  array<string, mixed>  $onward
     * @return array<string, mixed>|null
     */
    private static function legFromCoupon(
        array $coupon,
        array $template,
        array $onward,
        ?Carbon $returnDate,
    ): ?array {
        [$from, $to] = self::parseCouponRoute((string) ($coupon['route'] ?? ''));
        if ($from === '' || $to === '') {
            return null;
        }

        $flight = trim((string) ($coupon['flight'] ?? ''));
        $carrier = '';
        $flightNumber = '';

        if ($flight !== '' && preg_match('/^([A-Z0-9]{2,3})\s*(.*)$/i', $flight, $matches)) {
            $carrier = strtoupper($matches[1]);
            $flightNumber = trim($matches[2]);
        }

        if ($carrier === '') {
            $carrier = strtoupper(trim((string) ($template['carrier'] ?? '')));
        }

        if ($carrier === '' || $flightNumber === '') {
            return null;
        }

        $segment = self::reverseSegment($template, $returnDate);
        $segment['from'] = $from;
        $segment['to'] = $to;
        $segment['carrier'] = $carrier;
        $segment['flight_number'] = $flightNumber;
        $segment['carrier_display'] = trim($carrier . ' ' . $flightNumber);
        $segment['booking_code'] = strtoupper(trim((string) (
            $coupon['booking_class']
            ?? $template['booking_code']
            ?? 'Y'
        )));
        $segment['departure_city'] = resolveFlightCityLabel('', $from);
        $segment['arrival_city'] = resolveFlightCityLabel('', $to);

        $departure = trim((string) ($coupon['departure'] ?? ''));
        if ($departure !== '') {
            try {
                $departureAt = Carbon::parse($departure);
                $segment['departure_datetime'] = $departureAt->toIso8601String();
                $segment['departure_clock'] = $departureAt->format('H:i');
                $segment['departure_display'] = $departureAt->format('D, d M y');
                $segment['departure_label'] = formatFlightSegmentDate($departureAt);
                $segment['departure_weekday'] = $departureAt->format('D');
            } catch (\Throwable) {
            }
        }

        return [
            'elapsedTime' => (int) ($onward['elapsedTime'] ?? 0),
            'segments' => [$segment],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function parseCouponRoute(string $route): array
    {
        $route = strtoupper(trim($route));
        if ($route === '') {
            return ['', ''];
        }

        if (preg_match('/^([A-Z]{3})\s*(?:→|->|-)\s*([A-Z]{3})$/', $route, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return ['', ''];
    }

    /**
     * @param  array<string, mixed>  $onwardLeg
     * @return array<string, mixed>|null
     */
    private static function synthesizeReversedLeg(array $onwardLeg, B2bFlightBooking $booking): ?array
    {
        $segments = is_array($onwardLeg['segments'] ?? null) ? $onwardLeg['segments'] : [];
        if ($segments === []) {
            return null;
        }

        $reversed = [];
        foreach (array_reverse($segments) as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $reversed[] = self::reverseSegment($segment, $booking->return_date);
        }

        if ($reversed === []) {
            return null;
        }

        $from = strtoupper(trim((string) ($booking->from_airport ?? '')));
        $to = strtoupper(trim((string) ($booking->to_airport ?? '')));
        if ($from !== '' && $to !== '') {
            $first = &$reversed[0];
            $last = &$reversed[array_key_last($reversed)];
            $first['from'] = $to;
            $first['departure_city'] = resolveFlightCityLabel($first['departure_city'] ?? '', $to);
            $last['to'] = $from;
            $last['arrival_city'] = resolveFlightCityLabel($last['arrival_city'] ?? '', $from);
        }

        return [
            'elapsedTime' => (int) ($onwardLeg['elapsedTime'] ?? 0),
            'segments' => $reversed,
        ];
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    private static function reverseSegment(array $segment, ?Carbon $returnDate): array
    {
        $from = strtoupper(trim((string) ($segment['to'] ?? '')));
        $to = strtoupper(trim((string) ($segment['from'] ?? '')));
        $departureCity = trim((string) ($segment['arrival_city'] ?? ''));
        $arrivalCity = trim((string) ($segment['departure_city'] ?? ''));

        $reversed = $segment;
        $reversed['from'] = $from;
        $reversed['to'] = $to;
        $reversed['departure_city'] = $departureCity !== '' ? $departureCity : resolveFlightCityLabel('', $from);
        $reversed['arrival_city'] = $arrivalCity !== '' ? $arrivalCity : resolveFlightCityLabel('', $to);
        $reversed['departure_terminal'] = $segment['arrival_terminal'] ?? '';
        $reversed['arrival_terminal'] = $segment['departure_terminal'] ?? '';

        if ($returnDate instanceof Carbon) {
            $clock = trim((string) ($segment['departure_clock'] ?? '00:00'));
            try {
                $departure = Carbon::parse($returnDate->format('Y-m-d') . ' ' . $clock);
                $reversed['departure_datetime'] = $departure->toIso8601String();
                $reversed['departure_clock'] = $departure->format('H:i');
                $reversed['departure_display'] = $departure->format('D, d M y');
                $reversed['departure_label'] = formatFlightSegmentDate($departure);
                $reversed['departure_weekday'] = $departure->format('D');
            } catch (\Throwable) {
            }
        }

        return $reversed;
    }
}
