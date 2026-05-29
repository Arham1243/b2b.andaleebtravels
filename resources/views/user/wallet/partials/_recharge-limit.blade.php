@if (!($canRecharge ?? true))
    <div class="recharge-limit-alert">
        <i class="bx bx-info-circle"></i>
        <div>
            <strong>Recharge unavailable</strong>
            <p>{{ $rechargeBlockedMessage ?? 'You have reached your wallet recharge limit. To add more funds, please contact your system administrator.' }}</p>
        </div>
    </div>
@elseif (($maxRechargeAmount ?? 50000) < 50000 && ($maxRechargeAmount ?? 0) >= 100 && ($walletUser ?? null)?->hasCreditLimit())
    <div class="recharge-limit-note">
        <i class="bx bx-wallet"></i>
        You can add up to <strong>{!! formatPrice($maxRechargeAmount) !!}</strong> more to your wallet.
    </div>
@endif
