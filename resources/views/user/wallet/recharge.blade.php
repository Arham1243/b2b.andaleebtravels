@extends('user.layouts.main')

@section('css')
    <style>
        .recharge-section {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .recharge-section__title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }

        .recharge-section__title i {
            font-size: 24px;
            color: #0d2f81;
        }

        .quick-amounts {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .quick-amounts__divider {
            border-left: 3px solid #0d2f81;
            height: 40px;
        }

        .quick-amount-btn {
            padding: 10px 28px;
            border: 2px solid #dee2e6;
            background: #fff;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .quick-amount-btn:hover {
            border-color: #0d2f81;
            color: #0d2f81;
        }

        .quick-amount-btn.active {
            background: #0d2f81;
            border-color: #0d2f81;
            color: #fff;
        }

        .quick-amounts__or {
            font-weight: 600;
            color: #0d2f81;
            font-size: 15px;
        }

        .custom-amount-input {
            border: 2px solid #dee2e6;
            border-radius: 6px;
            padding: 10px 16px;
            font-size: 15px;
            width: 180px;
            outline: none;
            transition: border-color 0.2s;
        }

        .custom-amount-input:focus {
            border-color: #0d2f81;
        }

        .custom-amount-input::placeholder {
            color: #999;
        }

        .payment-methods {
            display: flex;
            gap: 16px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .payment-method-card {
            flex: 1;
            min-width: 250px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .payment-method-card:hover {
            border-color: #0d2f81;
        }

        .payment-method-card.selected {
            border-color: #0d2f81;
            background: #f0f4ff;
        }

        .payment-method-card input[type="radio"] {
            position: absolute;
            top: 16px;
            right: 16px;
            accent-color: #0d2f81;
            width: 18px;
            height: 18px;
        }

        .payment-method-card__title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 4px;
        }

        .payment-method-card__desc {
            font-size: 13px;
            color: #666;
        }

        .payment-method-card__icon {
            font-size: 28px;
            color: #0d2f81;
            margin-bottom: 10px;
        }

        .btn-recharge {
            background: #0d2f81;
            color: #fff;
            border: none;
            padding: 12px 40px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 20px;
        }

        .btn-recharge:hover {
            background: #0a2466;
            color: #fff;
        }

        .btn-recharge:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .current-balance {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #e8f5e9;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 600;
            color: #2e7d32;
            font-size: 15px;
        }

        .current-balance i {
            font-size: 20px;
        }
    </style>
@endsection

@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            <div class="recharge-section">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <h3 class="heading mb-0">Wallet Recharge</h3>
                    <div class="current-balance">
                        <i class='bx bxs-wallet'></i>
                        Current Balance: {!! formatPrice(Auth::user()->main_balance) !!}
                    </div>
                </div>

                <form action="{{ route('user.wallet.recharge.process') }}" method="POST" id="rechargeForm">
                    @csrf

                    {{-- Credit Card (PayBy) --}}
                    <div class="mb-4">
                        <div class="recharge-section__title">
                            <i class='bx bxs-credit-card'></i> Credit Card
                        </div>
                        <div class="quick-amounts">
                            <div class="quick-amounts__divider"></div>
                            <button type="button" class="quick-amount-btn" data-amount="1000">1000</button>
                            <button type="button" class="quick-amount-btn" data-amount="2500">2500</button>
                            <button type="button" class="quick-amount-btn" data-amount="5000">5000</button>
                            <button type="button" class="quick-amount-btn" data-amount="10000">10000</button>
                            <span class="quick-amounts__or">OR</span>
                            <div>
                                <label class="form-label mb-1" style="font-size: 12px; color: #666;">Enter Amount*</label>
                                <input type="number" name="amount" id="amountInput" class="custom-amount-input"
                                    placeholder="1000" min="100" max="50000" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Method Selection --}}
                    <div class="mb-3">
                        <div class="recharge-section__title">
                            <i class='bx bxs-bank'></i> Payment Method
                        </div>
                        <div class="payment-methods">
                            <label class="payment-method-card selected" data-method="payby">
                                <input type="radio" name="payment_method" value="payby" checked>
                                <div class="payment-method-card__icon">
                                    <i class='bx bxs-credit-card-front'></i>
                                </div>
                                <div class="payment-method-card__title">Credit / Debit Card</div>
                                <div class="payment-method-card__desc">Pay securely via PayBy</div>
                            </label>
                            <label class="payment-method-card" data-method="tabby">
                                <input type="radio" name="payment_method" value="tabby">
                                <div class="payment-method-card__icon">
                                    <i class='bx bxs-calendar-check'></i>
                                </div>
                                <div class="payment-method-card__title">Tabby</div>
                                <div class="payment-method-card__desc">Split in 4 interest-free payments</div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-recharge" id="rechargeBtn">
                        <i class='bx bxs-bolt'></i> Recharge Now
                    </button>
                </form>
            </div>

            {{-- Transaction History Table --}}
            <div class="table-container universal-table">
                <div class="custom-sec">
                    <div class="custom-sec__header">
                        <div class="section-content">
                            <h3 class="heading">Transaction History</h3>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Transaction #</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recharges as $recharge)
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
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No recharge transactions yet.
                                        </td>
                                    </tr>
                                @endforelse
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
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quickBtns = document.querySelectorAll('.quick-amount-btn');
            const amountInput = document.getElementById('amountInput');
            const paymentCards = document.querySelectorAll('.payment-method-card');
            const form = document.getElementById('rechargeForm');

            // Quick amount buttons
            quickBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    quickBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    amountInput.value = this.dataset.amount;
                });
            });

            // When user types custom amount, deselect quick buttons
            amountInput.addEventListener('input', function() {
                const val = parseInt(this.value);
                quickBtns.forEach(b => {
                    b.classList.toggle('active', parseInt(b.dataset.amount) === val);
                });
            });

            // Payment method card selection
            paymentCards.forEach(card => {
                card.addEventListener('click', function() {
                    paymentCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    this.querySelector('input[type="radio"]').checked = true;
                });
            });

            // Select first quick amount by default
            if (quickBtns.length > 0) {
                quickBtns[0].click();
            }

            // Prevent double submit
            form.addEventListener('submit', function() {
                const btn = document.getElementById('rechargeBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';
            });
        });
    </script>
@endpush
