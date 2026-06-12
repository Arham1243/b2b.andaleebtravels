<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bSavedPassenger;
use App\Models\B2bVendor;
use App\Support\FlightPassengerDobValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendorSavedPassengerController extends Controller
{
    public function store(Request $request, B2bVendor $vendor)
    {
        $data = $this->validatedPassenger($request);
        $vendor->savedPassengers()->create($data);

        return redirect()
            ->to(route('admin.vendors.show', $vendor) . '?tab=passengers')
            ->with('notify_success', 'Passenger saved successfully.');
    }

    public function update(Request $request, B2bVendor $vendor, B2bSavedPassenger $passenger)
    {
        $this->authorizePassenger($vendor, $passenger);

        $data = $this->validatedPassenger($request);
        $passenger->update($data);

        return redirect()
            ->to(route('admin.vendors.show', $vendor) . '?tab=passengers')
            ->with('notify_success', 'Passenger updated successfully.');
    }

    public function destroy(B2bVendor $vendor, B2bSavedPassenger $passenger)
    {
        $this->authorizePassenger($vendor, $passenger);
        $passenger->delete();

        return redirect()
            ->to(route('admin.vendors.show', $vendor) . '?tab=passengers')
            ->with('notify_success', 'Passenger removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPassenger(Request $request): array
    {
        $validated = $request->validate([
            'passenger_type' => ['required', Rule::in(['ADT', 'CHD', 'INF'])],
            'title' => 'required|string|max:10',
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'dob' => 'nullable|date|before_or_equal:today',
            'nationality' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'issuing_country' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'passport_no' => 'nullable|string|max:20',
            'passport_exp' => 'nullable|date|after:today',
        ]);

        $validated['passenger_type'] = B2bSavedPassenger::normalizeType($validated['passenger_type']);
        $validated['nationality'] = isset($validated['nationality'])
            ? strtoupper($validated['nationality'])
            : null;
        $validated['issuing_country'] = isset($validated['issuing_country'])
            ? strtoupper($validated['issuing_country'])
            : null;

        $dobError = FlightPassengerDobValidator::validateDobForType(
            $validated['dob'] ?? null,
            $validated['passenger_type'],
            Carbon::today(),
        );

        if ($dobError !== null) {
            throw ValidationException::withMessages(['dob' => $dobError]);
        }

        return $validated;
    }

    private function authorizePassenger(B2bVendor $vendor, B2bSavedPassenger $passenger): void
    {
        if ((int) $passenger->b2b_vendor_id !== (int) $vendor->id) {
            abort(404);
        }
    }
}
