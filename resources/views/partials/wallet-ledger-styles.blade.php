<style>
    .pm-pill {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 6px;
        line-height: 1.35;
        white-space: nowrap;
    }
    .pm-bank   { background:#f3e5f5; color:#6a1b9a; }
    .pm-debit-booking { background:#ffebee; color:#b71c1c; }
    .pm-refund { background:#e3f2fd; color:#1565c0; }
    .pm-recharge { background:#e8f5e9; color:#2e7d32; }
    .pm-manual { background:#fff8e1; color:#f57f17; }
    .pm-unpaid-credit { background:#fff3e0; color:#e65100; }
    .pm-unpaid-settlement { background:#eceff1; color:#455a64; }
    .pm-system { background:#f3f4f6; color:#4b5563; }
    .pm-void {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    /* ── Wallet ledger table ───────────────────────────────── */
    .vs-ledger-table-wrap {
        margin: 0 -4px;
    }

    #wallet-ledger-table.vs-ledger-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    #wallet-ledger-table.vs-ledger-table thead th {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #8492a6;
        padding: 10px 12px;
        border-bottom: 2px solid #e8ecf2;
        white-space: nowrap;
        vertical-align: bottom;
    }

    #wallet-ledger-table.vs-ledger-table tbody td {
        padding: 11px 12px;
        font-size: 0.82rem;
        color: #1a2540;
        border-bottom: 1px solid #f0f3f8;
        vertical-align: middle;
    }

    #wallet-ledger-table.vs-ledger-table tbody tr:last-child td {
        border-bottom: none;
    }

    #wallet-ledger-table .vs-ledger-col-date { min-width: 96px; }
    #wallet-ledger-table .vs-ledger-col-type { width: 1%; white-space: nowrap; }
    #wallet-ledger-table .vs-ledger-col-reason { width: 1%; white-space: nowrap; }
    #wallet-ledger-table .vs-ledger-col-amount { white-space: nowrap; text-align: right; }
    #wallet-ledger-table .vs-ledger-col-balance { white-space: nowrap; text-align: right; font-size: 0.8rem; color: #4a5568; }
    #wallet-ledger-table .vs-ledger-col-desc { min-width: 180px; max-width: 280px; }
    #wallet-ledger-table .vs-ledger-col-attach { width: 1%; white-space: nowrap; text-align: center; }

    #wallet-ledger-table thead .vs-ledger-col-amount,
    #wallet-ledger-table thead .vs-ledger-col-balance { text-align: right; }

    /* Date */
    .vs-ledger-date {
        white-space: nowrap;
        line-height: 1.35;
    }
    .vs-ledger-date__day {
        display: block;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .vs-ledger-date__time {
        display: block;
        font-size: 0.72rem;
        color: #8492a6;
    }

    /* Type badges */
    .vs-ledger-type {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: 5px;
    }
    .vs-ledger-type-badge {
        display: inline-block;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 6px;
        line-height: 1.3;
        white-space: nowrap;
    }
    .vs-ledger-type-badge--credit {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .vs-ledger-type-badge--debit {
        background: #ffebee;
        color: #b71c1c;
    }
    .vs-ledger-type-badge.is-voided {
        background: #e5e7eb;
        color: #6b7280;
        text-decoration: line-through;
    }
    .vs-ledger-void-tag {
        display: inline-block;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        background: #dc2626;
        color: #fff;
        border: none;
        line-height: 1.2;
        white-space: nowrap;
        cursor: default;
        letter-spacing: .02em;
    }
    .vs-ledger-status-tag {
        display: inline-block;
        font-size: 0.66rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 999px;
        line-height: 1.2;
        white-space: nowrap;
        letter-spacing: .02em;
        text-transform: uppercase;
    }
    .vs-ledger-status-tag--pending {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .vs-ledger-status-tag--settled {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    /* Reason pills inside ledger table */
    #wallet-ledger-table .pm-pill {
        font-size: 0.7rem;
        padding: 3px 9px;
        border-radius: 6px;
    }
    #wallet-ledger-table .pm-pill--voided-reason {
        opacity: 1;
        text-decoration: line-through;
        filter: saturate(0.75);
    }

    /* Description */
    .vs-ledger-desc__text {
        font-size: 0.8rem;
        line-height: 1.45;
        color: #4a5568;
        word-break: break-word;
    }
    .vs-ledger-desc a {
        display: inline-block;
        margin-top: 3px;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--color-primary, #cd1b4f);
        text-decoration: none;
    }
    .vs-ledger-desc a:hover { text-decoration: underline; }
    .vs-ledger-desc .text-muted { font-size: 0.72rem; }

    /* Voided rows */
    .vs-ledger-row--voided .vs-ledger-date__day,
    .vs-ledger-row--voided .vs-ledger-col-balance,
    .vs-ledger-row--voided .vs-ledger-desc__text {
        color: #6b7280;
    }
    .vs-ledger-row--voided .vs-ledger-amount {
        text-decoration: line-through;
        color: #6b7280 !important;
    }

    .vs-ledger-actions { display: flex; flex-wrap: wrap; gap: 0.35rem; }
    .vs-ledger-actions .btn-ledger {
        font-size: 0.72rem;
        padding: 0.25rem 0.55rem;
        border-radius: 6px;
        border: 1px solid #d8dbe2;
        background: #fff;
        color: #4b4753;
        cursor: pointer;
        line-height: 1.2;
    }
    .vs-ledger-actions .btn-ledger:hover { border-color: var(--color-primary, #cd1b4f); color: var(--color-primary, #cd1b4f); }
    .vs-ledger-actions .btn-ledger--void { border-color: #fecaca; color: #b91c1c; }
    .vs-ledger-actions .btn-ledger--void:hover { background: #fef2f2; }

    .vs-ledger-attachment-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.28rem 0.55rem;
        border-radius: 6px;
        border: 1px solid #d8dbe2;
        background: #fff;
        color: var(--color-primary, #cd1b4f);
        text-decoration: none;
        white-space: nowrap;
    }
    .vs-ledger-attachment-btn:hover {
        border-color: var(--color-primary, #cd1b4f);
        background: rgba(205, 27, 79, 0.06);
        color: var(--color-primary, #cd1b4f);
    }
    .vs-ledger-attachment-empty {
        color: #c9d2df;
        font-size: 0.85rem;
    }

    .vs-wallet-form {
        border: 1px solid #ebecf0;
        border-radius: 10px;
        padding: 1rem 1.1rem;
        margin-bottom: 1.25rem;
        background: #fafbfc;
    }
    .vs-wallet-form__title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #18181b;
        margin: 0 0 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .vs-wallet-form__hint {
        font-size: 0.78rem;
        color: #6b6573;
        margin: 0 0 0.85rem;
    }
    .vs-wallet-form .field {
        width: 100%;
        border: 1px solid #d8dbe2;
        border-radius: 8px;
        padding: 0.45rem 0.65rem;
        font-size: 0.88rem;
    }
    .vs-wallet-form label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #6b6573;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 0.25rem;
        display: block;
    }

    .vs-ledger-filters {
        border: 1px solid #ebecf0;
        border-radius: 10px;
        padding: 1rem 1.1rem;
        margin-bottom: 1.25rem;
        background: #fff;
    }
    .vs-ledger-filters__title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #18181b;
        margin: 0 0 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .vs-ledger-filters__meta {
        font-size: 0.78rem;
        color: #6b6573;
        margin: 0.65rem 0 0;
    }
    .vs-ledger-filters .field,
    .vs-ledger-filters .ps-field__input {
        width: 100%;
        border: 1px solid #d8dbe2;
        border-radius: 8px;
        padding: 0.45rem 0.65rem;
        font-size: 0.88rem;
    }
    .vs-ledger-filters label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #6b6573;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 0.25rem;
        display: block;
    }
    .vs-ledger-filters__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .vs-ledger-filters__clear {
        font-size: 0.82rem;
        color: var(--color-primary, #cd1b4f);
        text-decoration: none;
        font-weight: 600;
    }
    .vs-ledger-filters__clear:hover { text-decoration: underline; }

    .vs-ledger-balance {
        font-size: 0.82rem;
        color: #6b6573;
        margin: 0 0 1rem;
    }
    .vs-ledger-balance strong { color: #18181b; }

    .vs-wallet-form__hint .wallet-balance-metrics {
        margin-top: 0.5rem;
    }

    @media (max-width: 900px) {
        #wallet-ledger-table .vs-ledger-col-desc {
            max-width: none;
        }
    }
</style>
