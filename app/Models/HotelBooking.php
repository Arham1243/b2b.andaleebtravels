<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'booking_number',
        'yalago_booking_reference',
        'yalago_hotel_id',
        'hotel_name',
        'hotel_address',
        'check_in_date',
        'check_out_date',
        'nights',
        'rooms_data',
        'selected_rooms',
        'lead_title',
        'lead_first_name',
        'lead_last_name',
        'lead_email',
        'lead_phone',
        'lead_address',
        'guests_data',
        'extras_data',
        'extras_total',
        'flight_details',
        'rooms_total',
        'total_amount',
        'currency',
        'payment_method',
        'payment_status',
        'payment_reference',
        'tabby_payment_id',
        'payment_response',
        'booking_status',
        'availability_request',
        'availability_response',
        'booking_request',
        'booking_response',
        'source_market',
        'ip_address',
        'user_agent',
        'cancelled_at',
        'cancelled_by',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'rooms_data' => 'array',
        'selected_rooms' => 'array',
        'guests_data' => 'array',
        'extras_data' => 'array',
        'flight_details' => 'array',
        'availability_request' => 'array',
        'availability_response' => 'array',
        'cancel_response' => 'array',
        'booking_request' => 'array',
        'booking_response' => 'array',
        'rooms_total' => 'decimal:2',
        'extras_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Generate unique booking number
     */
    public static function generateBookingNumber(): string
    {
        do {
            $bookingNumber = 'HB' . date('Ymd') . rand(1000, 9999);
        } while (self::where('booking_number', $bookingNumber)->exists());

        return $bookingNumber;
    }

    /**
     * Get the user that owns the booking
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get full lead name
     */
    public function getLeadFullNameAttribute(): string
    {
        return trim($this->lead_first_name . ' ' . $this->lead_last_name);
    }

    /**
     * Check if booking is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if booking is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->booking_status === 'confirmed';
    }

    /**
     * Check if booking is pending
     */
    public function isPending(): bool
    {
        return $this->booking_status === 'pending';
    }

    /**
     * Check if booking is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->booking_status === 'cancelled';
    }

    /**
     * Scope to get bookings by user or email
     */
    public function scopeByUserOrEmail($query, $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhere('lead_email', $user->email);
        });
    }

    /**
     * Scope to get paid bookings
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope to get confirmed bookings
     */
    public function scopeConfirmed($query)
    {
        return $query->where('booking_status', 'confirmed');
    }
}
