<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class B2bAdmin extends Authenticatable
{
    protected $table = 'b2b_admins';

    public $timestamps = true;
}
