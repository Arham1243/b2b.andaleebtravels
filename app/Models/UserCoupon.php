<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCoupon extends Model
{
    protected $fillable = ['email', 'coupon_id'];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
