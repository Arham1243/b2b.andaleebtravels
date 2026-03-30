<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class B2bVendor extends Authenticatable
{
    use Notifiable;
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $casts = [
        'hotel_search_providers' => 'array',
        'flight_search_providers' => 'array',
    ];

    public function hotelBookings(): HasMany
    {
        return $this->hasMany(B2bHotelBooking::class, 'b2b_vendor_id');
    }

    public function parentVendor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_vendor_id');
    }

    public function subAgents(): HasMany
    {
        return $this->hasMany(self::class, 'parent_vendor_id');
    }

    public function walletRecharges(): HasMany
    {
        return $this->hasMany(B2bWalletRecharge::class, 'b2b_vendor_id');
    }

    public function walletLedger(): HasMany
    {
        return $this->hasMany(B2bWalletLedger::class, 'b2b_vendor_id');
    }
}
