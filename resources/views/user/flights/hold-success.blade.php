@extends('user.layouts.main')
@section('content')
<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:2rem;font-family:'Inter',sans-serif;">
    <div style="max-width:520px;width:100%;text-align:center;background:#fff;border:1px solid #dde3ef;border-radius:16px;padding:2.5rem 2rem;box-shadow:0 6px 24px rgba(26,37,64,.08);">
        <div style="font-size:3rem;color:#0f9d58;margin-bottom:1rem;"><i class="bx bx-check-circle"></i></div>
        <h2 style="font-size:1.4rem;font-weight:700;color:#1a2540;margin:0 0 .5rem;">Booking On Hold!</h2>
        <p style="font-size:.9rem;color:#4a5568;margin:0 0 1.5rem;line-height:1.6;">
            Your booking has been placed on hold. The PNR will be created at the airline end within the next few minutes.<br>
            No payment has been charged at this time.
        </p>

        @if(!empty($booking->sabre_record_locator))
        <div style="background:#f3f5fb;border:1px solid #dde3ef;border-radius:10px;padding:.85rem 1.2rem;margin-bottom:1.5rem;">
            <div style="font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#8492a6;">PNR / Record Locator</div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:1.6rem;font-weight:700;color:#cd1b4f;margin-top:.2rem;">{{ $booking->sabre_record_locator }}</div>
        </div>
        @endif

        <div style="background:#fef3c7;border:1px solid rgba(217,119,6,.25);border-radius:8px;padding:.75rem 1rem;margin-bottom:1.5rem;font-size:.78rem;color:#b45309;text-align:left;">
            <i class="bx bx-time-five" style="vertical-align:middle;margin-right:.35rem;"></i>
            Your hold is valid for approximately <strong>1 hour</strong>. Please complete ticketing within this window to avoid auto-cancellation.
        </div>

        <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="{{ route('user.bookings.index') }}"
               style="display:inline-flex;align-items:center;gap:.3rem;padding:.6rem 1.2rem;border-radius:8px;background:#cd1b4f;color:#fff;font-weight:700;font-size:.88rem;text-decoration:none;">
                <i class="bx bx-list-ul"></i> View My Bookings
            </a>
            <a href="{{ route('user.flights.index') }}"
               style="display:inline-flex;align-items:center;gap:.3rem;padding:.6rem 1.2rem;border-radius:8px;background:#fff;border:1.5px solid #dde3ef;color:#4a5568;font-weight:700;font-size:.88rem;text-decoration:none;">
                <i class="bx bxs-plane-take-off"></i> New Search
            </a>
        </div>
    </div>
</div>
@endsection
