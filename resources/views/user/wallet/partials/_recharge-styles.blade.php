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
</style>
