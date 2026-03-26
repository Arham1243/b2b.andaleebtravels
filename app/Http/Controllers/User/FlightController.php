<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class FlightController extends Controller
{
    public function index()
    {
        return view('user.flights.index');
    }
}
