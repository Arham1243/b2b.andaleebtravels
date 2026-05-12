<div class="hotel-search-redesign flight-search-redesign fs-pro-enterprise" v-cloak>
    <form method="GET" action="{{ route('user.flights.search') }}" @submit="onFlightSearchSubmit" class="fs-pro-layout">
        <input type="hidden" name="trip_type" :value="tripType">

        <div class="fs-pro-layout__main">
            <div class="fs-pro-card">

                <header class="fs-pro-card__head">
                    <div class="fs-pro-card__title-wrap">
                        <h2 class="fs-pro-card__title"><i class='bx bx-search-alt'></i> Search Flights</h2>
                        <p class="fs-pro-card__subtitle">Domestic & International · Live inventory</p>
                    </div>
                    <div class="fs-pro-specials">
                        <a href="#" class="fs-pro-special-chip" @click.prevent>
                            <span class="fs-pro-special-chip__icon fs-pro-special-chip__icon--a2a"><i class='bx bxs-plane'></i></span>
                            <span class="fs-pro-special-chip__text">A2A Special Fare</span>
                            <span class="fs-pro-badge-new">NEW</span>
                        </a>
                        <a href="#" class="fs-pro-special-chip" @click.prevent>
                            <span class="fs-pro-special-chip__icon fs-pro-special-chip__icon--akbar"><i class='bx bxs-plane-alt'></i></span>
                            <span class="fs-pro-special-chip__text">Akbar Special Fare</span>
                            <span class="fs-pro-badge-new fs-pro-badge-new--blue">NEW</span>
                        </a>
                    </div>
                </header>

                <div class="fs-trip-types fs-pro-trip-types">
                    <button type="button" class="fs-trip-types__item" :class="{ active: tripType === 'one_way' }"
                        @click="setTripType('one_way')">
                        <span class="fs-trip-types__dot"></span> One Way
                    </button>
                    <button type="button" class="fs-trip-types__item" :class="{ active: tripType === 'round_trip' }"
                        @click="setTripType('round_trip')">
                        <span class="fs-trip-types__dot"></span> Round Trip
                    </button>
                    <button type="button" class="fs-trip-types__item" :class="{ active: tripType === 'multi_city' }"
                        @click="setTripType('multi_city')">
                        <span class="fs-trip-types__dot"></span> Multi City
                    </button>
                </div>

                <input type="hidden" name="direct_flight" :value="directFlight ? 1 : 0">
                <input type="hidden" name="nearby_airports" :value="nearbyAirports ? 1 : 0">
                <input type="hidden" name="student_fare" :value="studentFare ? 1 : 0">

                <template v-if="tripType !== 'multi_city'">
                    <div class="fs-pro-route-sheet">
                        <div class="fs-pro-route-pair">
                            <div class="fs-pro-route-field hs-field hs-field--destination fs-pro-route-field--from"
                                ref="fromWrapperRef">
                                <div class="fs-pro-route-field__shell">
                                    <div class="fs-pro-route-field__inner hs-field__inner" @click.stop="onFromBoxClick">
                                        <span class="fs-pro-route-field__label hs-field__label">FROM</span>
                                        <div v-if="selectedFrom && !fromDropdownOpen" class="fs-pro-route-chosen">
                                            <strong class="fs-pro-route-chosen__city">@{{ selectedFrom.city }}</strong>
                                            <div class="fs-pro-route-chosen__airport"><span class="mono">[<span>@{{ selectedFrom.code }}</span>]</span> @{{ selectedFrom.name }}</div>
                                        </div>
                                        <div class="hs-field__value-row" v-show="!selectedFrom || fromDropdownOpen">
                                            <input type="text" autocomplete="off"
                                                class="hs-field__input fs-pro-route-input"
                                                v-model="fromInput" @input="onFromInput"
                                                placeholder="City or airport" ref="fromInputRef">
                                            <i class='bx bx-current-location fs-pro-route-inline-icon'></i>
                                        </div>
                                        <input type="hidden" name="from" :value="selectedFrom ? selectedFrom.code : ''">
                                    </div>
                                </div>

                                <div class="options-dropdown-wrapper options-dropdown-wrapper--from"
                                    :class="{ open: fromDropdownOpen, scroll: (filteredFromAirports?.length || 0) > 9 }">
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

                                    <div class="options-dropdown"
                                        v-if="!loadingAirports && (filteredFromAirports?.length || 0) > 0">
                                        <div class="options-dropdown__header">
                                            <span>Airports</span>
                                        </div>
                                        <div class="options-dropdown__body p-0">
                                            <ul class="options-dropdown-list">
                                                <li class="options-dropdown-list__item"
                                                    v-for="airport in filteredFromAirports"
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

                                    <div class="options-dropdown options-dropdown--norm"
                                        v-if="!loadingAirports && !(filteredFromAirports?.length || 0)">
                                        <div class="options-dropdown__header justify-content-center">
                                            <span class="text-danger" style="font-weight: 500;">No Matches Found</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="fs-pro-swap-wrap">
                                <button type="button" class="fs-pro-swap-btn" title="Swap cities"
                                    @click.prevent="swapRoutes" aria-label="Swap origin and destination">
                                    <i class='bx bx-transfer-alt'></i>
                                </button>
                            </div>

                            <div class="fs-pro-route-field hs-field hs-field--destination fs-pro-route-field--to"
                                ref="toWrapperRef">
                                <div class="fs-pro-route-field__shell">
                                    <div class="fs-pro-route-field__inner hs-field__inner" @click.stop="onToBoxClick">
                                        <span class="fs-pro-route-field__label hs-field__label">TO</span>
                                        <div v-if="selectedTo && !toDropdownOpen" class="fs-pro-route-chosen">
                                            <strong class="fs-pro-route-chosen__city">@{{ selectedTo.city }}</strong>
                                            <div class="fs-pro-route-chosen__airport"><span class="mono">[<span>@{{ selectedTo.code }}</span>]</span> @{{ selectedTo.name }}</div>
                                        </div>
                                        <div class="hs-field__value-row" v-show="!selectedTo || toDropdownOpen">
                                            <input type="text" autocomplete="off"
                                                class="hs-field__input fs-pro-route-input"
                                                v-model="toInput" @input="onToInput"
                                                placeholder="City or airport" ref="toInputRef">
                                            <i class='bx bx-map-pin fs-pro-route-inline-icon'></i>
                                        </div>
                                        <input type="hidden" name="to" :value="selectedTo ? selectedTo.code : ''">
                                    </div>
                                </div>

                                <div class="options-dropdown-wrapper options-dropdown-wrapper--from"
                                    :class="{ open: toDropdownOpen, scroll: (filteredToAirports?.length || 0) > 9 }">
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

                                    <div class="options-dropdown"
                                        v-if="!loadingAirports && (filteredToAirports?.length || 0) > 0">
                                        <div class="options-dropdown__header">
                                            <span>Airports</span>
                                        </div>
                                        <div class="options-dropdown__body p-0">
                                            <ul class="options-dropdown-list">
                                                <li class="options-dropdown-list__item"
                                                    v-for="airport in filteredToAirports"
                                                    :key="'to-' + airport.code" @click="selectToAirport(airport)">
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

                                    <div class="options-dropdown options-dropdown--norm"
                                        v-if="!loadingAirports && !(filteredToAirports?.length || 0)">
                                        <div class="options-dropdown__header justify-content-center">
                                            <span class="text-danger" style="font-weight: 500;">No Matches Found</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="fs-pro-date-pair">
                            <div class="fs-pro-date-cell hs-field hs-field--date" id="flight-departure-box">
                                <div class="hs-field__inner fs-pro-date-inner">
                                    <div class="hs-field__label fs-pro-date-label"><i class='bx bx-calendar'></i><span>DEPART</span>
                                        <i class='bx bx-chevron-down fs-pro-date-chevron'></i></div>
                                    <div class="hs-date-display">
                                        <span class="hs-date-display__day" id="flight-departure-dd">&mdash;</span>
                                        <div class="hs-date-display__meta">
                                            <span class="hs-date-display__month"
                                                id="flight-departure-mon">&nbsp;</span>
                                            <span class="hs-date-display__weekday"
                                                id="flight-departure-day">&nbsp;</span>
                                        </div>
                                    </div>
                                    <input readonly autocomplete="off" type="hidden" name="departure_date"
                                        id="flight-departure-input">
                                </div>
                            </div>

                            <div class="fs-pro-date-cell hs-field hs-field--date fs-pro-return-cell"
                                :class="{ 'fs-pro-return-cell--soft': tripType === 'one_way' }"
                                id="flight-return-box">
                                <div class="hs-field__inner fs-pro-date-inner fs-pro-return-inner">
                                    <button type="button" class="fs-pro-return-clear"
                                        v-show="tripType === 'round_trip' && returnDate" @click.stop="clearQuickReturn"
                                        title="Clear return" aria-label="Clear return date">
                                        <i class='bx bx-x'></i>
                                    </button>
                                    <div class="hs-field__label fs-pro-date-label"><i class='bx bx-calendar'></i><span>RETURN</span>
                                        <i class='bx bx-chevron-down fs-pro-date-chevron'></i></div>
                                    <div class="hs-date-display">
                                        <span class="hs-date-display__day" id="flight-return-dd">&mdash;</span>
                                        <div class="hs-date-display__meta">
                                            <span class="hs-date-display__month"
                                                id="flight-return-mon">&nbsp;</span>
                                            <span class="hs-date-display__weekday"
                                                id="flight-return-day">&nbsp;</span>
                                        </div>
                                    </div>
                                    <input readonly autocomplete="off" type="hidden" name="return_date"
                                        id="flight-return-input">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

        <template v-else>
            <div class="fs-multicity">
                <div class="fs-multicity__row" v-for="(segment, index) in multiCitySegments" :key="segment.key">
                    <div class="fs-multicity__grid">
                        <div class="hs-field hs-field--destination fs-multicity__field"
                            :ref="el => setSegmentFieldRef(index, 'from', el)">
                            <div class="hs-field__inner" @click.stop="openSegmentDropdown(index, 'from')">
                                <div class="hs-field__label">FROM</div>
                                <div class="hs-field__value-row">
                                    <input type="text" autocomplete="off" class="hs-field__input"
                                        v-model="segment.fromInput" @input="onSegmentAirportInput(index, 'from')"
                                        placeholder="City or airport">
                                    <input type="hidden" :name="'segments[' + index + '][from]'"
                                        :value="segment.selectedFrom ? segment.selectedFrom.code : ''">
                                    <i class='bx bx-plane-take-off hs-field__icon'></i>
                                </div>
                            </div>

                            <div class="options-dropdown-wrapper options-dropdown-wrapper--from"
                                :class="{ open: segment.fromDropdownOpen, scroll: filteredSegmentAirports(segment, 'from').length > 9 }">
                                <div class="options-dropdown" v-if="loadingAirports">
                                    <div class="options-dropdown__body p-0">
                                        <ul class="options-dropdown-list">
                                            <li class="options-dropdown-list__item no-hover" v-for="n in 5"
                                                :key="'seg-from-skel-' + segment.key + '-' + n">
                                                <div class="skeleton"></div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="options-dropdown"
                                    v-if="!loadingAirports && filteredSegmentAirports(segment, 'from').length > 0">
                                    <div class="options-dropdown__header">
                                        <span>Airports</span>
                                    </div>
                                    <div class="options-dropdown__body p-0">
                                        <ul class="options-dropdown-list">
                                            <li class="options-dropdown-list__item"
                                                v-for="airport in filteredSegmentAirports(segment, 'from')"
                                                :key="'seg-from-' + segment.key + '-' + airport.code"
                                                @click="selectSegmentAirport(index, 'from', airport)">
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

                                <div class="options-dropdown options-dropdown--norm"
                                    v-if="!loadingAirports && !filteredSegmentAirports(segment, 'from').length">
                                    <div class="options-dropdown__header justify-content-center">
                                        <span class="text-danger" style="font-weight: 500;">No Matches Found</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hs-field hs-field--destination fs-multicity__field"
                            :ref="el => setSegmentFieldRef(index, 'to', el)">
                            <div class="hs-field__inner" @click.stop="openSegmentDropdown(index, 'to')">
                                <div class="hs-field__label">TO</div>
                                <div class="hs-field__value-row">
                                    <input type="text" autocomplete="off" class="hs-field__input"
                                        v-model="segment.toInput" @input="onSegmentAirportInput(index, 'to')"
                                        placeholder="City or airport">
                                    <input type="hidden" :name="'segments[' + index + '][to]'"
                                        :value="segment.selectedTo ? segment.selectedTo.code : ''">
                                    <i class='bx bx-plane-landing hs-field__icon'></i>
                                </div>
                            </div>

                            <div class="options-dropdown-wrapper options-dropdown-wrapper--from"
                                :class="{ open: segment.toDropdownOpen, scroll: filteredSegmentAirports(segment, 'to').length > 9 }">
                                <div class="options-dropdown" v-if="loadingAirports">
                                    <div class="options-dropdown__body p-0">
                                        <ul class="options-dropdown-list">
                                            <li class="options-dropdown-list__item no-hover" v-for="n in 5"
                                                :key="'seg-to-skel-' + segment.key + '-' + n">
                                                <div class="skeleton"></div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="options-dropdown"
                                    v-if="!loadingAirports && filteredSegmentAirports(segment, 'to').length > 0">
                                    <div class="options-dropdown__header">
                                        <span>Airports</span>
                                    </div>
                                    <div class="options-dropdown__body p-0">
                                        <ul class="options-dropdown-list">
                                            <li class="options-dropdown-list__item"
                                                v-for="airport in filteredSegmentAirports(segment, 'to')"
                                                :key="'seg-to-' + segment.key + '-' + airport.code"
                                                @click="selectSegmentAirport(index, 'to', airport)">
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

                                <div class="options-dropdown options-dropdown--norm"
                                    v-if="!loadingAirports && !filteredSegmentAirports(segment, 'to').length">
                                    <div class="options-dropdown__header justify-content-center">
                                        <span class="text-danger" style="font-weight: 500;">No Matches Found</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hs-field hs-field--date fs-multicity__date"
                            :data-index="index"
                            :id="'flight-multicity-box-' + index">
                            <div class="hs-field__inner">
                                <div class="hs-field__label"><i class='bx bx-calendar'></i> DEPARTURE <i
                                        class='bx bx-chevron-down' style="font-size:11px"></i></div>
                                <div class="hs-date-display">
                                    <span class="hs-date-display__day">@{{ segmentDateParts(segment.departureDate).day }}</span>
                                    <div class="hs-date-display__meta">
                                        <span class="hs-date-display__month">@{{ segmentDateParts(segment.departureDate).month }}</span>
                                        <span class="hs-date-display__weekday">@{{ segmentDateParts(segment.departureDate).weekday }}</span>
                                    </div>
                                </div>
                                <input readonly autocomplete="off" type="text" class="fs-multicity__date-input"
                                    :id="'flight-multicity-input-' + index">
                                <input type="hidden" :name="'segments[' + index + '][departure_date]'"
                                    :value="segmentDisplayDate(segment.departureDate)">
                            </div>
                        </div>

                        <button type="button" class="fs-multicity__remove" @click="removeMultiCitySegment(index)"
                            v-if="multiCitySegments.length > minMultiCitySegments">
                            <i class='bx bx-x'></i>
                        </button>
                    </div>
                </div>

                <div class="fs-multicity__actions">
                    <button type="button" class="fs-add-city-btn" @click="addMultiCitySegment"
                        :disabled="multiCitySegments.length >= maxMultiCitySegments">
                        <i class='bx bx-plus'></i> Add City
                    </button>
                </div>
            </div>
        </template>

                <div class="fs-pro-footer">
                    <div class="fs-pro-pax-cabin-row">
                        <div class="hs-field hs-field--rooms fs-pro-travellers" ref="travellersRef">
                            <div class="hs-field__inner fs-pro-travellers__inner" @click.stop="toggleTravellers">
                                <div class="hs-field__label fs-pro-label">TRAVELLERS
                                    <i class='bx bx-chevron-down fs-pro-chevron'></i></div>
                                <div class="hs-field__value">
                                    <span class="hs-rooms-text fs-pro-pax-line">@{{ travellersTextCompact }}</span>
                                </div>
                            </div>

                            <div class="options-dropdown-wrapper options-dropdown-wrapper--pax"
                                :class="{ open: travellersOpen }">
                                <div class="options-dropdown options-dropdown--norm">
                                    <div class="options-dropdown__body">
                                        <input type="hidden" name="adults" :value="adults">
                                        <input type="hidden" name="children" :value="children">
                                        <input type="hidden" name="infants" :value="infants">

                                        <ul class="paxs-list mt-0">
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

                        <label class="fs-pro-select-group">
                            <span class="fs-pro-select-group__label">Onward Cabin Class</span>
                            <div class="fs-pro-select-wrap">
                                <i class='bx bx-chair fs-pro-select-wrap__icon'></i>
                                <select v-model="onwardCabin" class="fs-pro-select-el">
                                    <option value="Economy">Economy</option>
                                    <option value="Premium Economy">Premium Economy</option>
                                    <option value="Business">Business</option>
                                    <option value="First">First</option>
                                </select>
                                <i class='bx bx-chevron-down fs-pro-select-el-chevron'></i>
                            </div>
                        </label>

                        <label class="fs-pro-select-group" v-show="tripType === 'round_trip'">
                            <span class="fs-pro-select-group__label">Return Cabin Class</span>
                            <div class="fs-pro-select-wrap">
                                <i class='bx bx-chair fs-pro-select-wrap__icon'></i>
                                <select v-model="returnCabin" class="fs-pro-select-el">
                                    <option value="Economy">Economy</option>
                                    <option value="Premium Economy">Premium Economy</option>
                                    <option value="Business">Business</option>
                                    <option value="First">First</option>
                                </select>
                                <i class='bx bx-chevron-down fs-pro-select-el-chevron'></i>
                            </div>
                        </label>
                    </div>

                    <div class="fs-pro-airline-pref">
                        <div class="fs-pro-airline-pref__head">
                            <i class='bx bx-slider-alt'></i>
                            <span>Airline preference</span>
                        </div>
                        <div class="fs-pro-airline-pref__toggles">
                            <button type="button" class="fs-air-chip" :class="{ 'is-active': airlineAll }"
                                @click.prevent="airlineAll = !airlineAll">
                                All Airlines
                                <i class='bx bx-x' v-if="airlineAll" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="fs-air-chip" :class="{ 'is-active': airlineLowCost }"
                                @click.prevent="airlineLowCost = !airlineLowCost">
                                <i class='bx bx-check fs-air-chip__check'></i>
                                Low Cost Airlines
                            </button>
                            <button type="button" class="fs-air-chip" :class="{ 'is-active': airlineGds }"
                                @click.prevent="airlineGds = !airlineGds">
                                <i class='bx bx-check fs-air-chip__check'></i>
                                GDS Airlines
                            </button>
                        </div>
                    </div>

                    <div class="fs-pro-actions-footer">
                        <div class="fs-search-filters">
                            <label class="fs-filter-chip" :class="{ active: directFlight }">
                                <input type="checkbox" v-model="directFlight">
                                <span class="fs-filter-chip__box"></span>
                                <span class="fs-filter-chip__label">Direct Flight</span>
                            </label>

                            <label class="fs-filter-chip" :class="{ active: nearbyAirports }">
                                <input type="checkbox" v-model="nearbyAirports">
                                <span class="fs-filter-chip__box"></span>
                                <span class="fs-filter-chip__label">Nearby Airports</span>
                            </label>

                            <label class="fs-filter-chip" :class="{ active: studentFare }">
                                <input type="checkbox" v-model="studentFare">
                                <span class="fs-filter-chip__box"></span>
                                <span class="fs-filter-chip__label">Student Fare</span>
                            </label>
                        </div>

                        <button type="submit" class="fs-pro-search-btn" :disabled="!isSearchEnabled || isSearching">
                            <template v-if="isSearching">
                                <i class='bx bx-loader-alt bx-spin'></i>
                                Searching…
                            </template>
                            <template v-else>
                                <i class='bx bx-search-alt'></i> Search Flights
                            </template>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <aside class="fs-pro-aside">
            <div class="fs-pro-tile-grid">
                <a href="#" class="fs-pro-tile fs-pro-tile--offline" @click.prevent>
                    <span class="fs-pro-tile__icon"><i class='bx bx-wifi-off'></i></span>
                    <span class="fs-pro-tile__text">Offline request</span>
                </a>
                <a href="#" class="fs-pro-tile fs-pro-tile--import" @click.prevent>
                    <span class="fs-pro-tile__icon"><i class='bx bx-import'></i></span>
                    <span class="fs-pro-tile__text">Import PNR</span>
                </a>
                <a href="#" class="fs-pro-tile fs-pro-tile--hold" @click.prevent>
                    <span class="fs-pro-tile__icon"><i class='bx bx-stopwatch'></i></span>
                    <span class="fs-pro-tile__text">Hold itineraries</span>
                </a>
                <a href="#" class="fs-pro-tile fs-pro-tile--calendar" @click.prevent>
                    <span class="fs-pro-tile__icon"><i class='bx bx-calendar-event'></i></span>
                    <span class="fs-pro-tile__text">Travel calendar</span>
                </a>
            </div>

            <div class="fs-pro-recent-panel">
                <div class="fs-pro-recent-panel__label">Recent searches</div>
                <a href="#" class="fs-pro-recent-chip" @click.prevent>
                    <span>Dubai → Karachi</span>
                    <span class="fs-pro-recent-chip__dates">01 Jun&nbsp;'26&nbsp;·&nbsp;10 Jun&nbsp;'26</span>
                </a>
            </div>

            <nav class="fs-pro-util-links" aria-label="Quick links">
                <a href="#" class="fs-util-link" @click.prevent>
                    <span class="fs-util-dot fs-util-dot--amber"><i class='bx bx-info-circle'></i></span>
                    Notice board
                </a>
                <a href="#" class="fs-util-link" @click.prevent>
                    <span class="fs-util-dot fs-util-dot--emerald"><i class='bx bx-wallet'></i></span>
                    Recharge
                </a>
                <a href="#" class="fs-util-link" @click.prevent>
                    <span class="fs-util-dot fs-util-dot--rose"><i class='bx bx-bell'></i></span>
                    How to use portal
                </a>
                <a href="#" class="fs-util-link" @click.prevent>
                    <span class="fs-util-dot fs-util-dot--sky"><i class='bx bx-news'></i></span>
                    Newsletter
                </a>
            </nav>
        </aside>

        <div class="fs-pro-promos" aria-hidden="true">
            <a href="#" class="fs-promo fs-promo--gold" @click.prevent>
                <span class="fs-promo__kicker">Exclusive</span>
                <span class="fs-promo__title">Hotel bookings</span>
            </a>
            <a href="#" class="fs-promo fs-promo--ocean" @click.prevent>
                <span class="fs-promo__kicker">Route</span>
                <span class="fs-promo__title">Al Maktoum — Saudi Arabia</span>
            </a>
            <a href="#" class="fs-promo fs-promo--night" @click.prevent>
                <span class="fs-promo__kicker">NDC fares</span>
                <span class="fs-promo__title">Now available exclusively online</span>
            </a>
        </div>
    </form>
</div>
@push('css')
    <style>
        .fs-pro-enterprise {
            font-family: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
                Helvetica, Arial, sans-serif;
            color: #0f172a;
            letter-spacing: 0;
        }

        .fs-pro-enterprise.hotel-search-redesign {
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .fs-pro-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 288px;
            gap: 1.125rem;
            align-items: start;
        }

        @media (max-width: 991px) {
            .fs-pro-layout {
                grid-template-columns: 1fr;
            }
        }

        .fs-pro-card {
            border-radius: 20px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: linear-gradient(180deg, #fff 0%, #fafbfc 100%);
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.06);
            padding: 1.35rem 1.5rem 1.35rem;
        }

        .fs-pro-card__head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.15rem;
        }

        .fs-pro-card__title {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .fs-pro-card__title i {
            color: var(--color-primary);
            opacity: 0.9;
        }

        .fs-pro-card__subtitle {
            margin: 0.2rem 0 0;
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            letter-spacing: 0.02em;
        }

        .fs-pro-specials {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .fs-pro-special-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.72rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: #fff;
            font-weight: 600;
            font-size: 0.72rem;
            color: #334155;
            text-decoration: none;
            cursor: pointer;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .fs-pro-special-chip:hover {
            border-color: #94a3b8;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
        }

        .fs-pro-special-chip__icon {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .fs-pro-special-chip__icon--a2a {
            background: #fde8e9;
            color: #dc2626;
        }

        .fs-pro-special-chip__icon--akbar {
            background: #eef2ff;
            color: #2563eb;
        }

        .fs-pro-special-chip__text {
            letter-spacing: 0.015em;
        }

        .fs-pro-badge-new {
            font-size: 0.61rem;
            font-weight: 800;
            padding: 0.12rem 0.42rem;
            border-radius: 4px;
            background: linear-gradient(180deg, #ef4444, #dc2626);
            color: #fff;
            line-height: 1.2;
        }

        .fs-pro-badge-new--blue {
            background: linear-gradient(180deg, #2563eb, #1d4ed8);
        }

        .fs-pro-enterprise .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .fs-pro-enterprise .options-dropdown-wrapper--from {
            left: auto;
            right: auto;
        }

        .fs-pro-trip-types {
            margin-bottom: 1rem;
            gap: 0.62rem !important;
        }

        /* Route */
        .fs-pro-route-sheet {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
            margin-bottom: 1rem;
        }

        .fs-pro-route-pair {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 52px minmax(0, 1fr);
            gap: 0.45rem;
            align-items: stretch;
        }

        @media (max-width: 640px) {
            .fs-pro-route-pair {
                grid-template-columns: 1fr;
            }

            .fs-pro-swap-wrap {
                justify-content: flex-end !important;
                transform: rotate(90deg);
            }
        }

        .fs-pro-route-field {
            position: relative;
            overflow: visible;
        }

        .fs-pro-route-field__shell {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            min-height: 96px;
        }

        .fs-pro-route-field__shell:hover {
            border-color: #b8c3d9;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .fs-pro-route-field .hs-field__inner {
            cursor: pointer;
            padding: 12px 16px !important;
        }

        .fs-pro-route-field__label {
            font-weight: 800;
            font-size: 0.65rem !important;
            letter-spacing: 0.07em !important;
            color: #94a3b8 !important;
        }

        .fs-pro-route-chosen__city {
            display: block;
            margin-top: 0.05rem;
            font-size: 1.125rem !important;
            font-weight: 700 !important;
            color: #0f172a;
            letter-spacing: -0.015em;
        }

        .fs-pro-route-chosen__airport {
            margin-top: 0.2rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #64748b;
            line-height: 1.35;
        }

        .fs-pro-route-input {
            padding-left: 0 !important;
            font-weight: 600 !important;
        }

        .fs-pro-route-inline-icon {
            opacity: 0.35;
            font-size: 1.15rem;
        }

        .fs-pro-swap-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fs-pro-swap-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(165deg, #c7e355 0%, #a7c834 52%, #8fb02a 100%);
            color: #1a2e03;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(146, 189, 40, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.65);
            font-size: 1.35rem;
            transition: transform 0.14s ease, box-shadow 0.14s ease;
        }

        .fs-pro-swap-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(146, 189, 40, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .fs-pro-swap-btn:active {
            transform: translateY(0);
        }

        /* Dates */
        .fs-pro-date-pair {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.875rem;
        }

        .fs-pro-date-cell .hs-field__inner {
            position: relative;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: #fff;
            cursor: pointer;
            min-height: 88px;
            padding: 12px 16px !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .fs-pro-date-cell:hover .hs-field__inner {
            border-color: #b8c3d9;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .fs-pro-date-label {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem;
            flex-wrap: wrap;
            font-weight: 800 !important;
            font-size: 0.65rem !important;
            letter-spacing: 0.08em !important;
            color: #94a3b8 !important;
        }

        .fs-pro-date-chevron {
            font-size: 10px !important;
            opacity: 0.5;
            margin-left: 0 !important;
            margin-top: 0 !important;
        }

        .fs-pro-enterprise .hs-date-display__day {
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.025em !important;
        }

        .fs-pro-return-inner {
            padding-right: 2rem !important;
        }

        .fs-pro-return-cell--soft .hs-field__inner {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .fs-pro-return-clear {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 28px;
            height: 28px;
            padding: 0;
            border: none;
            border-radius: 8px;
            background: #f1f5f9;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
            transition: color 0.15s ease, background 0.15s ease;
        }

        .fs-pro-return-clear:hover {
            color: var(--color-primary);
            background: #fff;
        }

        /* Footer */
        .fs-pro-footer {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(226, 232, 240, 0.9);
        }

        .fs-pro-pax-cabin-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.875rem;
            align-items: flex-end;
        }

        .fs-pro-travellers {
            flex: 1;
            min-width: 168px;
        }

        .fs-pro-travellers__inner.hs-field__inner {
            cursor: pointer;
            border-radius: 12px !important;
            border: 1px solid rgba(148, 163, 184, 0.45) !important;
            background: #fff !important;
        }

        .fs-pro-label {
            font-weight: 800 !important;
            letter-spacing: 0.0625em !important;
            font-size: 0.65rem !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem;
            color: #94a3b8 !important;
        }

        .fs-pro-chevron {
            font-size: 10px !important;
            opacity: 0.45;
            margin-left: 0 !important;
        }

        .fs-pro-pax-line {
            font-size: 0.84rem !important;
            font-weight: 650 !important;
            color: #334155 !important;
        }

        .fs-pro-pax-compact {
            font-weight: 600;
            margin-left: 0.35rem !important;
            font-size: 0.73rem !important;
            color: #94a3b8 !important;
        }

        .fs-pro-select-group {
            flex: 1;
            min-width: 150px;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .fs-pro-select-group__label {
            font-weight: 800;
            letter-spacing: 0.058em;
            font-size: 0.645rem;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .fs-pro-select-wrap {
            position: relative;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: #fff;
            overflow: visible;
            display: flex;
            align-items: center;
            min-height: 44px;
        }

        .fs-pro-select-wrap__icon {
            position: absolute;
            left: 12px;
            font-size: 1.125rem;
            color: var(--color-primary);
            opacity: 0.6;
            pointer-events: none;
        }

        .fs-pro-select-el {
            appearance: none;
            width: 100%;
            padding: 0.55rem 2.15rem 0.55rem 2.65rem !important;
            border: none;
            margin: 0;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.84rem;
            font-weight: 650 !important;
            color: #0f172a;
            outline: none;
            background: transparent;
        }

        .fs-pro-select-el-chevron {
            position: absolute;
            right: 12px;
            font-size: 0.9rem;
            color: #94a3b8;
            pointer-events: none;
        }

        .fs-pro-airline-pref__head {
            display: flex;
            align-items: center;
            gap: 0.42rem;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.068em;
            text-transform: uppercase;
            padding: 0.38rem 0.92rem;
            border-radius: 10px;
            margin-bottom: 0.62rem;
            width: fit-content;
            background: linear-gradient(90deg, #e9f8ff 0%, #dbf3ff 100%);
            border: 1px solid rgba(56, 189, 248, 0.32);
            color: #0369a1;
        }

        .fs-pro-airline-pref__toggles {
            display: flex;
            flex-wrap: wrap;
            gap: 0.54rem;
        }

        .fs-air-chip {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 650 !important;
            padding: 0.48rem 0.92rem !important;
            border-radius: 999px !important;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            cursor: pointer;
            transition: border-color 0.15s ease, background-color 0.15s ease, color 0.15s ease;
        }

        .fs-air-chip:hover {
            border-color: #cbd5f5;
            color: var(--color-primary);
        }

        .fs-air-chip.is-active {
            border-color: #bdcf5a;
            background: linear-gradient(180deg, rgba(197, 230, 80, 0.14) 0%, rgba(173, 210, 50, 0.12));
            color: #3f6212;
            box-shadow: inset 0 0 0 1px rgba(162, 201, 50, 0.25);
        }

        .fs-air-chip__check {
            font-weight: 800;
            opacity: 0.35;
        }

        .fs-air-chip.is-active .fs-air-chip__check {
            opacity: 1;
            color: #54700d;
        }

        .fs-pro-actions-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .fs-pro-actions-footer .fs-search-filters {
            margin-bottom: 0 !important;
        }

        .fs-pro-search-btn {
            border-radius: 12px !important;
            padding: 0.78rem 1.68rem !important;
            border: none;
            cursor: pointer;
            font-size: 0.95rem !important;
            font-weight: 760 !important;
            letter-spacing: 0.01em !important;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            color: #253304 !important;
            background: linear-gradient(172deg, #d5ec6b 3%, #b8d942 52%, #9dbf2e 96%) !important;
            box-shadow: 0 8px 24px rgba(158, 200, 50, 0.35), inset 0 2px 0 rgba(255, 255, 255, 0.45) !important;
            transition: transform 0.14s ease, box-shadow 0.14s ease, opacity 0.14s ease;
        }

        .fs-pro-search-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 10px 32px rgba(158, 200, 50, 0.4), inset 0 2px 0 rgba(255, 255, 255, 0.6) !important;
        }

        .fs-pro-search-btn:disabled {
            opacity: 0.54;
            cursor: not-allowed;
            transform: none;
            box-shadow: none !important;
        }

        /* Aside */
        .fs-pro-aside {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .fs-pro-tile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
        }

        .fs-pro-tile {
            border-radius: 16px;
            padding: 1rem 0.82rem !important;
            text-decoration: none !important;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0.55rem;
            color: inherit !important;
            font-weight: 750;
            letter-spacing: 0.036em !important;
            font-size: 0.628rem !important;
            text-transform: uppercase !important;
            line-height: 1.33;
            cursor: pointer;
            border: none;
            background: transparent;
            box-shadow: inset 0 1px rgba(255, 255, 255, 0.35);
            transition: transform 0.13s ease, box-shadow 0.13s ease;
        }

        .fs-pro-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.1);
        }

        .fs-pro-tile__icon {
            align-self: flex-start;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.28rem !important;
        }

        .fs-pro-tile--offline {
            background: linear-gradient(145deg, #fff8e8 0%, #fff1d9 95%);
            color: #7c5612 !important;
        }

        .fs-pro-tile--offline .fs-pro-tile__icon {
            background: rgba(255, 237, 200, 0.65);
            color: #92400e;
        }

        .fs-pro-tile--import {
            background: linear-gradient(145deg, #eff8ff 0%, #dfefff 94%);
            color: #174c86 !important;
        }

        .fs-pro-tile--import .fs-pro-tile__icon {
            background: rgba(191, 219, 254, 0.5);
            color: #2563eb;
        }

        .fs-pro-tile--hold {
            background: linear-gradient(145deg, #ecfeff 0%, #dbf7f6 93%);
            color: #14606a !important;
        }

        .fs-pro-tile--hold .fs-pro-tile__icon {
            background: rgba(165, 243, 252, 0.4);
            color: #0e7490;
        }

        .fs-pro-tile--calendar {
            background: linear-gradient(145deg, #fdf2f9 0%, #fde7f7 93%);
            color: #893168 !important;
        }

        .fs-pro-tile--calendar .fs-pro-tile__icon {
            background: rgba(251, 207, 232, 0.45);
            color: #be185d;
        }

        .fs-pro-recent-panel__label {
            font-size: 0.695rem !important;
            font-weight: 800;
            letter-spacing: 0.05em !important;
            text-transform: uppercase;
            margin-bottom: 0.54rem !important;
            color: #94a3b8;
            display: inline-block !important;
        }

        .fs-pro-recent-chip {
            border-radius: 999px !important;
            padding: 0.68rem 0.94rem !important;
            border: 1px solid rgba(226, 232, 240, 0.92) !important;
            font-size: 0.78rem;
            font-weight: 650 !important;
            font-family: inherit;
            cursor: pointer;
            color: inherit;
            white-space: normal;
            line-height: 1.35 !important;
            display: inline-flex !important;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: wrap;
            text-decoration: none !important;
            background: rgba(248, 250, 252, 0.7) !important;
            backdrop-filter: blur(6px);
            transition: background 0.15s ease, border-color 0.15s ease;
        }

        .fs-pro-recent-chip:hover {
            background: #fff !important;
            border-color: #cfd8e9 !important;
        }

        .fs-pro-recent-chip__dates {
            font-variant-numeric: tabular-nums;
            opacity: 0.65;
            font-weight: 600;
        }

        .fs-pro-util-links {
            display: flex;
            flex-direction: column;
            gap: 0.35rem !important;
        }

        .fs-util-link {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.6rem !important;
            padding: 0.4rem !important;
            border-radius: 10px !important;
            margin: -0.4rem;
            flex-wrap: wrap;
            justify-content: flex-start;
            cursor: pointer;
            white-space: normal;
            line-height: 1.3 !important;
            color: inherit;
            font-size: 0.8rem !important;
            font-weight: 630 !important;
            text-decoration: none !important;
            transition: background 0.12s ease, color 0.12s ease;
        }

        .fs-util-link:hover {
            background: rgba(255, 255, 255, 0.6);
            color: var(--color-primary) !important;
        }

        .fs-util-dot {
            width: 30px !important;
            height: 30px !important;
            flex-shrink: 0;
            border-radius: 50% !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.955rem !important;
            margin-top: -0 !important;
        }

        .fs-util-dot i {
            line-height: 1 !important;
        }

        .fs-util-dot--amber {
            background: #fcd34d;
            color: #78350f;
        }

        .fs-util-dot--emerald {
            background: #6ee7b7;
            color: #064e3b;
        }

        .fs-util-dot--rose {
            background: #fda4af;
            color: #881337;
        }

        .fs-util-dot--sky {
            background: #7dd3fc;
            color: #0c4a6e;
        }

        .fs-promo {
            text-decoration: none !important;
            border-radius: 14px !important;
            overflow: visible;
            cursor: pointer;
            font-weight: 750;
            letter-spacing: 0.02em !important;
            font-size: 0.782rem !important;
            line-height: 1.3 !important;
            padding: 0.94rem !important;
            display: inline-flex !important;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: nowrap;
            min-height: 68px !important;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.1);
            position: relative;
            isolation: isolate;
            overflow: clip;
            color: inherit;
            transition: transform 0.12s ease !important;
        }

        .fs-promo:hover {
            transform: translateY(-2px);
        }

        .fs-promo__kicker {
            font-variant: all-small-caps;
            font-size: 0.68rem;
            opacity: 0.88 !important;
        }

        .fs-promo__title {
            display: block !important;
        }

        .fs-promo--gold {
            background: linear-gradient(110deg, #f7d47a 0%, #eab308 54%, #b45309 120%) !important;
            color: #292524 !important;
        }

        .fs-promo--ocean {
            background: linear-gradient(120deg, #38bdf8 0%, #2563eb 86%) !important;
            color: #f8fafc !important;
        }

        .fs-promo--night {
            background: linear-gradient(120deg, #1e293b 0%, #0f172a 90%) !important;
            border: 1px solid rgba(148, 163, 184, 0.2) !important;
            color: #e2e8f0 !important;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.4) !important;
        }

        .fs-promo--ocean .fs-promo__kicker,
        .fs-promo--ocean .fs-promo__title,
        .fs-promo--night .fs-promo__kicker,
        .fs-promo--night .fs-promo__title {
            color: inherit !important;
        }

        /* Promotional strip spanning full grid width */
        .fs-pro-promos {
            grid-column: 1 / -1;
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        /* Trip type accents — lime (reference UI) */
        .fs-pro-enterprise .fs-trip-types__item:hover {
            border-color: rgba(173, 201, 50, 0.55);
            color: var(--color-primary);
            box-shadow: 0 4px 14px rgba(158, 200, 50, 0.16);
        }

        .fs-pro-enterprise .fs-trip-types__item.active {
            border-color: rgba(158, 200, 50, 0.55);
            background: rgba(197, 230, 68, 0.16);
            color: var(--color-primary);
        }

        .fs-pro-enterprise .fs-trip-types__item.active:hover {
            border-color: rgba(138, 180, 40, 0.65);
        }

        .fs-pro-enterprise .fs-trip-types__item.active .fs-trip-types__dot {
            background: #93b82a !important;
            box-shadow: 0 0 0 3px rgba(158, 200, 50, 0.22);
        }

        .fs-pro-enterprise .fs-filter-chip:hover {
            border-color: rgba(173, 201, 50, 0.45);
            color: var(--color-primary);
            box-shadow: 0 4px 12px rgba(158, 200, 50, 0.14);
        }

        .fs-pro-enterprise .fs-filter-chip.active {
            border-color: rgba(158, 200, 50, 0.5);
            background: rgba(197, 230, 68, 0.14);
            color: var(--color-primary);
        }

        .fs-pro-enterprise .fs-filter-chip.active .fs-filter-chip__box {
            border-color: #8fb02a;
            background: #93b82a;
            box-shadow: 0 0 0 3px rgba(158, 200, 50, 0.2);
        }

        .fs-trip-types {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.95rem;
        }

        .fs-trip-types__item {
            border: 1px solid #e4e7ee;
            background: #ffffff;
            color: #5b6472;
            border-radius: 999px;
            padding: 0.5rem 0.85rem;
            font-size: 0.84rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-width: 104px;
            box-shadow: none;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease,
                box-shadow 0.2s ease;
            position: relative;
        }

        .fs-trip-types__item:hover {
            border-color: #d7b0bc;
            color: var(--color-primary);
            box-shadow: 0 4px 10px rgba(205, 27, 79, 0.08);
        }

        .fs-trip-types__item.active {
            background: rgba(205, 27, 79, 0.08);
            color: var(--color-primary);
            border-color: rgba(205, 27, 79, 0.26);
            box-shadow: inset 0 0 0 1px rgba(205, 27, 79, 0.05);
        }

        .fs-trip-types__dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #c8ced8;
            transition: inherit;
        }

        .fs-trip-types__item.active .fs-trip-types__dot {
            background: #ec4899;
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.18);
        }

        .fs-trip-types__item.active:hover {
            border-color: rgba(205, 27, 79, 0.34);
            color: var(--color-primary);
            box-shadow: 0 4px 12px rgba(205, 27, 79, 0.1);
        }

        .fs-search-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            margin-bottom: 0.95rem;
        }

        .fs-filter-chip {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-height: 38px;
            padding: 0.55rem 0.9rem;
            border: 1px solid #e3e7ef;
            border-radius: 999px;
            background: #fff;
            color: #5b6472;
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            transition: border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .fs-filter-chip input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .fs-filter-chip:hover {
            border-color: #d7b0bc;
            color: var(--color-primary);
            box-shadow: 0 4px 10px rgba(205, 27, 79, 0.08);
        }

        .fs-filter-chip.active {
            border-color: rgba(205, 27, 79, 0.26);
            background: rgba(205, 27, 79, 0.08);
            color: var(--color-primary);
        }

        .fs-filter-chip__box {
            width: 16px;
            height: 16px;
            border: 1.5px solid #cfd5df;
            border-radius: 5px;
            background: #fff;
            position: relative;
            flex-shrink: 0;
            transition: inherit;
        }

        .fs-filter-chip.active .fs-filter-chip__box {
            border-color: #ec4899;
            background: #ec4899;
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.18);
        }

        .fs-filter-chip.active .fs-filter-chip__box::after {
            content: "";
            position: absolute;
            left: 4px;
            top: 1px;
            width: 4px;
            height: 8px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .hs-field--disabled {
            background: #f8fafc;
            pointer-events: none;
        }

        .hs-field__note {
            font-size: 0.82rem;
            line-height: 1.35;
            color: #64748b;
            font-weight: 600;
            max-width: 150px;
        }

        .fs-multicity {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            margin-bottom: 0.85rem;
        }

        .fs-multicity__row {
            border: 1.5px solid #e0e0e0;
            border-radius: 0.8rem;
            background: #fafafa;
            overflow: visible;
        }

        .fs-multicity__grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.2fr) minmax(160px, 0.9fr) 48px;
            align-items: stretch;
        }

        .fs-multicity__field {
            border-right: 1.5px solid #e0e0e0;
        }

        .fs-multicity__date {
            border-right: 1.5px solid #e0e0e0;
            min-width: 0;
        }

        .fs-multicity__date .hs-field__inner {
            padding: 10px 16px;
        }

        .fs-multicity__date-input {
            border: none;
            background: transparent;
            padding: 0;
            width: 100%;
            height: 0;
            min-height: 0;
            opacity: 0;
            position: absolute;
            pointer-events: none;
            outline: none;
        }

        .fs-multicity__remove {
            border: none;
            background: transparent;
            color: #64748b;
            font-size: 1.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fs-multicity__remove:hover {
            color: var(--color-primary);
        }

        .fs-multicity__actions {
            display: flex;
            justify-content: flex-end;
        }

        .fs-add-city-btn {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #1e3a8a;
            border-radius: 0.55rem;
            padding: 0.55rem 0.85rem;
            font-size: 0.86rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .fs-add-city-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 991px) {
            .fs-multicity__grid {
                grid-template-columns: 1fr;
            }

            .fs-multicity__field,
            .fs-multicity__date {
                border-right: none;
                border-bottom: 1.5px solid #e0e0e0;
            }

            .fs-multicity__remove {
                min-height: 46px;
            }
        }
    </style>
@endpush
