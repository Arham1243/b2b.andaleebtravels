<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2bSavedPassenger extends Model
{
    protected $fillable = [
        'b2b_vendor_id',
        'title',
        'first_name',
        'last_name',
        'dob',
        'nationality',
        'passport_no',
        'passport_exp',
    ];

    protected $casts = [
        'dob'          => 'date',
        'passport_exp' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(B2bVendor::class, 'b2b_vendor_id');
    }
}
