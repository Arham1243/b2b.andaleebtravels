@php
    $walletUser = $walletUser ?? ($vendor ?? null) ?? Auth::user();
    $availableBalance = $walletUser->totalSpendableBalance();
    $usedBalance = $walletUser->usedBalanceAmount();
    $compact = $compact ?? false;
@endphp

<div class="wallet-balance-metrics{{ $compact ? ' wallet-balance-metrics--compact' : '' }}">
    <span class="wallet-balance-metrics__item">
        <span class="wallet-balance-metrics__label">Available Balance</span>
        <strong class="wallet-balance-metrics__value">{!! formatPrice($availableBalance) !!}</strong>
    </span>
    <span class="wallet-balance-metrics__item">
        <span class="wallet-balance-metrics__label">Used Balance</span>
        <strong class="wallet-balance-metrics__value wallet-balance-metrics__value--used">{!! formatPrice($usedBalance) !!}</strong>
    </span>
</div>
