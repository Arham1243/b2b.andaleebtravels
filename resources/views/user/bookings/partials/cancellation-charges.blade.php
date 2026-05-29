@if (!($response['IsCancellable'] ?? false))
    <p class="text-danger mb-0">
        <i class="bx bx-x-circle"></i>
        Cancellation deadline has expired.
    </p>
@else
    @php
        $policy = $response['CancellationPolicyStatic'][0] ?? null;
        $today = now()->toDateString();
        $actionShown = false;
    @endphp

    @if ($policy)
        <h6 class="mb-3">
            Cancellation Policy — {{ $policy['RoomName'] ?? 'Room' }}
        </h6>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Charge</th>
                    <th>Valid Until</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($policy['CancellationCharges'] ?? [] as $charge)
                    @php
                        $expiryRaw = $charge['ExpiryDate'] ?? $charge['ExpiryDateUTC'] ?? null;
                        $expiry = $expiryRaw ? substr((string) $expiryRaw, 0, 10) : null;
                        $isCurrent = !$actionShown && $expiry && $today <= $expiry;
                        $amount = data_get($charge, 'Charge.Amount');
                        $currency = data_get($charge, 'Charge.Currency', '');
                    @endphp

                    @if ($expiry)
                        <tr @if ($isCurrent) class="table-warning fw-bold" @endif>
                            <td>
                                @if ($amount !== null && (float) $amount <= 0)
                                    Free cancellation
                                @else
                                    {{ $currency }} {{ number_format((float) ($amount ?? 0), 2) }}
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($expiry)->format('d M Y') }}</td>
                            <td>
                                @if ($isCurrent)
                                    <form onsubmit="return confirm('Are you sure you want to cancel this booking?')" method="GET"
                                        action="{{ $cancelUrl }}">
                                        <button class="btn btn-danger btn-sm">Cancel Booking</button>
                                    </form>
                                    @php $actionShown = true; @endphp
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        @if (!$actionShown)
            <p class="text-danger mb-0 mt-3">
                <i class="bx bx-time-five"></i>
                Cancellation deadline has expired.
            </p>
        @endif
    @else
        <p class="text-muted mb-0">No cancellation policy information available from the supplier.</p>
    @endif
@endif
