<?php

namespace App\Support\Travelport;

final class TravelportContactSsrBuilder
{
    /**
     * Format email for SSR CTCE per Travelport / IATA AIRIMP rules.
     *
     * @see https://support.travelport.com/webhelp/uAPI/Content/Air/Shared_Air_Topics/SSRs_(Special_Service_Requests).htm
     */
    public static function formatCtceEmail(string $email): string
    {
        $email = strtoupper(trim($email));
        if ($email === '') {
            return '';
        }

        $email = str_replace('@', '//', $email);
        $email = str_replace('_', '..', $email);
        $email = str_replace('-', './', $email);

        return $email;
    }

    /**
     * Format mobile number for SSR CTCM (digits only, country code included).
     */
    public static function formatCtcmPhone(string $countryCode, string $areaCode, string $number): string
    {
        return preg_replace('/\D+/', '', $countryCode . $areaCode . $number) ?? '';
    }

    /**
     * @return list<array{type: string, free_text: string}>
     */
    public static function contactSsrs(string $countryCode, string $areaCode, string $number, string $email): array
    {
        $ssrs = [];

        $phone = self::formatCtcmPhone($countryCode, $areaCode, $number);
        if ($phone !== '') {
            $ssrs[] = [
                'type' => 'CTCM',
                'free_text' => $phone,
            ];
        }

        $formattedEmail = self::formatCtceEmail($email);
        if ($formattedEmail !== '' && str_contains($formattedEmail, '//')) {
            $ssrs[] = [
                'type' => 'CTCE',
                'free_text' => $formattedEmail,
            ];
        }

        return $ssrs;
    }

    public static function resolveCarrierFromPricingData(array $pricingData): string
    {
        foreach ($pricingData['segments'] ?? [] as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $carrier = strtoupper(trim((string) ($segment['carrier'] ?? '')));
            if ($carrier !== '') {
                return $carrier;
            }
        }

        return 'YY';
    }
}
