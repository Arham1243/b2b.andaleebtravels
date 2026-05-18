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
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <h3 class="heading fw-bold mb-0">Credit - Debit Card</h3>
                    <div class="current-balance">
                        <i class='bx bxs-wallet'></i>
                        Current Balance: {!! formatPrice(Auth::user()->main_balance) !!}
                    </div>
                </div>

                <form action="{{ route('user.wallet.recharge.card.process') }}" method="POST" enctype="multipart/form-data" id="rechargeForm">
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
                                    placeholder="1000" min="100" max="50000" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Payment proof (screenshot) *</label>
                        <input type="file" name="proof" id="cardProofInput" class="form-control form-control-sm" style="max-width: 320px;"
                               accept="image/jpeg,image/png,image/jpg,image/webp" required>
                        <small class="text-muted d-block mt-1">Upload a receipt or proof before continuing to PayBy. JPG, PNG or WebP — max 5 MB</small>
                        @error('proof')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
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
            const proofInput = document.getElementById('cardProofInput');
            const form = document.getElementById('rechargeForm');

            quickBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    quickBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    amountInput.value = this.dataset.amount;
                });
            });

            amountInput.addEventListener('input', function() {
                const val = parseInt(this.value);
                quickBtns.forEach(b => {
                    b.classList.toggle('active', parseInt(b.dataset.amount) === val);
                });
            });

            if (quickBtns.length > 0) {
                quickBtns[0].click();
            }

            form.addEventListener('submit', function(e) {
                if (!proofInput || !proofInput.files || proofInput.files.length === 0) {
                    e.preventDefault();
                    proofInput?.focus();
                    proofInput?.reportValidity();
                    return;
                }
                const btn = document.getElementById('rechargeBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';
            });
        });
    </script>
@endpush
