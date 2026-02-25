@if (!($response['IsCancellable'] ?? false))
    <p class="text-danger">This booking is not cancellable.</p>
@else
    @php
        $policy = $response['CancellationPolicyStatic'][0] ?? null;
        $today = now()->toDateString();
        $actionShown = false;
    @endphp

    @if ($policy)
        <h6 class="mb-3">
            Cancellation Policy - {{ $policy['RoomName'] ?? 'Room' }}
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
                @foreach ($policy['CancellationCharges'] as $charge)
                    @php
                        $expiry = substr($charge['ExpiryDate'], 0, 10);
                        $isCurrent = !$actionShown && $today <= $expiry;
                    @endphp

                    <tr @if ($isCurrent) class="table-warning fw-bold" @endif>
                        <td>{{ $charge['Charge']['Currency'] }} {{ number_format($charge['Charge']['Amount'], 2) }}</td>
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
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-muted">No cancellation policy information available.</p>
    @endif
@endif
