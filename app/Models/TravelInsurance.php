<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelInsurance extends Model
{
    protected $fillable = [
        'insurance_number',
        'plan_title',
        'plan_code',
        'user_id',
        'ssr_fee_code',
        'channel',
        'pnr',
        'purchase_date',
        'currency',
        'total_premium',
        'country_code',
        'total_adults',
        'total_children',
        'total_infants',
        'payment_method',
        'payment_reference',
        'payment_status',
        'payment_response',
        'tabby_payment_id',
        'lead_name',
        'lead_email',
        'lead_phone',
        'lead_country_of_residence',
        'origin',
        'destination',
        'start_date',
        'return_date',
        'residence_country',
        'request_data',
        'api_response',
        'status',
        'payby_merchant_order_no',
        'payby_order_no',
        'payby_payment_response',
        'proposal_state',
        'policy_numbers',
        'confirmed_passengers',
        'error_messages',
        'booking_confirmed',
        'confirmation_response',
    ];

    protected $casts = [
        'total_premium' => 'decimal:2',
        'purchase_date' => 'date',
        'start_date' => 'date',
        'return_date' => 'date',
        'request_data' => 'array',
        'api_response' => 'array',
        'booking_confirmed' => 'boolean',
    ];

    public function passengers()
    {
        return $this->hasMany(TravelInsurancePassenger::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generateInsuranceNumber()
    {
        return 'TI-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
    }
}
