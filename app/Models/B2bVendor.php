<?php

namespace App\Models;

use App\Notifications\B2bVendorResetPassword;
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
        'trade_license_expiry' => 'date',
    ];

    public function getContactNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getDisplayAgencyNameAttribute(): string
    {
        return $this->travel_agency ?: $this->name ?: '';
    }

    /** URL for agency logo shown in the site header (legacy avatar used as fallback). */
    public function agencyLogoUrl(): ?string
    {
        $path = $this->agency_logo ?: $this->avatar;

        return $path ? asset($path) : null;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending';
    }

    public function isAgencyAccount(): bool
    {
        return $this->parent_vendor_id === null;
    }

    public function isSubAgentAccount(): bool
    {
        return $this->parent_vendor_id !== null;
    }

    /** Agent code used at login (sub-agents share the parent agency code). */
    public function loginAgentCode(): ?string
    {
        if ($this->isSubAgentAccount()) {
            $this->loadMissing('parentVendor');

            return $this->parentVendor?->agent_code;
        }

        return $this->agent_code;
    }

    public function scopeApprovedAgencies($query)
    {
        return $query->whereNull('parent_vendor_id')->whereIn('status', ['active', 'inactive']);
    }

    public function scopePendingSignups($query)
    {
        return $query->whereNull('parent_vendor_id')->where('status', 'pending');
    }

    public function getEffectiveTradeLicenseExpiryAttribute(): ?\Illuminate\Support\Carbon
    {
        if ($this->trade_license_expiry) {
            return $this->trade_license_expiry;
        }

        if ($this->parent_vendor_id) {
            $this->loadMissing('parentVendor');

            return $this->parentVendor?->trade_license_expiry;
        }

        return null;
    }

    public function hasExpiredTradeLicense(): bool
    {
        $expiry = $this->effective_trade_license_expiry;

        if (!$expiry) {
            return false;
        }

        return $expiry->startOfDay()->lt(now()->startOfDay());
    }

    public function hotelBookings(): HasMany
    {
        return $this->hasMany(B2bHotelBooking::class, 'b2b_vendor_id');
    }

    public function flightBookings(): HasMany
    {
        return $this->hasMany(B2bFlightBooking::class, 'b2b_vendor_id');
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

    public function savedPassengers(): HasMany
    {
        return $this->hasMany(B2bSavedPassenger::class, 'b2b_vendor_id');
    }

    public function hasAssociatedData(): bool
    {
        return $this->hotelBookings()->exists()
            || $this->flightBookings()->exists()
            || $this->walletLedger()->exists()
            || $this->walletRecharges()->exists()
            || $this->savedPassengers()->exists()
            || $this->subAgents()->exists();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new B2bVendorResetPassword($token));
    }
}
