<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{

    public $apiKey = '93082895-c45f-489f-ae10-bed9eaae161e';
    public $url = 'https://api.yalago.com/hotels/Inventory/GetProvinces';

    public function index()
    {
        $title = 'Manage Provinces';
        $provinces = Province::latest()->paginate(10);
        return view('admin.hotels.provinces.list', compact('provinces', 'title'));
    }

    public function create()
    {
        $title = 'Add New Province';
        $countries = Country::where('status', 'active')
            ->orderBy('name', 'asc')
            ->get();
        return view('admin.hotels.provinces.add', compact('title', 'countries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'yalago_id' => 'required|int',
            'status' => 'required|in:active,inactive',
        ]);

        Province::create([
            'name' => $request->name,
            'country_id' => $request->country_id,
            'yalago_id' => $request->yalago_id,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.provinces.index')->with('notify_success', 'Province created successfully!');
    }

    public function edit(Province $province)
    {
        $title = 'Edit Province - ' . $province->name;
        $countries = Country::where('status', 'active')
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.hotels.provinces.edit', compact('province', 'title', 'countries'));
    }

    public function update(Request $request, Province $province)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'yalago_id' => 'required|int',
            'status' => 'required|in:active,inactive',
        ]);

        $province->update([
            'name' => $request->name,
            'country_id' => $request->country_id,
            'yalago_id' => $request->yalago_id,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.provinces.index')->with('notify_success', 'Province updated successfully!');
    }

    public function sync(Country $country)
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Connection: keep-alive',
            'Accept' => 'application/json',
        ])->post($this->url, [
            'CountryId' => $country->yalago_id,
        ]);

        if ($response->failed()) {
            return redirect()->back()
                ->with('notify_error', 'Failed to fetch provinces from Yalago.');
        }

        $provinces = $response->json('Provinces', []);
        $newCount = 0;

        foreach ($provinces as $province) {

            $existing = Province::where('yalago_id', $province['ProvinceId'])->first();

            if ($existing) {
                // update existing
                $existing->update([
                    'country_id' => $country->id,
                    'name'       => $province['Title'],
                    'status'     => 'active',
                ]);
            } else {
                // insert new
                Province::create([
                    'yalago_id'  => $province['ProvinceId'],
                    'country_id' => $country->id,
                    'name'       => $province['Title'],
                    'status'     => 'active',
                ]);

                $newCount++;
            }
        }

        $this->dumpToJson();

        return redirect()->route('admin.provinces.index')
            ->with('notify_success', "{$newCount} Provinces synced");
    }

    protected function dumpToJson()
    {
        $provinces = Province::with('country')
            ->get()
            ->sortBy('name')
            ->values()
            ->map(function ($province) {
                return [
                    'id' => $province->id,
                    'name' => $province->name,
                    'country_id' => $province->country_id,
                    'country_name' => $province->country->name ?? null,
                ];
            });

        file_put_contents(
            public_path('frontend/mocks/yalago_provinces.json'),
            $provinces->toJson(JSON_PRETTY_PRINT)
        );
    }
}
