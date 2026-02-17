<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function provinces()
    {
        return $this->hasMany(Province::class);
    }
}
