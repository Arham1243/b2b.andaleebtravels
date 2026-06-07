<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class B2bFlightBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'b2b_vendor_id',
        'booking_number',
        'provider',
        'sabre_record_locator',
        'itinerary_id',
        'from_airport',
        'to_airport',
        'departure_date',
        'return_date',
        'adults',
        'children',
        'infants',
        'passengers_data',
        'itinerary_data',
        'search_request',
        'search_response',
        'booking_request',
        'booking_response',
        'ticket_request',
        'ticket_response',
        'cancel_response',
        'total_amount',
        'original_amount',
        'vendor_discount_amount',
        'vendor_discount_snapshot',
        'vendor_markup_amount',
        'vendor_markup_snapshot',
        'wallet_amount',
        'currency',
        'payment_method',
        'payment_status',
        'payment_reference',
        'tabby_payment_id',
        'payment_response',
        'booking_status',
        'ticket_status',
        'source_market',
        'ip_address',
        'user_agent',
        'cancelled_at',
        'cancelled_by',
        'hold_expires_at',
        'confirmation_email_sent_at',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'passengers_data' => 'array',
        'itinerary_data' => 'array',
        'search_request' => 'array',
        'search_response' => 'array',
        'booking_request' => 'array',
        'booking_response' => 'array',
        'ticket_request' => 'array',
        'ticket_response' => 'array',
        'cancel_response' => 'array',
        'payment_response' => 'array',
        'total_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'vendor_discount_amount' => 'decimal:2',
        'vendor_discount_snapshot' => 'array',
        'vendor_markup_amount' => 'decimal:2',
        'vendor_markup_snapshot' => 'array',
        'wallet_amount' => 'decimal:2',
        'hold_expires_at' => 'datetime',
        'confirmation_email_sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public static function generateBookingNumber(): string
    {
        do {
            $bookingNumber = 'B2BFB' . date('Ymd') . rand(1000, 9999);
        } while (self::where('booking_number', $bookingNumber)->exists());

        return $bookingNumber;
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(B2bVendor::class, 'b2b_vendor_id');
    }

    public function getLeadFullNameAttribute(): string
    {
        $lead = $this->passengers_data['lead'] ?? [];
        return trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''));
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isOnHold(): bool
    {
        return $this->booking_status === 'hold' && ! $this->isPaid();
    }

    /**
     * Persist confirmed status after a hold booking is paid via Confirm & Pay.
     */
    public function reconcileStatusAfterHoldPayment(): bool
    {
        if ($this->booking_status !== 'hold' || ! $this->isPaid()) {
            return false;
        }

        $this->update(['booking_status' => 'confirmed']);
        $this->refresh();

        return true;
    }

    public function displayBookingStatus(): string
    {
        if ($this->booking_status === 'completed') {
            return 'confirmed';
        }

        if ($this->booking_status === 'hold' && $this->isPaid()) {
            return 'confirmed';
        }

        return $this->booking_status ?? 'pending';
    }

    public function isConfirmed(): bool
    {
        return in_array($this->displayBookingStatus(), ['confirmed', 'completed'], true);
    }

    public function isCancelled(): bool
    {
        return $this->booking_status === 'cancelled';
    }

    public function isTravelport(): bool
    {
        return normalizeFlightBookingProvider($this->provider) === 'travelport';
    }

    public function isSabre(): bool
    {
        return normalizeFlightBookingProvider($this->provider) === 'sabre';
    }

    public function providerLabel(): string
    {
        return formatFlightBookingProviderLabel($this->provider);
    }
}
