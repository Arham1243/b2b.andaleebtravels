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
                    <p class="text-muted px-3 mb-3" style="font-size: 0.85rem;">
                        Pending submissions appear first. Confirm after you verify the deposit in your bank account; the agent wallet is credited via the same ledger as card and Tabby recharges.
                    </p>
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
                                @forelse ($recharges as $r)
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
                                                <a href="{{ asset('storage/' . $r->proof_image_path) }}" target="_blank" rel="noopener" class="themeBtn" style="padding: 4px 10px; font-size: 12px;">
                                                    View
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($r->status === 'pending')
                                                <div class="d-flex flex-column gap-2">
                                                    <form action="{{ route('admin.wallet.bank-transfers.confirm', $r) }}" method="POST"
                                                          onsubmit="return confirm('Credit {{ number_format((float) $r->amount, 2) }} AED to {{ $r->vendor->name ?? 'this vendor' }}?');">
                                                        @csrf
                                                        <input type="text" name="note" class="field mb-1" placeholder="Optional note" style="font-size: 12px; padding: 6px 8px;">
                                                        <button type="submit" class="themeBtn" style="background: #15803d; border: none; padding: 6px 12px; font-size: 12px;">
                                                            Confirm &amp; credit wallet
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.wallet.bank-transfers.reject', $r) }}" method="POST"
                                                          onsubmit="return confirm('Reject this submission?');">
                                                        @csrf
                                                        <input type="text" name="reason" class="field mb-1" placeholder="Rejection reason *" required style="font-size: 12px; padding: 6px 8px;">
                                                        <button type="submit" class="themeBtn" style="background: #b91c1c; border: none; padding: 6px 12px; font-size: 12px;">
                                                            Reject
                                                        </button>
                                                    </form>
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
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No bank transfer requests yet.</td>
                                    </tr>
                                @endforelse
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
@endsection
