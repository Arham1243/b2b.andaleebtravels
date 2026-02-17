<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\TourCategory;
use App\Models\PackageCategory;
use Illuminate\Http\Request;
use App\Models\Tour;
use App\Models\TourReview;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class TourController extends Controller
{
    public function uae_services()
    {
        $search = request('search', '');
        $sortBy = request('sort_by', '');

        $banner = Banner::where('page', 'uae-tours')
            ->where('status', 'active')
            ->first();

        $categories = TourCategory::where('status', 'active')
            ->latest()
            ->get();

        $toursQuery = Tour::where('status', 'active')
            ->where('name', 'like', '%' . $search . '%');

        // Sorting logic
        if ($sortBy === 'recommended') {
            $toursQuery->where('is_recommended', 1);
        } elseif ($sortBy === 'price_low_to_high') {
            $toursQuery->orderBy('price', 'asc');
        } elseif ($sortBy === 'price_high_to_low') {
            $toursQuery->orderBy('price', 'desc');
        } else {
            $toursQuery->latest();
        }

        $tours = $toursQuery->take(16)->get();

        $total_tours = $toursQuery->count();

        $packageCategories = PackageCategory::with('packages')
            ->where('status', 'active')
            ->has('packages')
            ->latest()
            ->get();

        return view(
            'frontend.tour.uae-services',
            compact('banner', 'categories', 'tours', 'packageCategories', 'total_tours')
        );
    }
    public function search_uae_services(Request $request)
    {
        $query = $request->input('q', '');

        $tours = Tour::where('status', 'active')
            ->where('name', 'like', '%' . $query . '%')
            ->latest()
            ->get()
            ->map(function ($tour) {
                return [
                    'id' => $tour->id,
                    'text' => $tour->name
                ];
            });

        return response()->json(['results' => $tours]);
    }

    public function details($slug)
    {
        $date = request('date') ?? now()->format('Y-m-d');

        $tour = Tour::where('slug', $slug)->firstOrFail();
        $availability = $this->checkAvailability($tour, $date);
        $isTourAvailable = $availability['is_available'] ?? true;
        $availableRanges = $availability['available_ranges'] ?? [];
        $timeSlots = [];
        if ($date) {
            $timeSlots = $this->getTimeSlots($tour, $date);
        }

        $tourCategories = TourCategory::with([
            'tours' => function ($query) use ($tour) {
                $query->where('tours.status', 'active')
                    ->where('tours.id', '!=', $tour->id);
            }
        ])
            ->where('status', 'active')
            ->latest()
            ->get()
            ->filter(fn($category) => $category->tours->isNotEmpty());

        return view('frontend.tour.details', compact('tour', 'tourCategories', 'isTourAvailable', 'availableRanges', 'timeSlots'));
    }


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


    public function getTimeSlots($tour, $date)
    {
        $date = $date ?? now()->format('Y-m-d');
        $accessToken = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
        ])->get("https://distributor-api.prioticket.com/v3.5/distributor/products/{$tour->product_id_prio}/availability?distributor_id=49670&from_date={$date}");
        if ($response->successful()) {
            $data = $response->json();
            $rawSlots = $data['data']['items'] ?? [];

            if ($response->successful()) {
                $data = $response->json();
                $rawSlots = $data['data']['items'] ?? [];

                $formattedSlots = collect($rawSlots)
                    ->map(function ($slot) use ($tour) {
                        return [
                            'id' => $slot['availability_id'] ?? null,
                            'start_time' => isset($slot['availability_from_date_time'])
                                ? \Carbon\Carbon::parse($slot['availability_from_date_time'])->format('h:i A')
                                : null,
                            'end_time' => isset($slot['availability_to_date_time'])
                                ? \Carbon\Carbon::parse($slot['availability_to_date_time'])->format('h:i A')
                                : null,
                            'open_spots' => $slot['availability_spots']['availability_spots_open'] ?? 0,
                            'has_capacity' => $tour->has_capacity,
                        ];
                    })
                    ->filter(function ($slot) use ($tour) {
                        // If tour doesn't have capacity constraints, show all slots
                        if (!$tour->has_capacity) {
                            return true;
                        }
                        // Otherwise, only show slots with open spots
                        return $slot['open_spots'] > 0;
                    })
                    ->values()
                    ->toArray();

                return $formattedSlots;
            }
        }

        return [];
    }


    public function checkAvailability($tour, $date = null)
    {
        $requestedDate = $date ? Carbon::parse($date) : null;
        $isAvailable = false;
        $availableRanges = [];
        if (isset($tour->product_type_seasons)) {
            foreach ($tour->product_type_seasons as $season) {
                $start = Carbon::parse($season['product_type_season_start_date']);
                $end = Carbon::parse($season['product_type_season_end_date']);

                $availableRanges[] = [
                    'start' => $start->format('M d, Y'),
                    'end' => $end->format('M d, Y'),
                ];

                if ($requestedDate && $requestedDate->between($start, $end)) {
                    $isAvailable = true;
                }
            }
        }

        return [
            'is_available' => $isAvailable,
            'available_ranges' => $availableRanges,
        ];
    }

    public function loadTourBlocks(Request $request)
    {
        $searchQuery = $request->search_query;
        // Make sure we get an array
        $block = is_array($request->block) ? $request->block : json_decode($request->block, true);
        $block = array_filter($block, fn($id) => is_numeric($id)); // keep only numbers

        $limit = (int) $request->limit ?: 8;
        $offset = (int) $request->offset ?: 0;

        $colClass = $request->col_class ?? 'col-md-3';
        $cardStyle = $request->card_style ?? 'style3';

        // IDs already shown
        $excludedIds = array_map('intval', $block);

        // Query tours except already shown
        $toursQuery = Tour::where('status', 'active')
            ->whereNotIn('id', $excludedIds)
            ->where('name', 'like', '%' . $searchQuery . '%');

        // total count of remaining active tours
        $totalTours = $toursQuery->count();

        // apply offset and limit
        $tours = $toursQuery->skip($offset)->take($limit)->get();

        $remainingCount = max($totalTours - ($offset + $tours->count()), 0);

        return response()->json([
            'html' => view('frontend.partials.tour-cards', compact('tours', 'colClass', 'cardStyle'))->render(),
            'count' => $tours->count(),
            'remainingCount' => $remainingCount,
        ]);
    }


    public function saveReview(Request $request, $tourSlug)
    {

        $tour = Tour::where('slug', $tourSlug)->firstOrFail();
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'comment' => 'required|string',
            'rating' => 'required',
        ]);
        $validated['tour_id'] = $tour->id;
        $validated['user_id'] = auth()->user()->id;

        TourReview::create($validated);

        return back()->with('notify_success', 'Review Pending For Admin Approval!');
    }
}
