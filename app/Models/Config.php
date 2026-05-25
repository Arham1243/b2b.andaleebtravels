<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    public const SITE_LOGO_KEY = 'B2B_SITE_LOGO';

    protected $fillable = ['config_key', 'config_value'];
}
