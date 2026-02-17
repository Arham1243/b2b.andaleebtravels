<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'tour_id',
        'user_id',
        'guest_email',
        'tour_name',
        'booking_date',
        'time_slot',
        'price',
        'quantity',
        'subtotal',
        'pax_details',
        'product_id_prio',
        'availability_id',
        'booking_reference',
        'reservation_response',
        'reservation_data',
        'order_data',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'pax_details' => 'array',
        'booking_date' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
