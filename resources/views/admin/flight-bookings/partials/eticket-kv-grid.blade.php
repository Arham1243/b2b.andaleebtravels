@php
    $items = $items ?? [];
    $wideKeys = $wideKeys ?? [];
    $monoKeys = $monoKeys ?? [];
@endphp
@if($items !== [])
    <div class="eta-kv-grid">
        @foreach($items as $key => $item)
            @php
                $fieldKey = is_string($key) ? $key : '';
                $label = is_array($item) ? (string) ($item['label'] ?? $fieldKey) : (string) $key;
                $value = is_array($item) ? ($item['value'] ?? '') : $item;
                $isWide = ($item['wide'] ?? false)
                    || in_array($fieldKey, $wideKeys, true)
                    || in_array($label, $wideKeys, true);
                $isMono = ($item['mono'] ?? false) || in_array($fieldKey, $monoKeys, true);
            @endphp
            @if($value !== null && $value !== '')
                <div class="eta-kv{{ $isWide ? ' eta-kv--wide' : '' }}">
                    <span class="eta-kv__label">{{ $label }}</span>
                    <span class="eta-kv__val{{ $isMono ? ' eta-kv__val--mono' : '' }}">{!! $value !!}</span>
                </div>
            @endif
        @endforeach
    </div>
@endif
