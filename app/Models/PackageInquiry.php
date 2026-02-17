<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageInquiry extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
