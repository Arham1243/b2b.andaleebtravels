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
        'wallet_amount' => 'decimal:2',
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

    public function isConfirmed(): bool
    {
        return $this->booking_status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->booking_status === 'cancelled';
    }
}
