<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\B2bSavedPassenger;
use App\Support\CountryCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SavedPassengerController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $filter = strtolower(trim((string) $request->query('type', 'all')));

        $query = $user->savedPassengers()->orderBy('passenger_type')->orderBy('first_name');

        if ($filter === 'adt' || $filter === 'adult' || $filter === 'adults') {
            $query->where('passenger_type', B2bSavedPassenger::TYPE_ADULT);
        } elseif ($filter === 'chd' || $filter === 'child' || $filter === 'children') {
            $query->where('passenger_type', B2bSavedPassenger::TYPE_CHILD);
        } elseif ($filter === 'inf' || $filter === 'infant' || $filter === 'infants') {
            $query->where('passenger_type', B2bSavedPassenger::TYPE_INFANT);
        } else {
            $filter = 'all';
        }

        $passengers = $query->get();
        $counts = [
            'all' => $user->savedPassengers()->count(),
            'adt' => $user->savedPassengers()->where('passenger_type', B2bSavedPassenger::TYPE_ADULT)->count(),
            'chd' => $user->savedPassengers()->where('passenger_type', B2bSavedPassenger::TYPE_CHILD)->count(),
            'inf' => $user->savedPassengers()->where('passenger_type', B2bSavedPassenger::TYPE_INFANT)->count(),
        ];

        return view('user.profile-settings.saved-passengers')
            ->with('title', 'Saved Passengers')
            ->with([
                'user' => $user,
                'passengers' => $passengers,
                'filter' => $filter,
                'counts' => $counts,
                'countries' => CountryCatalog::forAutocomplete(),
            ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedPassenger($request);
        Auth::user()->savedPassengers()->create($data);

        return redirect()
            ->route('user.profile.savedPassengers', ['type' => $this->filterForType($data['passenger_type'])])
            ->with('notify_success', 'Passenger saved successfully.');
    }

    public function update(Request $request, B2bSavedPassenger $passenger)
    {
        $this->authorizePassenger($passenger);

        $data = $this->validatedPassenger($request);
        $passenger->update($data);

        return redirect()
            ->route('user.profile.savedPassengers', ['type' => $this->filterForType($data['passenger_type'])])
            ->with('notify_success', 'Passenger updated successfully.');
    }

    public function destroy(B2bSavedPassenger $passenger)
    {
        $this->authorizePassenger($passenger);

        $type = $this->filterForType($passenger->passenger_type);
        $passenger->delete();

        return redirect()
            ->route('user.profile.savedPassengers', ['type' => $type])
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

        return $validated;
    }

    private function authorizePassenger(B2bSavedPassenger $passenger): void
    {
        if ((int) $passenger->b2b_vendor_id !== (int) Auth::id()) {
            abort(404);
        }
    }

    private function filterForType(?string $type): string
    {
        return match (B2bSavedPassenger::normalizeType($type)) {
            B2bSavedPassenger::TYPE_CHILD => 'chd',
            B2bSavedPassenger::TYPE_INFANT => 'inf',
            default => 'adt',
        };
    }
}
