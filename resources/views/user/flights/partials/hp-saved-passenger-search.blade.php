<div class="hp-saved-row">
    <label class="hp-label" for="saved-{{ $pIndex }}">Load from saved passengers</label>
    <select class="hp-select hp-saved-pick" id="saved-{{ $pIndex }}" data-pax-idx="{{ $pIndex }}">
        <option value="">- Select saved passenger -</option>
        @foreach ($savedPassengers as $sp)
            <option value="{{ json_encode($sp) }}">
                {{ $sp['title'] }} {{ $sp['first_name'] }} {{ $sp['last_name'] }}
            </option>
        @endforeach
    </select>
</div>
