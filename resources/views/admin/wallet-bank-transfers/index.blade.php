@extends('admin.layouts.main')
@section('content')
    <div class="col-md-12">
        <div class="dashboard-content">
            {{ Breadcrumbs::render('admin.wallet.bank-transfers.index') }}
            <div class="table-container universal-table">
                <div class="custom-sec">
                    <div class="custom-sec__header mt-4">
                        <div class="section-content">
                            <h3 class="heading">{{ $title }}</h3>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction #</th>
                                    <th>Vendor</th>
                                    <th>Email</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Proof</th>
                                    <th style="min-width: 220px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recharges as $r)
                                    <tr>
                                        <td>{{ $r->created_at->format('d M Y, H:i') }}</td>
                                        <td><span class="fw-semibold">{{ $r->transaction_number }}</span></td>
                                        <td>{{ $r->vendor->name ?? '—' }}</td>
                                        <td>{{ $r->vendor->email ?? '—' }}</td>
                                        <td>{!! formatPrice($r->amount) !!}</td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $r->status === 'paid' ? 'success' : ($r->status === 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($r->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($r->proof_image_path)
                                                <a href="{{ walletBankProofUrl($r->proof_image_path) }}" target="_blank" rel="noopener" class="themeBtn" style="padding: 4px 10px; font-size: 12px;">
                                                    View
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($r->status === 'pending')
                                                @php
                                                    $confirmCreditMsg = 'Credit '.number_format((float) $r->amount, 2).' AED to '.($r->vendor->name ?? 'this vendor').'? Transaction #'.$r->transaction_number;
                                                @endphp
                                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                                    <form action="{{ route('admin.wallet.bank-transfers.confirm', $r) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="themeBtn"
                                                                style="background: #15803d; border: none; padding: 6px 12px; font-size: 12px;"
                                                                onclick="return confirm(@json($confirmCreditMsg));">
                                                            Confirm &amp; credit
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            class="themeBtn wallet-bank-open-reject"
                                                            style="background: #b91c1c; border: none; padding: 6px 12px; font-size: 12px;"
                                                            data-reject-url="{{ route('admin.wallet.bank-transfers.reject', $r) }}"
                                                            data-vendor="{{ e($r->vendor->name ?? 'Vendor') }}"
                                                            data-email="{{ e($r->vendor->email ?? '—') }}"
                                                            data-amount="{{ number_format((float) $r->amount, 2) }}"
                                                            data-txn="{{ $r->transaction_number }}">
                                                        Reject
                                                    </button>
                                                </div>
                                            @elseif ($r->status === 'paid' && $r->admin_confirmed_at)
                                                <small class="text-muted">
                                                    Confirmed {{ $r->admin_confirmed_at->format('d M Y H:i') }}
                                                    @if ($r->confirmedByAdmin)
                                                        <br>{{ $r->confirmedByAdmin->name ?? ('Admin #' . $r->confirmed_by_b2b_admin_id) }}
                                                    @endif
                                                </small>
                                            @elseif ($r->status === 'failed')
                                                <small class="text-danger">{{ \Illuminate\Support\Str::limit((string) ($r->failure_reason ?? ''), 80) }}</small>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($recharges->hasPages())
                        <div class="d-flex justify-content-center mt-3 pb-3">
                            {{ $recharges->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Reject modal --}}
    <div class="modal fade" id="walletBankRejectModal" tabindex="-1" aria-labelledby="walletBankRejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="walletBankRejectForm" method="POST" action="#">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="walletBankRejectModalLabel">Reject submission</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="walletBankRejectSummary" class="mb-3 small text-muted"></p>
                        <label for="walletBankRejectReason" class="form-label">Rejection reason <span class="text-danger">*</span></label>
                        <textarea id="walletBankRejectReason" name="reason" class="form-control form-control-sm" rows="4" maxlength="500" required placeholder="Explain why this submission is rejected"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="themeBtn" style="background: #b91c1c; border: none;" id="walletBankRejectSubmit">
                            Yes, reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var rejectModalEl = document.getElementById('walletBankRejectModal');
            var rejectForm = document.getElementById('walletBankRejectForm');
            var rejectSummary = document.getElementById('walletBankRejectSummary');
            var rejectReason = document.getElementById('walletBankRejectReason');

            function showModal(el) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(el).show();
                    return;
                }
                if (typeof window.jQuery !== 'undefined' && window.jQuery.fn.modal) {
                    window.jQuery(el).modal('show');
                }
            }

            document.querySelectorAll('.wallet-bank-open-reject').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    rejectForm.action = btn.getAttribute('data-reject-url');
                    rejectReason.value = '';
                    rejectSummary.textContent =
                        'Reject bank transfer for ' + btn.getAttribute('data-vendor') +
                        ' (' + btn.getAttribute('data-email') + '), ' + btn.getAttribute('data-amount') +
                        ' AED, #' + btn.getAttribute('data-txn') + '.';
                    showModal(rejectModalEl);
                });
            });

            document.getElementById('walletBankRejectSubmit').addEventListener('click', function () {
                var reason = (rejectReason.value || '').trim();
                if (!reason.length) {
                    rejectReason.focus();
                    rejectReason.reportValidity();
                    return;
                }
                if (!confirm('Reject this submission? The vendor will see the failure status.')) {
                    return;
                }
                rejectForm.submit();
            });
        });
    </script>
@endpush
