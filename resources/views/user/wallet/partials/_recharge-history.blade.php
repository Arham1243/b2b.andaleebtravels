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
                            <td>
                                @switch($recharge->payment_method)
                                    @case('bank_transfer')
                                        Bank transfer
                                        @break
                                    @case('payby')
                                        Card (PayBy)
                                        @break
                                    @case('tabby')
                                        Tabby
                                        @break
                                    @default
                                        {{ strtoupper($recharge->payment_method) }}
                                @endswitch
                            </td>
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
                                @elseif ($recharge->payment_method === 'bank_transfer')
                                    <span class="text-warning">
                                        <i class='bx bxs-time'></i> Awaiting admin confirmation
                                    </span>
                                @else
                                    <span class="text-warning">
                                        <i class='bx bxs-time'></i> Awaiting payment
                                    </span>
                                @endif
                            </td>
                            <td>{{ $recharge->created_at->format('d M Y, h:i A') }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1 justify-content-start align-items-center">
                                    @if ($recharge->proof_image_path)
                                        <a href="{{ walletBankProofUrl($recharge->proof_image_path) }}" target="_blank" rel="noopener"
                                           class="btn btn-sm btn-outline-secondary wallet-action-btn">
                                            <i class='bx bx-image'></i> Proof
                                        </a>
                                    @endif
                                    @if ($recharge->status === 'failed' && in_array($recharge->payment_method, ['payby', 'tabby'], true))
                                        <a href="{{ route('user.wallet.recharge.retry', $recharge->id) }}"
                                            class="btn btn-sm btn-outline-primary wallet-action-btn"
                                            onclick="return confirm('Retry payment of {{ number_format($recharge->amount, 2) }} AED?')">
                                            <i class='bx bx-refresh'></i> Pay Again
                                        </a>
                                    @endif
                                    @if (!$recharge->proof_image_path && !($recharge->status === 'failed' && in_array($recharge->payment_method, ['payby', 'tabby'], true)))
                                        <span class="text-muted"> - </span>
                                    @endif
                                </div>
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
