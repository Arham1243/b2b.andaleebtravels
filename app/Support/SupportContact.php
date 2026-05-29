<?php

namespace App\Support;

use App\Models\Config;

class SupportContact
{
    public const DEFAULT_WHATSAPP = '+971525748986';

    public const DEFAULT_EMAIL = 'info@andaleebtours.com';

    /**
     * @return array{link: string, display: string}
     */
    public static function whatsapp(): array
    {
        $raw = B2bConfig::value(Config::B2B_WHATSAPP_KEY, 'WHATSAPP', self::DEFAULT_WHATSAPP);
        $digits = preg_replace('/\D+/', '', $raw);

        return [
            'link' => 'https://api.whatsapp.com/send?phone=' . $digits . '&text=' . rawurlencode("I'm interested in your services"),
            'display' => $raw,
        ];
    }

    public static function email(): string
    {
        return B2bConfig::value(Config::B2B_SUPPORT_EMAIL_KEY, 'SUPPORT_EMAIL', self::DEFAULT_EMAIL);
    }
}
