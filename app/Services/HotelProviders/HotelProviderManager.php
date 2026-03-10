<?php

namespace App\Services\HotelProviders;

use App\Models\Province;
use App\Services\HotelProviders\Contracts\HotelProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HotelProviderManager
{
    /** @var HotelProviderInterface[] */
    private array $providers;

    public function __construct(float $commissionPercentage)
    {
        $this->providers = [
            new YalagoHotelProvider($commissionPercentage),
            new TboHotelProvider(),
        ];
    }

    public function search(Province $province, array $rooms, Request $request): Collection
    {
        return collect($this->providers)
            ->flatMap(fn(HotelProviderInterface $provider) => $provider->search($province, $rooms, $request))
            ->values();
    }
}
