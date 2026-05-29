@php
    $supportWhatsapp = \App\Support\SupportContact::whatsapp($config ?? []);
    $supportEmail = \App\Support\SupportContact::email($config ?? []);
@endphp
<a href="{{ $supportWhatsapp['link'] }}" class="hs-support-strip__link" target="_blank" rel="noopener">
    <i class='bx bxl-whatsapp'></i> {{ $supportWhatsapp['display'] }}
</a>

<a href="mailto:{{ $supportEmail }}" class="hs-support-strip__link">
    <i class='bx bxs-envelope'></i> {{ $supportEmail }}
</a>
