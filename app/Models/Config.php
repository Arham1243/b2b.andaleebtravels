<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    public const SITE_LOGO_KEY = 'B2B_SITE_LOGO';

    public const B2B_WHATSAPP_KEY = 'B2B_WHATSAPP';

    public const B2B_SUPPORT_EMAIL_KEY = 'B2B_SUPPORT_EMAIL';

    public const B2B_FACEBOOK_KEY = 'B2B_FACEBOOK';

    public const B2B_INSTAGRAM_KEY = 'B2B_INSTAGRAM';

    public const B2B_TWITTER_KEY = 'B2B_TWITTER';

    public const B2B_LINKEDIN_KEY = 'B2B_LINKEDIN';

    public const B2B_YOUTUBE_KEY = 'B2B_YOUTUBE';

    protected $fillable = ['config_key', 'config_value'];
}
