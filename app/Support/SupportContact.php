<?php

namespace App\Support;

class SupportContact
{
    public const DEFAULT_WHATSAPP = '+971525748986';

    public const DEFAULT_EMAIL = 'info@andaleebtours.com';

    /**
     * @return array{link: string, display: string}
     */
    public static function whatsapp(?array $config = null): array
    {
        $raw = trim((string) (($config ?? [])['WHATSAPP'] ?? ''));

        if ($raw === '') {
            $raw = self::DEFAULT_WHATSAPP;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        return [
            'link' => 'https://api.whatsapp.com/send?phone=' . $digits . '&text=' . rawurlencode("I'm interested in your services"),
            'display' => $raw,
        ];
    }

    public static function email(?array $config = null): string
    {
        $email = trim((string) (($config ?? [])['SUPPORT_EMAIL'] ?? ''));

        return $email !== '' ? $email : self::DEFAULT_EMAIL;
    }
}
