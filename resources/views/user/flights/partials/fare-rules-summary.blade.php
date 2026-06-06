@php
    $fareRules = $fareRules ?? [];
    $fareBrand = trim((string) ($fareBrand ?? ''));
    $nonRefund = (bool) ($nonRefund ?? false);
    if (array_key_exists('refundable', $fareRules)) {
        $nonRefund = ! (bool) $fareRules['refundable'];
    }
    $routeLabel = trim((string) ($routeLabel ?? ''));
@endphp

<div class="fd-rules__route">{{ $routeLabel !== '' ? $routeLabel : 'Fare Rules' }}</div>

<div class="fd-rules__summary">
    <div class="fd-rules__row">
        <span class="fd-rules__key">Refundability</span>
        <span class="fd-rules__val fd-rules__val--{{ $nonRefund ? 'nr' : 'ref' }}">
            {{ $fareRules['refund_label'] ?? ($nonRefund ? 'Non-Refundable' : 'Refundable') }}
        </span>
    </div>
    @if($fareBrand !== '' || !empty($fareRules['fare_brand']))
        <div class="fd-rules__row">
            <span class="fd-rules__key">Fare Brand</span>
            <span class="fd-rules__val">{{ $fareRules['fare_brand'] ?? $fareBrand }}</span>
        </div>
    @endif
    @if(!empty($fareRules['validating_carrier']))
        <div class="fd-rules__row">
            <span class="fd-rules__key">Validating Carrier</span>
            <span class="fd-rules__val">{{ $fareRules['validating_carrier'] }}</span>
        </div>
    @endif
    @if(!empty($fareRules['last_ticket_display']))
        <div class="fd-rules__row">
            <span class="fd-rules__key">Last Ticket Date</span>
            <span class="fd-rules__val">{{ $fareRules['last_ticket_display'] }}</span>
        </div>
    @endif
</div>

@if(!empty($fareRules['policy_sections']))
    <div class="fd-rules__section">
        @foreach($fareRules['policy_sections'] as $section)
            <div class="fd-rules__policy">
                <div class="fd-rules__section-title">{{ $section['title'] ?? 'Policy' }}</div>
                <ul class="fd-rules__list">
                    @foreach($section['items'] ?? [] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
@endif

@if(!empty($fareRules['components']))
    <div class="fd-rules__section">
        <div class="fd-rules__section-title">Fare Components</div>
        @foreach($fareRules['components'] as $component)
            <div class="fd-rules__component">
                <div class="fd-rules__component-route">{{ $component['route'] ?? 'Segment' }}</div>
                <div class="fd-rules__component-grid">
                    @if(!empty($component['brand']))
                        <div><span>Brand</span><strong>{{ $component['brand'] }}</strong></div>
                    @endif
                    @if(!empty($component['fare_basis']))
                        <div><span>Fare Basis</span><strong>{{ $component['fare_basis'] }}</strong></div>
                    @endif
                    @if(!empty($component['fare_rule']))
                        <div><span>Fare Rule</span><strong>{{ $component['fare_rule'] }}</strong></div>
                    @endif
                    @if(!empty($component['cabin']))
                        <div><span>Cabin</span><strong>{{ formatFlightCabinLabel($component['cabin']) }}</strong></div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

@if(!empty($fareRules['notes']))
    <div class="fd-rules__notes">
        @foreach($fareRules['notes'] as $note)
            <p><i class="bx bx-info-circle"></i> {{ $note }}</p>
        @endforeach
    </div>
@endif
