<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TourCard extends Component
{
    public $tour;
    public $style;
    public function __construct($tour, $style = 'style1')
    {
        $this->tour = $tour;
        $this->style = $style;
    }

    public function render()
    {
        return view('components.frontend.tour-card');
    }
}
