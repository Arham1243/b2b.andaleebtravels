@php
    use App\Models\B2bSavedPassenger;

    $paxType = $paxType ?? 'ADT';
    $filteredSaved = B2bSavedPassenger::filterForBookingType($savedPassengers ?? [], $paxType);
@endphp

@if (!empty($filteredSaved))
    <div class="hp-saved-row">
        <label class="hp-label" for="saved-{{ $pIndex }}">Load from saved passengers</label>
        <select class="hp-select hp-saved-pick" id="saved-{{ $pIndex }}" data-pax-idx="{{ $pIndex }}" data-pax-type="{{ B2bSavedPassenger::normalizeType($paxType) }}">
            <option value="">- Select saved passenger -</option>
            @foreach ($filteredSaved as $sp)
                <option value="{{ json_encode($sp) }}">
                    {{ $sp['title'] }} {{ $sp['first_name'] }} {{ $sp['last_name'] }}
                </option>
            @endforeach
        </select>
    </div>
@endif
