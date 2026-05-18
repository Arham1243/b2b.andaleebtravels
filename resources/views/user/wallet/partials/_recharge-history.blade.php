<div class="table-container universal-table">
    <div class="custom-sec">
        <div class="custom-sec__header">
            <div class="section-content">
                <h3 class="heading">Transaction History</h3>
            </div>
        </div>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Transaction #</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recharges as $recharge)
                        <tr>
                            <td>
                                <span class="fw-semibold">{{ $recharge->transaction_number }}</span>
                            </td>
                            <td>{!! formatPrice($recharge->amount) !!}</td>
                            <td>{{ strtoupper($recharge->payment_method) }}</td>
                            <td>
                                <span
                                    class="badge rounded-pill bg-{{ $recharge->status === 'paid' ? 'success' : ($recharge->status === 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($recharge->status) }}
                                </span>
                            </td>
                            <td>
                                @if ($recharge->status === 'paid')
                                    <span class="text-success">
                                        <i class='bx bxs-check-circle'></i> Wallet credited successfully
                                    </span>
                                @elseif ($recharge->status === 'failed')
                                    <span class="text-danger">
                                        <i class='bx bxs-x-circle'></i>
                                        {{ $recharge->failure_reason ?? 'Payment failed' }}
                                    </span>
                                @else
                                    <span class="text-warning">
                                        <i class='bx bxs-time'></i> Awaiting payment
                                    </span>
                                @endif
                            </td>
                            <td>{{ $recharge->created_at->format('d M Y, h:i A') }}</td>
                            <td>
                                @if ($recharge->status === 'failed')
                                    <a href="{{ route('user.wallet.recharge.retry', $recharge->id) }}"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="return confirm('Retry payment of {{ number_format($recharge->amount, 2) }} AED?')">
                                        <i class='bx bx-refresh'></i> Pay Again
                                    </a>
                                @else
                                    <span class="text-muted"> - </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($recharges->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $recharges->links() }}
            </div>
        @endif
    </div>
</div>
