<div class="hotel-search-redesign flight-search-redesign" v-cloak>
    <div class="hotel-search-redesign__title">
        <i class='bx bx-plane'></i> Book Domestic and International Flights
    </div>
    <form method="GET" action="{{ route('user.flights.search') }}" @submit="onFlightSearchSubmit">
        <!-- ROW 1: From + To + Dates -->
        <div class="hs-row hs-row--top">
            <!-- FROM -->
            <div class="hs-field hs-field--destination" ref="fromWrapperRef">
                <div class="hs-field__inner" @click.stop="onFromBoxClick">
                    <div class="hs-field__label">FROM</div>
                    <div class="hs-field__value-row">
                        <input type="text" autocomplete="off" class="hs-field__input"
                            v-model="fromInput" @input="onFromInput" placeholder="City or airport"
                            ref="fromInputRef">
                        <input type="hidden" name="from" :value="selectedFrom ? selectedFrom.code : ''">
                        <i class='bx bx-plane-take-off hs-field__icon'></i>
                    </div>
                </div>

                <!-- From Dropdown -->
                <div class="options-dropdown-wrapper options-dropdown-wrapper--from"
                    :class="{
                        open: fromDropdownOpen,
                        scroll: (filteredFromAirports?.length || 0) > 9
                    }">
                    <!-- Loading State -->
                    <div class="options-dropdown" v-if="loadingAirports">
                        <div class="options-dropdown__body p-0">
                            <ul class="options-dropdown-list">
                                <li class="options-dropdown-list__item no-hover" v-for="n in 5"
                                    :key="'from-skel-' + n">
                                    <div class="skeleton"></div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Airports -->
                    <div class="options-dropdown"
                        v-if="!loadingAirports && (filteredFromAirports?.length || 0) > 0">
                        <div class="options-dropdown__header">
                            <span>Airports</span>
                        </div>
                        <div class="options-dropdown__body p-0">
                            <ul class="options-dropdown-list">
                                <li class="options-dropdown-list__item" v-for="airport in filteredFromAirports"
                                    :key="'from-' + airport.code"
                                    @click="selectFromAirport(airport)">

                                    <div class="icon">
                                        <i class='bx bx-map-pin'></i>
                                    </div>

                                    <div class="info">
                                        <div class="name">@{{ airport.code }} - @{{ airport.name }}</div>
                                        <span class="sub-text">@{{ airport.city }}, @{{ airport.country }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- No Matches -->
                    <div class="options-dropdown options-dropdown--norm"
                        v-if="!loadingAirports && !(filteredFromAirports?.length || 0)">
                        <div class="options-dropdown__header justify-content-center">
                            <span class="text-danger" style="font-weight: 500;">No Matches Found</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TO -->
            <div class="hs-field hs-field--destination" ref="toWrapperRef">
                <div class="hs-field__inner" @click.stop="onToBoxClick">
                    <div class="hs-field__label">TO</div>
                    <div class="hs-field__value-row">
                        <input type="text" autocomplete="off" class="hs-field__input"
                            v-model="toInput" @input="onToInput" placeholder="City or airport"
                            ref="toInputRef">
                        <input type="hidden" name="to" :value="selectedTo ? selectedTo.code : ''">
                        <i class='bx bx-plane-landing hs-field__icon'></i>
                    </div>
                </div>

                <!-- To Dropdown -->
                <div class="options-dropdown-wrapper options-dropdown-wrapper--from"
                    :class="{
                        open: toDropdownOpen,
                        scroll: (filteredToAirports?.length || 0) > 9
                    }">
                    <!-- Loading State -->
                    <div class="options-dropdown" v-if="loadingAirports">
                        <div class="options-dropdown__body p-0">
                            <ul class="options-dropdown-list">
                                <li class="options-dropdown-list__item no-hover" v-for="n in 5"
                                    :key="'to-skel-' + n">
                                    <div class="skeleton"></div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Airports -->
                    <div class="options-dropdown"
                        v-if="!loadingAirports && (filteredToAirports?.length || 0) > 0">
                        <div class="options-dropdown__header">
                            <span>Airports</span>
                        </div>
                        <div class="options-dropdown__body p-0">
                            <ul class="options-dropdown-list">
                                <li class="options-dropdown-list__item" v-for="airport in filteredToAirports"
                                    :key="'to-' + airport.code"
                                    @click="selectToAirport(airport)">

                                    <div class="icon">
                                        <i class='bx bx-map-pin'></i>
                                    </div>

                                    <div class="info">
                                        <div class="name">@{{ airport.code }} - @{{ airport.name }}</div>
                                        <span class="sub-text">@{{ airport.city }}, @{{ airport.country }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- No Matches -->
                    <div class="options-dropdown options-dropdown--norm"
                        v-if="!loadingAirports && !(filteredToAirports?.length || 0)">
                        <div class="options-dropdown__header justify-content-center">
                            <span class="text-danger" style="font-weight: 500;">No Matches Found</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DEPARTURE DATE -->
            <div class="hs-field hs-field--date" id="flight-departure-box">
                <div class="hs-field__inner">
                    <div class="hs-field__label"><i class='bx bx-calendar'></i> DEPARTURE <i class='bx bx-chevron-down'
                            style="font-size:11px"></i></div>
                    <div class="hs-date-display">
                        <span class="hs-date-display__day" id="flight-departure-dd">&mdash;</span>
                        <div class="hs-date-display__meta">
                            <span class="hs-date-display__month" id="flight-departure-mon">&nbsp;</span>
                            <span class="hs-date-display__weekday" id="flight-departure-day">&nbsp;</span>
                        </div>
                    </div>
                    <input readonly autocomplete="off" type="hidden" name="departure_date"
                        id="flight-departure-input">
                </div>
            </div>

            <!-- TRIP BADGE -->
            <div class="hs-trip-badge" :class="{ 'hs-trip-badge--round': hasReturnDate }">
                <span class="hs-trip-badge__count">@{{ tripBadgeTop }}</span>
                <span class="hs-trip-badge__label">@{{ tripBadgeBottom }}</span>
            </div>

            <!-- RETURN DATE -->
            <div class="hs-field hs-field--date" id="flight-return-box">
                <div class="hs-field__inner">
                    <div class="hs-field__label"><i class='bx bx-calendar'></i> RETURN <i
                            class='bx bx-chevron-down' style="font-size:11px"></i></div>
                    <div class="hs-date-display">
                        <span class="hs-date-display__day" id="flight-return-dd">&mdash;</span>
                        <div class="hs-date-display__meta">
                            <span class="hs-date-display__month" id="flight-return-mon">&nbsp;</span>
                            <span class="hs-date-display__weekday" id="flight-return-day">&nbsp;</span>
                        </div>
                    </div>
                    <input readonly autocomplete="off" type="hidden" name="return_date"
                        id="flight-return-input">
                </div>
            </div>
        </div>

        <!-- ROW 2: Travellers + Search -->
        <div class="hs-row hs-row--bottom">
            <!-- TRAVELLERS -->
            <div class="hs-field hs-field--rooms" ref="travellersRef">
                <div class="hs-field__inner" @click.stop="toggleTravellers">
                    <div class="hs-field__label">TRAVELLERS <i class='bx bx-chevron-down'
                            style="font-size:11px"></i></div>
                    <div class="hs-field__value">
                        <span class="hs-rooms-text">
                            <strong>@{{ adults }}</strong> Adult<template v-if="adults !== 1">s</template>
                            <template v-if="children > 0">
                                , <strong>@{{ children }}</strong> Child<template v-if="children !== 1">ren</template>
                            </template>
                            <template v-if="infants > 0">
                                , <strong>@{{ infants }}</strong> Infant<template v-if="infants !== 1">s</template>
                            </template>
                        </span>
                    </div>
                </div>

                <!-- Travellers Dropdown -->
                <div class="options-dropdown-wrapper options-dropdown-wrapper--pax"
                    :class="{ open: travellersOpen }">
                    <div class="options-dropdown options-dropdown--norm">
                        <div class="options-dropdown__body">
                            <input type="hidden" name="adults" :value="adults">
                            <input type="hidden" name="children" :value="children">
                            <input type="hidden" name="infants" :value="infants">

                            <ul class="paxs-list mt-0">
                                <!-- Adults -->
                                <li class="paxs-item">
                                    <div class="info">
                                        <div class="name">Adults</div>
                                        <span>12+ years</span>
                                    </div>
                                    <div class="quantity-counter">
                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="decrementAdults">
                                            <i class='bx bx-minus'></i>
                                        </button>
                                        <span
                                            class="quantity-counter__btn quantity-counter__btn--quantity">@{{ adults }}</span>
                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="incrementAdults">
                                            <i class='bx bx-plus'></i>
                                        </button>
                                    </div>
                                </li>

                                <!-- Children -->
                                <li class="paxs-item">
                                    <div class="info">
                                        <div class="name">Children</div>
                                        <span>2-11 years</span>
                                    </div>
                                    <div class="quantity-counter">
                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="decrementChildren">
                                            <i class='bx bx-minus'></i>
                                        </button>
                                        <span
                                            class="quantity-counter__btn quantity-counter__btn--quantity">@{{ children }}</span>
                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="incrementChildren">
                                            <i class='bx bx-plus'></i>
                                        </button>
                                    </div>
                                </li>

                                <!-- Infants -->
                                <li class="paxs-item">
                                    <div class="info">
                                        <div class="name">Infants</div>
                                        <span>Under 2</span>
                                    </div>
                                    <div class="quantity-counter">
                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="decrementInfants">
                                            <i class='bx bx-minus'></i>
                                        </button>
                                        <span
                                            class="quantity-counter__btn quantity-counter__btn--quantity">@{{ infants }}</span>
                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="incrementInfants">
                                            <i class='bx bx-plus'></i>
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEARCH BUTTON -->
            <div class="hs-field hs-field--btn">
                <button type="submit" class="hs-search-btn" :disabled="!isSearchEnabled || isSearching">
                    <template v-if="isSearching">
                        <i class='bx bx-loader-alt bx-spin'></i> Searching...
                    </template>
                    <template v-else>
                        Search Flights
                    </template>
                </button>
            </div>
        </div>
    </form>
</div>
