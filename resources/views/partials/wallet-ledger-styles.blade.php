<style>
    .pm-pill {
        display: inline-block;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .pm-bank   { background:#f3e5f5; color:#6a1b9a; }
    .pm-debit-booking { background:#ffebee; color:#b71c1c; }
    .pm-refund { background:#e3f2fd; color:#1565c0; }
    .pm-recharge { background:#e8f5e9; color:#2e7d32; }
    .pm-manual { background:#fff8e1; color:#f57f17; }
    .pm-system { background:#f3f4f6; color:#4b5563; }
    .pm-void {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
        font-weight: 700;
        letter-spacing: .06em;
    }
    .badge-voided {
        background: #dc2626 !important;
        color: #fff !important;
        font-weight: 700;
        letter-spacing: .04em;
        font-size: 0.68rem;
    }

    .vs-ledger-row--voided td { opacity: .72; }
    .vs-ledger-row--voided .vs-ledger-amount { text-decoration: line-through; }
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
        color: #9ca3af;
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
</style>
