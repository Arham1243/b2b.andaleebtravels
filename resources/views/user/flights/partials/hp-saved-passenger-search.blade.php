<div class="hp-saved-row">
    <label class="hp-label" for="saved-search-{{ $pIndex }}">Search saved passenger</label>
    <div class="hp-ac-wrap hp-saved-ac" data-pax-idx="{{ $pIndex }}">
        <input type="text"
            id="saved-search-{{ $pIndex }}"
            class="hp-input hp-saved-ac-input"
            placeholder="Type passenger name to search..."
            autocomplete="off"
            aria-label="Search saved passenger">
        <div class="hp-ac-dropdown hp-saved-ac-dropdown" hidden></div>
    </div>
</div>
