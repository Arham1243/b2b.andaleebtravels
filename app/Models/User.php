<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $appends = ['name'];
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
