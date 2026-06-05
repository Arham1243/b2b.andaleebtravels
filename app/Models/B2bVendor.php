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

    /** @var array{prepaid: float, credit_used: float, net: float}|null */
    protected ?array $creditPoolsCache = null;

    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $casts = [
        'hotel_search_providers' => 'array',
        'flight_search_providers' => 'array',
        'trade_license_expiry' => 'date',
        'main_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'credit_used' => 'decimal:2',
        'flight_discount_value' => 'decimal:2',
        'hotel_discount_value' => 'decimal:2',
        'vendor_discounts_enabled' => 'boolean',
        'flight_markup_value' => 'decimal:2',
        'hotel_markup_value' => 'decimal:2',
        'vendor_markups_enabled' => 'boolean',
        'agent_markup_override_enabled' => 'boolean',
        'agent_flight_markup_value' => 'decimal:2',
        'agent_hotel_markup_value' => 'decimal:2',
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
        return max(0, round((float) $this->credit_limit, 2));
    }

    public function hasCreditLimit(): bool
    {
        return $this->creditLimitAmount() > 0;
    }

    /** Unused portion of the admin-assigned credit limit. */
    public function creditLimitRemainingAmount(): float
    {
        return VendorWalletCredit::creditRemaining($this->creditUsedAmount(), $this->creditLimitAmount());
    }

    /**
     * Replay active wallet ledger entries to derive prepaid, credit used, and net balance.
     *
     * @return array{prepaid: float, credit_used: float, net: float}
     */
    public function creditPools(bool $fresh = false): array
    {
        if ($fresh || $this->creditPoolsCache === null) {
            $this->creditPoolsCache = VendorWalletCredit::poolsFromLedger($this);
        }

        return $this->creditPoolsCache;
    }

    public function refresh()
    {
        $this->creditPoolsCache = null;

        return parent::refresh();
    }

    /** Outstanding amount drawn from the agency credit line. */
    public function creditUsedAmount(): float
    {
        return max(0, round($this->creditPools()['credit_used'], 2));
    }

    /** Total active wallet debits — shown to admins/users as "Used Balance". */
    public function usedBalanceAmount(): float
    {
        return round((float) $this->walletLedger()->active()->where('type', 'debit')->sum('amount'), 2);
    }

    public function creditAvailableAmount(): float
    {
        return $this->creditLimitRemainingAmount();
    }

    /** Prepaid wallet funds from real money (recharges, manual credits, etc.). */
    public function prepaidWalletBalance(): float
    {
        return max(0, round($this->creditPools()['prepaid'], 2));
    }

    public function netWalletBalance(): float
    {
        return round($this->creditPools()['net'], 2);
    }

    /** Net wallet balance — what the vendor actually has (shown as Available Balance). */
    public function availableBalanceAmount(): float
    {
        $pools = $this->creditPools();

        return VendorWalletCredit::availableBalance(
            $pools['prepaid'],
            $pools['credit_used'],
            $this->creditLimitAmount()
        );
    }

    /** Maximum spend allowed on checkout / new debits. */
    public function totalSpendableBalance(): float
    {
        return $this->availableBalanceAmount();
    }

    public function minimumAllowedBalance(): float
    {
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

    /** Max single recharge allowed (AED). */
    public function maxRechargeAmount(): float
    {
        return 50000;
    }

    public function canRechargeAmount(float $amount): bool
    {
        $amount = round($amount, 2);

        return $amount >= 100 && $amount <= 50000;
    }

    public function canRecharge(): bool
    {
        return true;
    }

    public function rechargeBlockedMessage(): ?string
    {
        return null;
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
