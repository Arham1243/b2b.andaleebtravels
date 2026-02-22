<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'images' => 'array',
    ];

    protected $appends = ['rating', 'rating_text'];


    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function getRatingTextAttribute(): string
    {
        $rating = (float) $this->rating;

        return match (true) {
            $rating >= 4.5 => 'Spectacular',
            $rating >= 4.0 => 'Excellent',
            $rating >= 3.5 => 'Good',
            $rating >= 3.0 => 'Above Average',
            $rating >= 2.0 => 'Average',
            $rating >= 1.0 => 'Poor',
            default        => 'Very Poor',
        };
    }

    public function getRatingStarsAttribute(): float
    {
        return (float) floor($this->rating ?? 0);
    }
}
