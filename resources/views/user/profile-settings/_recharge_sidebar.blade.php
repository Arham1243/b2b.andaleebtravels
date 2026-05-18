<aside class="ps-nav">
    <nav class="ps-nav__menu">
        <a href="{{ route('user.wallet.recharge.card') }}"
           class="ps-nav__item {{ request()->routeIs('user.wallet.recharge.card') ? 'ps-nav__item--active' : '' }}">
            <span class="ps-nav__item-icon"><i class="bx bx-credit-card"></i></span>
            <span class="ps-nav__item-text">Credit / Debit Card</span>
            @if (request()->routeIs('user.wallet.recharge.card'))
                <i class="bx bx-chevron-right ps-nav__item-arrow"></i>
            @endif
        </a>

        <a href="{{ route('user.wallet.recharge.tabby') }}"
           class="ps-nav__item {{ request()->routeIs('user.wallet.recharge.tabby') ? 'ps-nav__item--active' : '' }}">
            <span class="ps-nav__item-icon"><i class="bx bx-calendar-check"></i></span>
            <span class="ps-nav__item-text">Tabby</span>
            @if (request()->routeIs('user.wallet.recharge.tabby'))
                <i class="bx bx-chevron-right ps-nav__item-arrow"></i>
            @endif
        </a>
    </nav>
</aside>
