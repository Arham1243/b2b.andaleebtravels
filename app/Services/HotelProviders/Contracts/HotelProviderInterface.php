<?php

namespace App\Services\HotelProviders\Contracts;

use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface HotelProviderInterface
{
    public function key(): string;

    public function search(Province $province, array $rooms, Request $request): Collection;
}
