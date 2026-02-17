<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    protected $casts = [
        'content' => 'array',
        'locations' => 'array',
        'includes' => 'array',
        'excludes' => 'array',
        'product_type_seasons' => 'array',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function categories()
    {
        return $this->belongsToMany(TourCategory::class, 'category_tour', 'tour_id', 'tour_category_id');
    }
    public function approvedReviews()
    {
        return $this->hasMany(TourReview::class, 'tour_id')->where('status', 'active');
    }

    public function reviews()
    {
        return $this->hasMany(TourReview::class, 'tour_id');
    }

    public function getAvgRatingAttribute()
    {
        $avg = round($this->approvedReviews()->avg('rating'), 1);
        return $avg;
    }

    public function getImageAttribute()
    {
        return $this->content['product_images'][0]['image_url']
            ?? asset('frontend/assets/images/placeholder.png');
    }
}
