<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Tour;
use Carbon\Carbon;

class TourSyncService
{

    public function getAccessToken()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic YW5kYWxlZWIyMDIzMDFAcHJpb2FwaXMuY29tOkBBbmQwVHJhdjMkTEAhMiM='
        ])->post('https://distributor-api.prioticket.com/v3.5/distributor/oauth2/token');

        $accessToken = $response->json('access_token');

        if (!$accessToken) {
            return null;
        }

        return $accessToken;
    }

    public function syncPrioTicketTours(int $page = 1)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return back()->with('notify_error', 'Failed to fetch access token');
        }

        $productsResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'distributor_id' => '1070691',
            'Accept' => 'application/json',
        ])->get('https://distributor-api.prioticket.com/v3.5/distributor/products', [
            'distributor_id' => 49670,
            'items_per_page' => 500,
            'page' => $page,
        ]);

        $items = data_get($productsResponse->json(), 'data.items', []);

        $today = Carbon::now();

        foreach ($items as $item) {

            $status = ($item['product_status'] ?? '') === 'ACTIVE' ? 'active' : 'inactive';

            $seasons = $item['product_type_seasons'] ?? [];
            $currentSeason = null;

            foreach ($seasons as $season) {
                $start = Carbon::parse($season['product_type_season_start_date']);
                $end = Carbon::parse($season['product_type_season_end_date']);
                if ($today->between($start, $end)) {
                    $currentSeason = $season;
                    break;
                }
            }

            if (!$currentSeason && !empty($seasons)) {
                $currentSeason = end($seasons);
            }

            if (!$currentSeason) {
                continue;
            }

            $seasonDetails = $currentSeason['product_type_season_details'] ?? [];
            $adultSeason = $seasonDetails[0] ?? null;
            $childSeason = $seasonDetails[1] ?? null;
            $infantSeason = $seasonDetails[2] ?? null;
        
            Tour::updateOrCreate(
                ['slug' => $item['product_slug']],
                [
                    'distributer_name' => 'Prio Ticket',
                    'product_id_prio' => $item['product_id'],

                    'type' => data_get($adultSeason, 'product_type_price_type'),
                    'name' => data_get($item, 'product_content.product_title'),
                    'slug' => $item['product_slug'],

                    'price' => data_get($adultSeason, 'product_type_pricing.product_type_sales_price'),
                    'discount_price' => data_get($adultSeason, 'product_type_pricing.product_type_list_price'),
                    'child_price' => data_get($childSeason, 'product_type_pricing.product_type_sales_price'),
                    'infant_price' => data_get($infantSeason, 'product_type_pricing.product_type_sales_price'),

                    'min_qty' => $item['product_booking_quantity_min'] ?? 1,
                    'max_qty' => $item['product_booking_quantity_max'] ?? 99,
                    'has_capacity' => $item['product_capacity'],

                    'content' => $item['product_content'] ?? null,
                    'duration' => data_get($item, 'product_content.product_duration_text'),
                    'short_description' => data_get($item, 'product_content.product_short_description'),
                    'long_description' => data_get($item, 'product_content.product_long_description'),
                    'additional_information' => data_get($item, 'product_content.product_additional_information'),
                    'cancellation_policies' => data_get($item, 'product_cancellation_policies.0.cancellation_description'),

                    'locations' => $item['product_locations'] ?? null,
                    'includes' => data_get($item, 'product_content.product_includes'),
                    'excludes' => data_get($item, 'product_content.product_excludes'),
                    'product_type_seasons' => $item['product_type_seasons'] ?? null,

                    'status' => $status,
                ]
            );
        }
    }
}
