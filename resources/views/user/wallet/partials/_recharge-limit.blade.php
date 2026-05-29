@if (!($canRecharge ?? true))
    <div class="recharge-limit-alert">
        <i class="bx bx-info-circle"></i>
        <div>
            <strong>Recharge unavailable</strong>
            <p>{{ $rechargeBlockedMessage ?? 'You cannot add more funds to your wallet at this time.' }}</p>
        </div>
    </div>
@elseif (($maxRechargeAmount ?? 50000) < 50000 && ($walletUser ?? null)?->hasCreditLimit())
    <div class="recharge-limit-note">
        <i class="bx bx-wallet"></i>
        Prepaid wallet limit: {!! formatPrice($walletUser->creditLimitAmount()) !!}.
        You can add up to <strong>{!! formatPrice($maxRechargeAmount) !!}</strong> more
        @if ($walletUser->creditUsedAmount() > 0)
            (includes paying down {!! formatPrice($walletUser->creditUsedAmount()) !!} credit used).
        @else
            to your prepaid balance.
        @endif
    </div>
@endif
