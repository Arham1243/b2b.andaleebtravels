<?php

namespace App\Http\Controllers;

use App\Models\Config;
use Illuminate\Support\Facades\View;

abstract class Controller
{
    public function __construct()
    {
        $config = Config::pluck('config_value', 'config_key')->toArray();

        View::share('config', $config);
    }
}
