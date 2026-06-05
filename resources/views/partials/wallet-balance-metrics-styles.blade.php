<style>
    .wallet-balance-metrics {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem 1.5rem;
        align-items: center;
    }
    .wallet-balance-metrics--compact {
        gap: 0.75rem 1.25rem;
        font-size: 0.88rem;
    }
    .wallet-balance-metrics__item {
        display: inline-flex;
        align-items: baseline;
        gap: 0.4rem;
    }
    .wallet-balance-metrics__label {
        color: #6b6573;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .wallet-balance-metrics--compact .wallet-balance-metrics__label {
        font-size: 0.72rem;
    }
    .wallet-balance-metrics__value {
        color: var(--color-primary, #cd1b4f);
        font-size: 1rem;
    }
    .wallet-balance-metrics__value--used {
        color: #b45309;
    }
    .wallet-balance-metrics__value--negative {
        color: #dc2626;
    }
    .wallet-balance-metrics--compact .wallet-balance-metrics__value {
        font-size: 0.92rem;
    }
</style>
