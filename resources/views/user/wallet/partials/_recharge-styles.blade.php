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
        color: var(--color-primary);
    }

    .quick-amounts {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .quick-amounts__divider {
        border-left: 3px solid var(--color-primary);
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
        border-color: var(--color-primary);
        color: var(--color-primary);
    }

    .quick-amount-btn.active {
        background: var(--color-primary);
        border-color: var(--color-primary);
        color: #fff;
    }

    .quick-amounts__or {
        font-weight: 600;
        color: var(--color-primary);
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
        border-color: var(--color-primary);
    }

    .custom-amount-input::placeholder {
        color: #999;
    }

    .recharge-method-note {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        border: 2px solid #dee2e6;
        border-radius: 10px;
        padding: 18px 20px;
        margin-top: 8px;
        background: var(--color-primary-light, #fdf1f4);
        border-color: rgba(205, 27, 79, 0.25);
    }

    .recharge-method-note__icon {
        font-size: 28px;
        color: var(--color-primary);
        flex-shrink: 0;
    }

    .recharge-method-note__title {
        font-size: 16px;
        font-weight: 600;
        color: #1a1a2e;
        margin-bottom: 4px;
    }

    .recharge-method-note__desc {
        font-size: 13px;
        color: #666;
        margin: 0;
    }

    .btn-recharge {
        background: var(--color-primary);
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

    .btn-recharge:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .current-balance {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--color-primary-light);
        padding: 8px 18px;
        border-radius: 8px;
        font-weight: 600;
        color: var(--color-primary);
        font-size: 15px;
    }

    .current-balance i {
        font-size: 20px;
    }

    .wallet-balance-summary {
        background: var(--color-primary-light, #fdf1f4);
        border: 1px solid rgba(205, 27, 79, 0.15);
        border-radius: 10px;
        padding: 10px 16px;
        min-width: 240px;
    }
    .wallet-balance-summary__main {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        color: var(--color-primary);
        font-size: 15px;
    }
    .wallet-balance-summary__main i { font-size: 20px; }
    .wallet-balance-summary__breakdown {
        margin-top: 4px;
        font-size: 12px;
        color: #666;
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 0.35rem;
        align-items: center;
    }
    .wallet-balance-summary__sep { color: #ccc; }
    .wallet-balance-summary__used { color: #b45309; font-weight: 600; }

    .recharge-limit-alert {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 14px 16px;
        border-radius: 10px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        margin-bottom: 20px;
    }
    .recharge-limit-alert i { font-size: 22px; flex-shrink: 0; margin-top: 2px; }
    .recharge-limit-alert p { margin: 4px 0 0; font-size: 13px; color: #7f1d1d; }
    .recharge-limit-note {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 8px;
        background: #fff8e1;
        border: 1px solid #ffe082;
        color: #7a5c00;
        font-size: 13px;
        margin-bottom: 16px;
    }
    .recharge-limit-note i { font-size: 18px; }

    .wallet-file-upload__btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #fff;
        cursor: pointer;
        background: var(--color-primary);
        margin: 0;
        transition: background 0.2s;
    }
    .wallet-file-upload__btn:hover {
        background: var(--color-primary-dark, #a91540);
        color: #fff;
    }
    .recharge-section .agency-logo-upload__preview {
        width: auto;
        height: auto;
        max-height: 80px;
        max-width: 160px;
        min-height: 48px;
        object-fit: contain;
        border-radius: 8px;
        border: 1px solid #e4e9f0;
        background: #f8f9fa;
        padding: 6px;
        margin-bottom: 10px;
    }
    .recharge-section .agency-logo-upload__name {
        font-size: 13px;
        color: #8492a6;
    }

    /* Transaction history actions: icon + label inline */
    .custom-table .wallet-action-btn {
        display: inline-flex !important;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        white-space: nowrap;
        vertical-align: middle;
    }

    .custom-table .wallet-action-btn i {
        font-size: 1rem;
        line-height: 1;
        vertical-align: middle;
    }
</style>
