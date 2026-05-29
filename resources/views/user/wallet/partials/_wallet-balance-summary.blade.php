@php
    $walletUser = $walletUser ?? Auth::user();
    $prepaidBalance = (float) ($walletUser->main_balance ?? 0);
    $creditLimit = $walletUser->creditLimitAmount();
    $creditUsed = $walletUser->creditUsedAmount();
    $creditAvailable = $walletUser->creditAvailableAmount();
    $totalSpendable = $walletUser->totalSpendableBalance();
@endphp
<div class="wallet-balance-summary">
    <div class="wallet-balance-summary__main">
        <i class="bx bxs-wallet"></i>
        <span>Available to spend: <strong>{!! formatPrice($totalSpendable) !!}</strong></span>
    </div>
    <div class="wallet-balance-summary__breakdown">
        <span>Prepaid: {!! formatPrice($prepaidBalance) !!}</span>
        @if ($creditLimit > 0)
            <span class="wallet-balance-summary__sep">·</span>
            <span>Credit: {!! formatPrice($creditAvailable) !!} of {!! formatPrice($creditLimit) !!}</span>
            @if ($creditUsed > 0)
                <span class="wallet-balance-summary__sep">·</span>
                <span class="wallet-balance-summary__used">Used: {!! formatPrice($creditUsed) !!}</span>
            @endif
        @endif
    </div>
</div>
