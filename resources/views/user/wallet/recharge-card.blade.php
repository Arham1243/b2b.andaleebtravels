@extends('user.layouts.main')

@section('css')
    @include('user.profile-settings._styles')
    @include('user.wallet.partials._recharge-styles')
@endsection

@section('content')
<div class="ps">
    <div class="container">

        <div class="ps-page-head">
            <div class="ps-page-head__icon"><i class="bx bx-wallet-alt"></i></div>
            <div>
                <h1 class="ps-page-head__title">Recharge</h1>
                <p class="ps-page-head__sub">Add funds to your wallet</p>
            </div>
        </div>

        <div class="ps-shell">
            @include('user.profile-settings._recharge_sidebar')

            <main class="ps-main">
            <div class="recharge-section">
                <h3 class="heading fw-bold mb-3">Credit - Debit Card</h3>

                @include('user.wallet.partials._recharge-limit')

                @if ($canRecharge ?? true)
                <form action="{{ route('user.wallet.recharge.card.process') }}" method="POST" id="rechargeForm">
                    @csrf

                    <div class="mb-4">
                        <div class="recharge-section__title">
                            <i class='bx bxs-credit-card'></i> Amount
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
                                    placeholder="1000" min="100" max="{{ min(50000, max(100, $maxRechargeAmount ?? 50000)) }}" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    <div class="recharge-method-note">
                        <div class="recharge-method-note__icon"><i class='bx bxs-credit-card-front'></i></div>
                        <div>
                            <div class="recharge-method-note__title">Pay securely via PayBy</div>
                            <p class="recharge-method-note__desc">Your card payment is processed through our secure payment partner.</p>
                        </div>
                    </div>

                    <button type="submit" class="btn-recharge" id="rechargeBtn">
                        <i class='bx bxs-bolt'></i> Recharge Now
                    </button>
                </form>
                @endif
            </div>

            @include('user.wallet.partials._recharge-history')
            </main>
        </div>
    </div>
</div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quickBtns = document.querySelectorAll('.quick-amount-btn');
            const amountInput = document.getElementById('amountInput');
            const form = document.getElementById('rechargeForm');
            const maxRecharge = @json((float) ($maxRechargeAmount ?? 50000));

            if (!form || !amountInput) return;

            quickBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const amount = Math.min(parseFloat(this.dataset.amount), maxRecharge);
                    quickBtns.forEach(b => b.classList.remove('active'));
                    if (amount >= 100) {
                        this.classList.add('active');
                        amountInput.value = amount;
                    }
                });
            });

            amountInput.addEventListener('input', function() {
                const val = parseFloat(this.value);
                quickBtns.forEach(b => {
                    b.classList.toggle('active', parseFloat(b.dataset.amount) === val);
                });
            });

            const firstAffordable = Array.from(quickBtns).find(b => parseFloat(b.dataset.amount) <= maxRecharge);
            if (firstAffordable) {
                firstAffordable.click();
            }

            form.addEventListener('submit', function(e) {
                const val = parseFloat(amountInput.value);
                if (val > maxRecharge) {
                    e.preventDefault();
                    alert('Maximum recharge allowed is ' + maxRecharge.toFixed(2) + ' AED.');
                    return;
                }
                const btn = document.getElementById('rechargeBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';
            });
        });
    </script>
@endpush
