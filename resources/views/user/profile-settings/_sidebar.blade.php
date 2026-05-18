<aside class="ps-nav">
    <nav class="ps-nav__menu">

        <a href="{{ route('user.profile.personalInfo') }}"
           class="ps-nav__item {{ request()->routeIs('user.profile.personalInfo') ? 'ps-nav__item--active' : '' }}">
            <span class="ps-nav__item-icon"><i class="bx bxs-id-card"></i></span>
            <span class="ps-nav__item-text">Personal Info</span>
            @if(request()->routeIs('user.profile.personalInfo'))
                <i class="bx bx-chevron-right ps-nav__item-arrow"></i>
            @endif
        </a>

        <a href="{{ route('user.profile.changePassword') }}"
           class="ps-nav__item {{ request()->routeIs('user.profile.changePassword') ? 'ps-nav__item--active' : '' }}">
            <span class="ps-nav__item-icon"><i class="bx bxs-lock-alt"></i></span>
            <span class="ps-nav__item-text">Change Password</span>
            @if(request()->routeIs('user.profile.changePassword'))
                <i class="bx bx-chevron-right ps-nav__item-arrow"></i>
            @endif
        </a>

        <a href="{{ route('user.sub-agents.index') }}"
           class="ps-nav__item {{ request()->routeIs('user.sub-agents.*') ? 'ps-nav__item--active' : '' }}">
            <span class="ps-nav__item-icon"><i class="bx bx-user-plus"></i></span>
            <span class="ps-nav__item-text">Sub Agents</span>
            @if(request()->routeIs('user.sub-agents.*'))
                <i class="bx bx-chevron-right ps-nav__item-arrow"></i>
            @endif
        </a>

    </nav>
</aside>
