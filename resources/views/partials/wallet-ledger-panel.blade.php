@php
    $showManualForm = $showManualForm ?? false;
    $readOnly = $readOnly ?? true;
    $ledgerContext = $ledgerContext ?? 'admin';
    $filterApplyClass = $filterApplyClass ?? 'themeBtn';
    $referenceLink = $ledgerContext === 'user'
        ? fn ($entry) => $entry->userReferenceLink()
        : fn ($entry) => $entry->adminReferenceLink();
@endphp

@if ($showManualForm)
    <div class="vs-wallet-form">
        <div class="vs-wallet-form__title"><i class="bx bx-plus-circle"></i> Add manual transaction</div>
        <p class="vs-wallet-form__hint">
            Record an admin credit or debit (e.g. hotel payment taken from wallet offline). Use <strong>Edit</strong> or <strong>Void</strong> on any row below to correct mistakes — the wallet balance is recalculated automatically.
            @include('partials.wallet-balance-metrics', ['vendor' => $vendor, 'compact' => true])
        </p>
        <form action="{{ route('admin.vendors.wallet-transactions.store', $vendor) }}" method="POST"
            id="manual-wallet-form" class="row g-3 align-items-end" enctype="multipart/form-data">
            @csrf
            <div class="col-md-2">
                <label for="mw_type">Type</label>
                <select name="type" id="mw_type" class="field" required>
                    <option value="credit" {{ old('type', 'credit') === 'credit' ? 'selected' : '' }}>Credit</option>
                    <option value="debit" {{ old('type') === 'debit' ? 'selected' : '' }}>Debit</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="mw_amount">Amount (AED)</label>
                <input type="number" name="amount" id="mw_amount" class="field" step="0.01" min="0.01"
                    value="{{ old('amount') }}" required placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label for="mw_date">Date</label>
                <input type="date" name="transaction_date" id="mw_date" class="field"
                    value="{{ old('transaction_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="col-md-2">
                <label for="mw_time">Time</label>
                <input type="time" name="transaction_time" id="mw_time" class="field"
                    value="{{ old('transaction_time', now()->format('H:i')) }}">
            </div>
            <div class="col-md-4">
                <label for="mw_description">Description <span class="text-danger">*</span></label>
                <input type="text" name="description" id="mw_description" class="field" maxlength="500"
                    value="{{ old('description') }}" required>
            </div>
            <div class="col-md-4">
                <label for="mw_attachment">Attachment</label>
                @include('partials.file-upload-picker', [
                    'inputId' => 'mw_attachment',
                    'inputName' => 'attachment',
                    'previewId' => 'mw_attachment_preview',
                    'filenameId' => 'mw_attachment_filename',
                    'chooseLabel' => 'Choose file',
                    'btnClass' => 'themeBtn agency-logo-upload__btn',
                    'accept' => '.jpg,.jpeg,.png,.gif,.webp,.pdf',
                ])
            </div>
            <div class="col-12">
                <button type="submit" class="themeBtn" style="font-size:.85rem;">
                    <i class="bx bx-check"></i> Add to ledger
                </button>
            </div>
        </form>
    </div>
@endif

@if (($ledgerTotalCount ?? 0) > 0)
    <div class="vs-ledger-filters">
        <div class="vs-ledger-filters__title"><i class="bx bx-filter-alt"></i> Filter ledger</div>
        <form method="GET" action="{{ $filterFormAction }}" class="row g-3 align-items-end" id="ledger-filter-form">
            @if (!empty($filterHiddenInputs))
                @foreach ($filterHiddenInputs as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
            @endif
            <div class="col-md-3">
                <label for="ledger_category">Category</label>
                <select name="ledger_category" id="ledger_category" class="field">
                    <option value="">All categories</option>
                    @foreach (\App\Support\WalletLedgerDescription::ledgerFilterOptions() as $slug => $label)
                        <option value="{{ $slug }}" {{ ($ledgerFilters['category'] ?? '') === $slug ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="ledger_from">From date</label>
                <input type="date" name="ledger_from" id="ledger_from" class="field"
                    value="{{ $ledgerFilters['from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label for="ledger_till">Till date</label>
                <input type="date" name="ledger_till" id="ledger_till" class="field"
                    value="{{ $ledgerFilters['till'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <div class="vs-ledger-filters__actions">
                    <button type="submit" class="{{ $filterApplyClass }}" style="font-size:.85rem;">
                        <i class="bx bx-search"></i> Apply
                    </button>
                    @if (!empty($ledgerFilters['has_filters']))
                        <a href="{{ $clearFiltersUrl }}" class="vs-ledger-filters__clear">Clear</a>
                    @endif
                </div>
            </div>
        </form>
        @if (!empty($ledgerFilters['has_filters']))
            <p class="vs-ledger-filters__meta mb-0">
                Showing <strong>{{ $walletLedger->count() }}</strong> of <strong>{{ $ledgerTotalCount }}</strong> transactions
                @if (!empty($ledgerFilters['category']))
                    · Category: <strong>{{ \App\Support\WalletLedgerDescription::ledgerFilterLabel($ledgerFilters['category']) }}</strong>
                    @if (!in_array($ledgerFilters['category'], \App\Support\WalletLedgerDescription::ledgerFilterActiveSlugs(), true))
                        <span class="text-muted">(no transactions for this product yet)</span>
                    @endif
                @endif
                @if (!empty($ledgerFilters['from']) || !empty($ledgerFilters['till']))
                    · Date:
                    <strong>
                        {{ $ledgerFilters['from'] ? \Carbon\Carbon::parse($ledgerFilters['from'])->format('d M Y') : '…' }}
                        –
                        {{ $ledgerFilters['till'] ? \Carbon\Carbon::parse($ledgerFilters['till'])->format('d M Y') : '…' }}
                    </strong>
                @endif
            </p>
        @endif
    </div>
@endif

@if ($walletLedger->isNotEmpty())
    <div class="table-responsive vs-ledger-table-wrap">
        <table class="data-table vs-ledger-table" id="wallet-ledger-table">
            <thead>
                <tr>
                    <th class="vs-ledger-col-date">Date</th>
                    <th class="vs-ledger-col-type">Type</th>
                    <th class="vs-ledger-col-reason">Reason</th>
                    <th class="vs-ledger-col-amount">Amount</th>
                    <th class="vs-ledger-col-balance" title="Balance before transaction">Before</th>
                    <th class="vs-ledger-col-balance" title="Balance after transaction">After</th>
                    <th class="vs-ledger-col-desc">Description</th>
                    <th class="no-sort vs-ledger-col-attach">Attach</th>
                    @unless ($readOnly)
                        <th class="no-sort">Actions</th>
                    @endunless
                </tr>
            </thead>
            <tbody>
                @foreach ($walletLedger as $entry)
                    @php
                        $refLink = $referenceLink($entry);
                        $isVoided = $entry->isVoided();
                    @endphp
                    <tr class="{{ $isVoided ? 'vs-ledger-row--voided' : '' }}"
                        data-ledger-category="{{ \App\Support\WalletLedgerDescription::adminFilterCategory($entry) }}">
                        <td data-order="{{ $entry->created_at->timestamp }}" class="vs-ledger-date">
                            <span class="vs-ledger-date__day">{{ $entry->created_at->format('d M Y') }}</span>
                            <span class="vs-ledger-date__time">{{ $entry->created_at->format('h:i A') }}</span>
                        </td>
                        <td class="vs-ledger-type">
                            <span class="vs-ledger-type-badge vs-ledger-type-badge--{{ $entry->type }}{{ $isVoided ? ' is-voided' : '' }}">
                                {{ ucfirst($entry->type) }}
                            </span>
                            @if ($isVoided)
                                <span class="vs-ledger-void-tag"
                                    @if ($entry->voided_at) title="Voided {{ $entry->voided_at->format('d M Y') }}" @endif>
                                    Voided
                                </span>
                            @endif
                        </td>
                        <td class="vs-ledger-reason">
                            <span class="pm-pill {{ $entry->adminReasonClass($isVoided) }}{{ $isVoided ? ' pm-pill--voided-reason' : '' }}">
                                {{ $entry->adminReasonLabel($isVoided) }}
                            </span>
                        </td>
                        <td class="fw-bold vs-ledger-amount vs-ledger-col-amount {{ $isVoided ? 'text-muted' : ($entry->isCredit() ? 'text-success' : 'text-danger') }}">
                            {{ $entry->isCredit() ? '+' : '-' }}{!! formatPrice($entry->amount) !!}
                        </td>
                        <td class="vs-ledger-col-balance">{!! formatPrice($entry->balance_before) !!}</td>
                        <td class="fw-semibold vs-ledger-col-balance">{!! formatPrice($entry->balance_after) !!}</td>
                        <td class="vs-ledger-desc">
                            <div class="vs-ledger-desc__text">{{ $entry->description }}</div>
                            @if (!empty($refLink['label']))
                                @if (!empty($refLink['url']))
                                    <a href="{{ $refLink['url'] }}">{{ $refLink['label'] }}</a>
                                @else
                                    <span class="text-muted">{{ $refLink['label'] }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="vs-ledger-col-attach">
                            @if ($entry->hasAttachment())
                                <a href="{{ $entry->attachmentUrl() }}" class="vs-ledger-attachment-btn" target="_blank" rel="noopener" title="View attachment">
                                    <i class="bx bx-show"></i> View
                                </a>
                            @else
                                <span class="vs-ledger-attachment-empty">-</span>
                            @endif
                        </td>
                        @unless ($readOnly)
                            <td>
                                @if ($isVoided)
                                    <span class="small text-muted">-</span>
                                @else
                                    <div class="vs-ledger-actions">
                                        <button type="button" class="btn-ledger btn-edit-ledger"
                                            data-entry-id="{{ $entry->id }}"
                                            data-type="{{ $entry->type }}"
                                            data-amount="{{ $entry->amount }}"
                                            data-description="{{ e($entry->description) }}"
                                            data-date="{{ $entry->created_at->format('Y-m-d') }}"
                                            data-time="{{ $entry->created_at->format('H:i') }}"
                                            data-has-attachment="{{ $entry->hasAttachment() ? '1' : '0' }}"
                                            data-attachment-url="{{ $entry->attachmentUrl() ?? '' }}"
                                            data-update-url="{{ route('admin.vendors.wallet-transactions.update', [$vendor, $entry]) }}">
                                            <i class="bx bx-edit-alt"></i> Edit
                                        </button>
                                        <form action="{{ route('admin.vendors.wallet-transactions.void', [$vendor, $entry]) }}" method="POST"
                                            class="d-inline ledger-void-form"
                                            data-amount="{{ number_format((float) $entry->amount, 2) }}"
                                            data-type="{{ $entry->type }}">
                                            @csrf
                                            <button type="submit" class="btn-ledger btn-ledger--void">
                                                <i class="bx bx-block"></i> Void
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        @endunless
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif (($ledgerTotalCount ?? 0) > 0 && !empty($ledgerFilters['has_filters']))
    <div class="text-center py-5" style="color:#6b6573;">
        <i class="bx bx-filter-alt" style="font-size:40px; opacity:.35; display:block; margin-bottom:.5rem;"></i>
        <p class="mb-2">No transactions match your filters.</p>
        <a href="{{ $clearFiltersUrl }}" class="vs-ledger-filters__clear">Clear filters</a>
    </div>
@else
    <div class="text-center py-5" style="color:#6b6573;">
        <i class="bx bx-wallet" style="font-size:40px; opacity:.35; display:block; margin-bottom:.5rem;"></i>
        No wallet transactions yet.
    </div>
@endif
