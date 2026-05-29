<?php

namespace App\Support;

use App\Models\Config;

class SocialMediaConfig
{
    /** @var array<string, array{b2b: string, legacy: string, icon: string}> */
    public const PLATFORMS = [
        'facebook' => [
            'b2b' => Config::B2B_FACEBOOK_KEY,
            'legacy' => 'FACEBOOK',
            'icon' => 'bx bxl-facebook',
        ],
        'twitter' => [
            'b2b' => Config::B2B_TWITTER_KEY,
            'legacy' => 'TWITTER',
            'icon' => 'bx bxl-twitter',
        ],
        'instagram' => [
            'b2b' => Config::B2B_INSTAGRAM_KEY,
            'legacy' => 'INSTAGRAM',
            'icon' => 'bx bxl-instagram',
        ],
        'linkedin' => [
            'b2b' => Config::B2B_LINKEDIN_KEY,
            'legacy' => 'LINKEDIN',
            'icon' => 'bx bxl-linkedin',
        ],
        'youtube' => [
            'b2b' => Config::B2B_YOUTUBE_KEY,
            'legacy' => 'YOUTUBE',
            'icon' => 'bx bxl-youtube',
        ],
    ];

    /**
     * @return array<int, array{url: string, icon: string, label: string}>
     */
    public static function links(): array
    {
        $links = [];

        foreach (self::PLATFORMS as $label => $platform) {
            $url = B2bConfig::value($platform['b2b'], $platform['legacy']);

            if ($url === '') {
                continue;
            }

            $links[] = [
                'url' => $url,
                'icon' => $platform['icon'],
                'label' => $label,
            ];
        }

        return $links;
    }

    public static function adminValue(string $b2bKey, string $legacyKey): string
    {
        return B2bConfig::value($b2bKey, $legacyKey);
    }
}
