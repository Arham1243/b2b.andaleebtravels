@php
    $timerRedirectUrl = $timerRedirectUrl ?? '';
    $timerStorageKey = $timerStorageKey ?? 'flight_booking_session_expires';
    $timerMinutes = (int) ($timerMinutes ?? 20);
@endphp
@if ($timerRedirectUrl !== '')
<div class="hp-session-timer" id="hp-session-timer" aria-live="polite">
    <div class="hp-session-timer__ring">
        <svg viewBox="0 0 36 36" class="hp-session-timer__svg" aria-hidden="true">
            <path class="hp-session-timer__track"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
            <path class="hp-session-timer__progress" id="hp-session-timer-progress"
                stroke-dasharray="100, 100"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
        </svg>
        <span class="hp-session-timer__time" id="hp-session-timer-label">20:00</span>
    </div>
    <div class="hp-session-timer__meta">
        <span class="hp-session-timer__title">Session expires in</span>
        <span class="hp-session-timer__hint">Complete booking before time runs out</span>
    </div>
</div>
@endif

@once
@push('css')
<style>
.hp-session-timer {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .75rem 1.1rem;
    border-bottom: 1px solid var(--c-line, #dde3ef);
    background: linear-gradient(135deg, rgba(205,27,79,.04) 0%, transparent 70%);
}
.hp-session-timer__ring {
    position: relative;
    width: 52px;
    height: 52px;
    flex-shrink: 0;
}
.hp-session-timer__svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}
.hp-session-timer__track {
    fill: none;
    stroke: #e8edf5;
    stroke-width: 3;
}
.hp-session-timer__progress {
    fill: none;
    stroke: var(--c-brand, #cd1b4f);
    stroke-width: 3;
    stroke-linecap: round;
    transition: stroke-dasharray .35s linear;
}
.hp-session-timer__time {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--mono, ui-monospace, monospace);
    font-size: .62rem;
    font-weight: 700;
    color: var(--c-ink, #1a2540);
}
.hp-session-timer__meta {
    display: flex;
    flex-direction: column;
    gap: .12rem;
    min-width: 0;
}
.hp-session-timer__title {
    font-size: .72rem;
    font-weight: 700;
    color: var(--c-ink, #1a2540);
}
.hp-session-timer__hint {
    font-size: .64rem;
    color: var(--c-muted, #8492a6);
}
.hp-session-timer--urgent .hp-session-timer__progress { stroke: #dc2626; }
.hp-session-timer--urgent .hp-session-timer__time { color: #dc2626; }
</style>
@endpush
@endonce

@push('js')
<script>
(function () {
    const redirectUrl = @json($timerRedirectUrl);
    const storageKey = @json($timerStorageKey);
    const totalMs = {{ max(1, $timerMinutes) }} * 60 * 1000;

    if (!redirectUrl) return;

    const timerEl = document.getElementById('hp-session-timer');
    const labelEl = document.getElementById('hp-session-timer-label');
    const progressEl = document.getElementById('hp-session-timer-progress');
    if (!timerEl || !labelEl || !progressEl) return;

    let expiresAt = Number(sessionStorage.getItem(storageKey) || 0);
    if (!expiresAt || expiresAt <= Date.now()) {
        expiresAt = Date.now() + totalMs;
        sessionStorage.setItem(storageKey, String(expiresAt));
    }

    function pad(n) { return String(n).padStart(2, '0'); }

    function tick() {
        const remaining = expiresAt - Date.now();
        if (remaining <= 0) {
            sessionStorage.removeItem(storageKey);
            window.location.href = redirectUrl;
            return;
        }

        const mins = Math.floor(remaining / 60000);
        const secs = Math.floor((remaining % 60000) / 1000);
        const pct = (remaining / totalMs) * 100;

        labelEl.textContent = pad(mins) + ':' + pad(secs);
        progressEl.setAttribute('stroke-dasharray', pct.toFixed(2) + ', 100');
        timerEl.classList.toggle('hp-session-timer--urgent', remaining < 120000);
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
@endpush
