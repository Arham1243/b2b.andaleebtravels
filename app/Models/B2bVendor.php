<?php

namespace App\Models;

use App\Support\VendorWalletCredit;

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
        'main_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'credit_used' => 'decimal:2',
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

    /** Agency account that owns credit limit (parent for sub-agents). */
    public function walletAgency(): self
    {
        if ($this->isSubAgentAccount()) {
            $this->loadMissing('parentVendor');

            return $this->parentVendor ?? $this;
        }

        return $this;
    }

    public function creditLimitAmount(): float
    {
        return max(0, (float) ($this->walletAgency()->credit_limit ?? 0));
    }

    public function hasCreditLimit(): bool
    {
        return $this->creditLimitAmount() > 0;
    }

    /** Outstanding amount drawn from the agency credit line. */
    public function creditUsedAmount(): float
    {
        if ($this->hasCreditLimit()) {
            return max(0, round((float) ($this->credit_used ?? 0), 2));
        }

        return max(0, round(-min(0, (float) $this->main_balance), 2));
    }

    public function creditAvailableAmount(): float
    {
        return max(0, round($this->creditLimitAmount() - $this->creditUsedAmount(), 2));
    }

    /** Prepaid wallet funds (excludes credit line). */
    public function prepaidWalletBalance(): float
    {
        if ($this->hasCreditLimit()) {
            return max(0, round((float) $this->main_balance, 2));
        }

        return max(0, round((float) $this->main_balance, 2));
    }

    public function netWalletBalance(): float
    {
        if ($this->hasCreditLimit()) {
            return VendorWalletCredit::netBalance(
                (float) $this->main_balance,
                (float) ($this->credit_used ?? 0)
            );
        }

        return round((float) $this->main_balance, 2);
    }

    public function totalSpendableBalance(): float
    {
        if ($this->hasCreditLimit()) {
            return VendorWalletCredit::totalSpendable(
                VendorWalletCredit::currentPrepaid($this),
                (float) ($this->credit_used ?? 0),
                $this->creditLimitAmount()
            );
        }

        return round((float) $this->main_balance, 2);
    }

    public function minimumAllowedBalance(): float
    {
        if ($this->hasCreditLimit()) {
            return round(-$this->creditLimitAmount(), 2);
        }

        return 0.0;
    }

    public function canDebitAmount(float $amount): bool
    {
        return round($this->totalSpendableBalance(), 2) >= round($amount, 2);
    }

    public function pendingRechargeAmount(): float
    {
        return round((float) $this->walletRecharges()->where('status', 'pending')->sum('amount'), 2);
    }

    /** Max single recharge allowed (respects credit limit prepaid cap). */
    public function maxRechargeAmount(): float
    {
        if (! $this->hasCreditLimit()) {
            return 50000;
        }

        $prepaid = VendorWalletCredit::currentPrepaid($this);
        $creditUsed = $this->creditUsedAmount();
        $limit = $this->creditLimitAmount();
        $prepaidHeadroom = max(0, round($limit - $prepaid - $this->pendingRechargeAmount(), 2));

        return min(50000, round($creditUsed + $prepaidHeadroom, 2));
    }

    public function canRechargeAmount(float $amount): bool
    {
        $amount = round($amount, 2);

        if ($amount < 100 || $amount > 50000) {
            return false;
        }

        if (! $this->hasCreditLimit()) {
            return true;
        }

        [$newPrepaid] = VendorWalletCredit::applyCredit(
            VendorWalletCredit::currentPrepaid($this),
            $this->creditUsedAmount(),
            $amount
        );

        return round($newPrepaid + $this->pendingRechargeAmount(), 2) <= round($this->creditLimitAmount(), 2) + 0.001;
    }

    public function canRecharge(): bool
    {
        return $this->maxRechargeAmount() >= 100;
    }

    public function rechargeBlockedMessage(): ?string
    {
        if ($this->canRecharge()) {
            return null;
        }

        if (! $this->hasCreditLimit()) {
            return null;
        }

        return 'Your prepaid wallet has reached the credit limit of '
            . number_format($this->creditLimitAmount(), 2)
            . ' AED. You cannot add more funds until your balance is below this limit or your credit limit is increased.';
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
