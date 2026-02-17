<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourCategory extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function tours()
    {
        return $this->belongsToMany(Tour::class, 'category_tour', 'tour_category_id', 'tour_id');
    }
}
