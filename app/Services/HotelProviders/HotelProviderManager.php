<?php

namespace App\Services\HotelProviders;

use App\Models\Country;
use App\Models\Province;
use App\Services\HotelProviders\Contracts\HotelProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HotelProviderManager
{
    /** @var HotelProviderInterface[] */
    private array $providers;

    public function __construct(float $commissionPercentage, ?array $enabledProviders = null)
    {
        $providers = [
            new YalagoHotelProvider($commissionPercentage),
            new TripInDealHotelProvider(),
            new TboHotelProvider(),
        ];

        if (!empty($enabledProviders)) {
            $enabled = collect($enabledProviders)
                ->map(fn($key) => strtolower(trim((string) $key)))
                ->filter()
                ->flip();

            $providers = array_values(array_filter(
                $providers,
                fn(HotelProviderInterface $provider) => $enabled->has(strtolower($provider->key()))
            ));
        }

        $this->providers = $providers;
    }

    public function search(Province|Country $destination, array $rooms, Request $request): Collection
    {
        return collect($this->providers)
            ->flatMap(fn(HotelProviderInterface $provider) => $provider->search($destination, $rooms, $request))
            ->values();
    }
}
