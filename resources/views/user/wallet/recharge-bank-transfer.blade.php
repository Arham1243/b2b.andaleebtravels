@extends('user.layouts.main')

@section('css')
    @include('user.profile-settings._styles')
    @include('user.wallet.partials._recharge-styles')
    <style>
        .bank-info-box {
            background: #f8faff;
            border: 1px solid #e4e9f0;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: .84rem;
            color: #1a2540;
            line-height: 1.45;
            margin-bottom: 20px;
        }
        .bank-info-box--empty {
            color: #8492a6;
            font-style: italic;
        }

        /* Less gap between section rule and bank info box */
        .recharge-section__title--bank-details {
            margin-bottom: 8px;
            padding-bottom: 8px;
        }
    </style>
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
                    <h3 class="heading fw-bold mb-0">Direct Bank Transfer</h3>
                    @include('user.wallet.partials._wallet-balance-summary')
                </div>

                @include('user.wallet.partials._recharge-limit')

                <div class="recharge-section__title recharge-section__title--bank-details">
                    <i class='bx bxs-bank'></i> Bank details
                </div>
                @if ($bankInstructions !== '')
                    @php
                        $bankLines = preg_replace('/\R+/u', "\n", trim($bankInstructions));
                    @endphp
                    <div class="bank-info-box">{!! nl2br(e($bankLines)) !!}</div>
                @else
                    <div class="bank-info-box bank-info-box--empty">
                        Bank transfer instructions are not configured yet. Please contact support or try another recharge method.
                    </div>
                @endif

                @if ($canRecharge ?? true)
                <form action="{{ route('user.wallet.recharge.bank-transfer.submit') }}" method="POST" enctype="multipart/form-data" id="bankTransferForm">
                    @csrf

                    <div class="mb-4">
                        <div class="recharge-section__title">
                            <i class='bx bx-money'></i> Amount you transferred
                        </div>
                        <div class="quick-amounts">
                            <div class="quick-amounts__divider"></div>
                            <button type="button" class="quick-amount-btn" data-amount="1000">1000</button>
                            <button type="button" class="quick-amount-btn" data-amount="2500">2500</button>
                            <button type="button" class="quick-amount-btn" data-amount="5000">5000</button>
                            <button type="button" class="quick-amount-btn" data-amount="10000">10000</button>
                            <span class="quick-amounts__or">OR</span>
                            <div>
                                <label class="form-label mb-1" style="font-size: 12px; color: #666;">Enter Amount *</label>
                                <input type="number" name="amount" id="amountInput" class="custom-amount-input"
                                    value="{{ old('amount') }}"
                                    placeholder="1000" min="100" max="{{ min(50000, max(100, $maxRechargeAmount ?? 50000)) }}" step="0.01" required>
                            </div>
                        </div>
                        @error('amount')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Payment proof (screenshot) *</label>
                        <input type="file" name="proof" class="form-control form-control-sm" style="max-width: 320px;"
                               accept="image/jpeg,image/png,image/jpg,image/webp" required>
                        <small class="text-muted d-block mt-1">JPG, PNG or WebP - max 5 MB</small>
                        @error('proof')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn-recharge" id="submitBtn">
                        <i class='bx bx-upload'></i> Submit for verification
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
            const form = document.getElementById('bankTransferForm');
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

            if (!amountInput.value) {
                const firstAffordable = Array.from(quickBtns).find(b => parseFloat(b.dataset.amount) <= maxRecharge);
                if (firstAffordable) {
                    firstAffordable.click();
                }
            }

            form.addEventListener('submit', function(e) {
                const val = parseFloat(amountInput.value);
                if (val > maxRecharge) {
                    e.preventDefault();
                    alert('Maximum recharge allowed is ' + maxRecharge.toFixed(2) + ' AED.');
                    return;
                }
                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Submitting...';
            });
        });
    </script>
@endpush
