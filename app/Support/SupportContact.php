<?php

namespace App\Support;

use App\Models\Config;
use App\Support\Travelport\TravelportHoldPayloadBuilder;

class SupportContact
{
    public const DEFAULT_WHATSAPP = '+971525748986';

    public const DEFAULT_EMAIL = 'info@andaleebtours.com';

    /**
     * @return array{link: string, display: string}
     */
    public static function whatsapp(): array
    {
        $raw = self::whatsappNumber();
        $digits = preg_replace('/\D+/', '', $raw);

        return [
            'link' => 'https://api.whatsapp.com/send?phone=' . $digits . '&text=' . rawurlencode("I'm interested in your services"),
            'display' => $raw,
        ];
    }

    public static function whatsappNumber(): string
    {
        return B2bConfig::value(Config::B2B_WHATSAPP_KEY, 'WHATSAPP', self::DEFAULT_WHATSAPP);
    }

    public static function email(): string
    {
        return B2bConfig::value(Config::B2B_SUPPORT_EMAIL_KEY, 'SUPPORT_EMAIL', self::DEFAULT_EMAIL);
    }

    /**
     * Default lead contact for flight checkout/hold forms (admin site settings).
     *
     * @return array{email: string, phone: string, phone_dial_code: string, phone_local: string, phone_iso: string}
     */
    public static function defaultLeadContact(): array
    {
        $phone = trim(self::whatsappNumber());
        $parsed = TravelportHoldPayloadBuilder::parseLeadPhoneForForm($phone);

        return [
            'email' => trim(self::email()),
            'phone' => $phone,
            'phone_dial_code' => $parsed['dial_code'],
            'phone_local' => $parsed['local'],
            'phone_iso' => $parsed['iso'],
        ];
    }
}
