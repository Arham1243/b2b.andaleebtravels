<?php

namespace App\Services\HotelProviders\Contracts;

use App\Models\Country;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface HotelProviderInterface
{
    public function key(): string;

    public function search(Province|Country $destination, array $rooms, Request $request): Collection;
}
