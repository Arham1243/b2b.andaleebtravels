<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelInsurancePassenger extends Model
{
    protected $fillable = [
        'travel_insurance_id',
        'passenger_type',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'passport_number',
        'nationality',
        'country_of_residence',
        'age',
        'status',
        'policy_number',
        'policy_url_link',
        'insurance_details',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function travelInsurance()
    {
        return $this->belongsTo(TravelInsurance::class);
    }
}
