@php
    $walletUser = $walletUser ?? ($vendor ?? null) ?? Auth::user();
    $walletAgency = $walletUser->walletAgency();
    $availableBalance = $walletAgency->availableBalanceAmount();
    $usedBalance = $walletAgency->usedBalanceAmount();
    $creditLimit = $walletAgency->creditLimitAmount();
    $compact = $compact ?? false;
@endphp

<div class="wallet-balance-metrics{{ $compact ? ' wallet-balance-metrics--compact' : '' }}">
    <span class="wallet-balance-metrics__item">
        <span class="wallet-balance-metrics__label">Available Balance</span>
        <strong class="wallet-balance-metrics__value{{ $availableBalance < 0 ? ' wallet-balance-metrics__value--negative' : '' }}">{!! formatPrice($availableBalance) !!}</strong>
    </span>
    <span class="wallet-balance-metrics__item">
        <span class="wallet-balance-metrics__label">Credit Limit</span>
        <strong class="wallet-balance-metrics__value">{!! formatPrice($creditLimit) !!}</strong>
    </span>
    <span class="wallet-balance-metrics__item">
        <span class="wallet-balance-metrics__label">Used Balance</span>
        <strong class="wallet-balance-metrics__value wallet-balance-metrics__value--used">{!! formatPrice($usedBalance) !!}</strong>
    </span>
</div>
