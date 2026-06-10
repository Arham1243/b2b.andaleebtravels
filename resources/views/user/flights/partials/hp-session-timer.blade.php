@php
    $timerRedirectUrl = $timerRedirectUrl ?? '';
    $timerStorageKey = $timerStorageKey ?? 'flight_booking_session_expires';
    $timerMinutes = (int) ($timerMinutes ?? 20);
@endphp
@if ($timerRedirectUrl !== '')
<div class="hp-session-timer" id="hp-session-timer" aria-live="polite">
    <div class="hp-session-timer__ring">
        <svg viewBox="0 0 80 80" class="hp-session-timer__svg" aria-hidden="true">
            <circle class="hp-session-timer__track" cx="40" cy="40" r="32" pathLength="100" />
            <circle class="hp-session-timer__progress" id="hp-session-timer-progress" cx="40" cy="40" r="32" pathLength="100" />
        </svg>
        <div class="hp-session-timer__time" id="hp-session-timer-label">
            <span class="hp-session-timer__mins">{{ str_pad((string) $timerMinutes, 2, '0', STR_PAD_LEFT) }}</span>
            <span class="hp-session-timer__sep">:</span>
            <span class="hp-session-timer__secs">00</span>
        </div>
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
    gap: 1rem;
    padding: 1rem 1.15rem;
    background: var(--c-white, #fff);
    border: 1px solid var(--c-line, #dde3ef);
    border-radius: 14px;
    box-shadow: var(--c-shadow, 0 2px 8px rgba(26,37,64,.07));
    flex-shrink: 0;
}
.hp-session-timer__ring {
    position: relative;
    width: 76px;
    height: 76px;
    flex-shrink: 0;
}
.hp-session-timer__svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}
.hp-session-timer__track,
.hp-session-timer__progress {
    fill: none;
    stroke-width: 6;
}
.hp-session-timer__track {
    stroke: #e8edf5;
    stroke-dasharray: 100;
    stroke-dashoffset: 0;
}
.hp-session-timer__progress {
    stroke: var(--c-brand, #cd1b4f);
    stroke-linecap: round;
    stroke-dasharray: 100;
    stroke-dashoffset: 0;
    transition: stroke-dashoffset 1s linear, stroke .3s ease;
}
.hp-session-timer__time {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--mono, ui-monospace, monospace);
    font-size: .92rem;
    font-weight: 800;
    color: var(--c-ink, #1a2540);
    line-height: 1;
    letter-spacing: -.02em;
}
.hp-session-timer__sep {
    margin: 0 .02rem;
    animation: hp-timer-blink 1s step-end infinite;
}
.hp-session-timer__meta {
    display: flex;
    flex-direction: column;
    gap: .2rem;
    min-width: 0;
}
.hp-session-timer__title {
    font-size: .82rem;
    font-weight: 700;
    color: var(--c-ink, #1a2540);
}
.hp-session-timer__hint {
    font-size: .7rem;
    color: var(--c-muted, #8492a6);
    line-height: 1.35;
}
.hp-session-timer--urgent .hp-session-timer__progress { stroke: #dc2626; }
.hp-session-timer--urgent .hp-session-timer__time { color: #dc2626; }
@keyframes hp-timer-blink {
    50% { opacity: 0; }
}
</style>
@endpush
@endonce

@if ($timerRedirectUrl !== '')
@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const redirectUrl = @json($timerRedirectUrl);
    const storageKey = @json($timerStorageKey);
    const totalMs = {{ max(1, $timerMinutes) }} * 60 * 1000;
    const startKey = storageKey + '_start';
    const RING_MAX = 100;

    const timerEl = document.getElementById('hp-session-timer');
    const labelEl = document.getElementById('hp-session-timer-label');
    const progressEl = document.getElementById('hp-session-timer-progress');
    if (!timerEl || !labelEl || !progressEl) return;

    let startedAt = Number(sessionStorage.getItem(startKey) || 0);
    let expiresAt = Number(sessionStorage.getItem(storageKey) || 0);
    const now = Date.now();

    if (!startedAt || !expiresAt || expiresAt <= now) {
        startedAt = now;
        expiresAt = now + totalMs;
        sessionStorage.setItem(startKey, String(startedAt));
        sessionStorage.setItem(storageKey, String(expiresAt));
    }

    const minsEl = labelEl.querySelector('.hp-session-timer__mins');
    const secsEl = labelEl.querySelector('.hp-session-timer__secs');

    function pad(n) { return String(n).padStart(2, '0'); }

    function setRingProgress(pct) {
        const clamped = Math.max(0, Math.min(1, pct));
        progressEl.style.strokeDasharray = String(RING_MAX);
        progressEl.style.strokeDashoffset = String(RING_MAX * (1 - clamped));
    }

    function tick() {
        const remaining = expiresAt - Date.now();
        if (remaining <= 0) {
            sessionStorage.removeItem(storageKey);
            sessionStorage.removeItem(startKey);
            window.location.href = redirectUrl;
            return;
        }

        const mins = Math.floor(remaining / 60000);
        const secs = Math.floor((remaining % 60000) / 1000);
        const sessionLength = Math.max(expiresAt - startedAt, 1);
        const pct = remaining / sessionLength;

        if (minsEl) minsEl.textContent = pad(mins);
        if (secsEl) secsEl.textContent = pad(secs);
        setRingProgress(pct);
        timerEl.classList.toggle('hp-session-timer--urgent', remaining < 120000);
    }

    tick();
    setInterval(tick, 1000);
});
</script>
@endpush
@endif
