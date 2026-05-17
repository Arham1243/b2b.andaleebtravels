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
            new TboHotelProvider($commissionPercentage),
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
        // Page-aware budget: providers run sequentially. Each provider gets to see
        // how many hotels it still needs to deliver to fill the current page, so
        // expensive APIs (e.g. TBO) can avoid pulling thousands of hotels when a
        // cheaper provider already covered the page.
        $perPage = (int) $request->input('per_page', 10);
        $page    = max(1, (int) $request->input('page', 1));

        // Bypass budget when the user is searching for a specific hotel by name
        // — we need the full provider catalogue to find it.
        $hotelNameSearch = trim((string) $request->input('hotel_name', '')) !== '';

        // Filters that slice results after the fact need extra room so we don't
        // empty out the page (e.g. star rating leaves only a fraction of hotels).
        $hasNarrowingFilter = $request->filled('rating')
            || $request->filled('property_type')
            || $request->filled('min_price')
            || $request->filled('max_price')
            || $request->filled('board_type');

        // Aim a bit higher than the page itself so post-filter losses still
        // leave us with a full page (3x by default, 6x when filtering on
        // attributes that may remove a large portion of the set).
        $multiplier = $hasNarrowingFilter ? 6 : 3;
        $target     = max(30, $perPage * $page * $multiplier);

        $results = collect();

        foreach ($this->providers as $provider) {
            $remaining = $hotelNameSearch ? PHP_INT_MAX : max(0, $target - $results->count());

            // Expose budget to provider via Request attributes. Providers that
            // ignore this still work — they just won't take advantage of it.
            $request->attributes->set('hotel_search_budget', $remaining);
            $request->attributes->set('hotel_search_collected', $results->count());

            $providerResults = $provider->search($destination, $rooms, $request);

            if ($providerResults instanceof Collection) {
                $results = $results->merge($providerResults);
            }
        }

        // Clean up so downstream code doesn't see stale values.
        $request->attributes->remove('hotel_search_budget');
        $request->attributes->remove('hotel_search_collected');

        return $results->values();
    }
}
