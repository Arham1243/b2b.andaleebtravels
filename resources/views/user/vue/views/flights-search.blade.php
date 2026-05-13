<div class="hotel-search-redesign flight-search-redesign fs-pro-enterprise" v-cloak>
    <form method="GET" action="{{ route('user.flights.search') }}" @submit="onFlightSearchSubmit" class="fs-pro-layout">
        <input type="hidden" name="trip_type" :value="tripType">

        <div class="fs-pro-layout__main">
            <div class="fs-pro-card">

                <header class="fs-pro-card__head">
                    <div class="fs-pro-card__title-wrap">
                        <div class="fs-pro-eyebrow">
                            <span class="fs-pro-eyebrow__dot"></span>
                            <span class="fs-pro-eyebrow__label">Live inventory</span>
                            <span class="fs-pro-eyebrow__sep">·</span>
                            <span class="fs-pro-eyebrow__meta">GDS · LCC · NDC</span>
                        </div>
                        <h2 class="fs-pro-card__title">Search Flights</h2>
                        <p class="fs-pro-card__subtitle">Domestic &amp; international fares,
                            consolidator-grade pricing.</p>
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

                <div class="fs-pro-controls-row">
                    <div class="fs-trip-types fs-pro-trip-types" role="tablist" aria-label="Trip type">
                        <button type="button" class="fs-trip-types__item" role="tab"
                            :class="{ active: tripType === 'one_way' }" :aria-selected="tripType === 'one_way'"
                            @click="setTripType('one_way')">
                            <i class='bx bx-right-arrow-alt fs-trip-types__icon'></i> One way
                        </button>
                        <button type="button" class="fs-trip-types__item" role="tab"
                            :class="{ active: tripType === 'round_trip' }"
                            :aria-selected="tripType === 'round_trip'" @click="setTripType('round_trip')">
                            <i class='bx bx-transfer fs-trip-types__icon'></i> Round trip
                        </button>
                        <button type="button" class="fs-trip-types__item" role="tab"
                            :class="{ active: tripType === 'multi_city' }"
                            :aria-selected="tripType === 'multi_city'" @click="setTripType('multi_city')">
                            <i class='bx bx-shuffle fs-trip-types__icon'></i> Multi-city
                        </button>
                    </div>

                    <div class="fs-pro-meta-row">
                        <span class="fs-pro-meta-chip" title="Booking currency">
                            <i class='bx bx-dollar-circle'></i>
                            <span>USD</span>
                            <i class='bx bx-chevron-down fs-pro-meta-chip__chevron'></i>
                        </span>
                        <a href="#" class="fs-pro-meta-link" @click.prevent>
                            <i class='bx bx-history'></i> History
                        </a>
                        <a href="#" class="fs-pro-meta-link" @click.prevent>
                            <i class='bx bx-bookmark'></i> Saved
                        </a>
                    </div>
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
                        <div class="fs-pro-airline-pref__label">
                            <i class='bx bx-slider-alt'></i>
                            <span>Airline preference</span>
                        </div>
                        <div class="fs-pro-airline-pref__toggles">
                            <button type="button" class="fs-air-chip" :class="{ 'is-active': airlineAll }"
                                @click.prevent="airlineAll = !airlineAll">
                                <span class="fs-air-chip__indicator"></span>
                                All airlines
                            </button>
                            <button type="button" class="fs-air-chip" :class="{ 'is-active': airlineLowCost }"
                                @click.prevent="airlineLowCost = !airlineLowCost">
                                <span class="fs-air-chip__indicator"></span>
                                Low cost (LCC)
                            </button>
                            <button type="button" class="fs-air-chip" :class="{ 'is-active': airlineGds }"
                                @click.prevent="airlineGds = !airlineGds">
                                <span class="fs-air-chip__indicator"></span>
                                GDS published
                            </button>
                            <button type="button" class="fs-air-chip" :class="{ 'is-active': airlineNdc }"
                                @click.prevent="airlineNdc = !airlineNdc">
                                <span class="fs-air-chip__indicator"></span>
                                NDC content
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
                                <span>Search flights</span>
                                <i class='bx bx-right-arrow-alt fs-pro-search-btn__arrow'></i>
                            </template>
                        </button>
                    </div>

                    <div class="fs-pro-trust-strip">
                        <span class="fs-pro-trust-item">
                            <i class='bx bx-shield-quarter'></i> Secure pricing &amp; PCI-compliant
                        </span>
                        <span class="fs-pro-trust-item">
                            <i class='bx bx-time-five'></i> Avg. response &lt;&thinsp;1.4&thinsp;s
                        </span>
                        <span class="fs-pro-trust-item">
                            <i class='bx bx-support'></i> 24×7 desk support
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <aside class="fs-pro-aside">
            <div class="fs-pro-aside-card">
                <div class="fs-pro-aside-card__head">
                    <span class="fs-pro-aside-card__label">Workspace</span>
                </div>
                <div class="fs-pro-tile-grid">
                    <a href="#" class="fs-pro-tile fs-pro-tile--offline" @click.prevent>
                        <span class="fs-pro-tile__icon"><i class='bx bx-envelope'></i></span>
                        <span class="fs-pro-tile__meta">
                            <span class="fs-pro-tile__title">Offline request</span>
                            <span class="fs-pro-tile__hint">Email the desk</span>
                        </span>
                    </a>
                    <a href="#" class="fs-pro-tile fs-pro-tile--import" @click.prevent>
                        <span class="fs-pro-tile__icon"><i class='bx bx-import'></i></span>
                        <span class="fs-pro-tile__meta">
                            <span class="fs-pro-tile__title">Import PNR</span>
                            <span class="fs-pro-tile__hint">From GDS</span>
                        </span>
                    </a>
                    <a href="#" class="fs-pro-tile fs-pro-tile--hold" @click.prevent>
                        <span class="fs-pro-tile__icon"><i class='bx bx-stopwatch'></i></span>
                        <span class="fs-pro-tile__meta">
                            <span class="fs-pro-tile__title">Hold itineraries</span>
                            <span class="fs-pro-tile__hint">Manage holds</span>
                        </span>
                    </a>
                    <a href="#" class="fs-pro-tile fs-pro-tile--calendar" @click.prevent>
                        <span class="fs-pro-tile__icon"><i class='bx bx-calendar'></i></span>
                        <span class="fs-pro-tile__meta">
                            <span class="fs-pro-tile__title">Travel calendar</span>
                            <span class="fs-pro-tile__hint">Lowest fare view</span>
                        </span>
                    </a>
                </div>
            </div>

            <div class="fs-pro-aside-card fs-pro-recent-panel">
                <div class="fs-pro-aside-card__head">
                    <span class="fs-pro-aside-card__label">Recent searches</span>
                    <a href="#" class="fs-pro-aside-card__action" @click.prevent>Clear</a>
                </div>
                <a href="#" class="fs-pro-recent-row" @click.prevent>
                    <div class="fs-pro-recent-row__route">
                        <span class="fs-pro-recent-row__city">DXB</span>
                        <i class='bx bx-right-arrow-alt fs-pro-recent-row__arrow'></i>
                        <span class="fs-pro-recent-row__city">KHI</span>
                    </div>
                    <div class="fs-pro-recent-row__meta">
                        <span class="fs-pro-recent-row__dates">01 Jun – 10 Jun '26</span>
                        <span class="fs-pro-recent-row__pax">1 ADT · Y</span>
                    </div>
                </a>
                <a href="#" class="fs-pro-recent-row" @click.prevent>
                    <div class="fs-pro-recent-row__route">
                        <span class="fs-pro-recent-row__city">DXB</span>
                        <i class='bx bx-right-arrow-alt fs-pro-recent-row__arrow'></i>
                        <span class="fs-pro-recent-row__city">COK</span>
                    </div>
                    <div class="fs-pro-recent-row__meta">
                        <span class="fs-pro-recent-row__dates">13 May – 14 May '26</span>
                        <span class="fs-pro-recent-row__pax">2 ADT · Y</span>
                    </div>
                </a>
            </div>

            <div class="fs-pro-aside-card fs-pro-aside-card--quick">
                <nav class="fs-pro-util-links" aria-label="Quick links">
                    <a href="#" class="fs-util-link" @click.prevent>
                        <span class="fs-util-dot fs-util-dot--amber"><i class='bx bx-bell'></i></span>
                        <span class="fs-util-link__text">
                            <span>Notice board</span>
                            <em>3 new</em>
                        </span>
                    </a>
                    <a href="#" class="fs-util-link" @click.prevent>
                        <span class="fs-util-dot fs-util-dot--emerald"><i class='bx bx-wallet'></i></span>
                        <span class="fs-util-link__text">
                            <span>Recharge wallet</span>
                            <em>USD 12,480</em>
                        </span>
                    </a>
                    <a href="#" class="fs-util-link" @click.prevent>
                        <span class="fs-util-dot fs-util-dot--rose"><i class='bx bx-book-open'></i></span>
                        <span class="fs-util-link__text">
                            <span>How to use portal</span>
                            <em>Quick guide</em>
                        </span>
                    </a>
                    <a href="#" class="fs-util-link" @click.prevent>
                        <span class="fs-util-dot fs-util-dot--sky"><i class='bx bx-news'></i></span>
                        <span class="fs-util-link__text">
                            <span>Newsletter</span>
                            <em>May 2026</em>
                        </span>
                    </a>
                </nav>
            </div>
        </aside>

        <div class="fs-pro-promos">
            <a href="#" class="fs-promo fs-promo--gold" @click.prevent>
                <div class="fs-promo__body">
                    <span class="fs-promo__kicker">Exclusive</span>
                    <span class="fs-promo__title">Premium hotel bookings</span>
                    <span class="fs-promo__cta">Open catalogue <i class='bx bx-right-arrow-alt'></i></span>
                </div>
                <i class='bx bx-buildings fs-promo__art'></i>
            </a>
            <a href="#" class="fs-promo fs-promo--ocean" @click.prevent>
                <div class="fs-promo__body">
                    <span class="fs-promo__kicker">New route</span>
                    <span class="fs-promo__title">Al Maktoum &mdash; Saudi Arabia</span>
                    <span class="fs-promo__cta">View schedule <i class='bx bx-right-arrow-alt'></i></span>
                </div>
                <i class='bx bx-trip fs-promo__art'></i>
            </a>
            <a href="#" class="fs-promo fs-promo--night" @click.prevent>
                <div class="fs-promo__body">
                    <span class="fs-promo__kicker">NDC fares</span>
                    <span class="fs-promo__title">Available exclusively online</span>
                    <span class="fs-promo__cta">Learn more <i class='bx bx-right-arrow-alt'></i></span>
                </div>
                <i class='bx bxs-plane-alt fs-promo__art'></i>
            </a>
        </div>
    </form>
</div>
@push('css')
    <style>
        /* ===== Enterprise theme tokens ===== */
        .fs-pro-enterprise {
            --fs-navy: #0c1844;
            --fs-navy-2: #18255a;
            --fs-navy-3: #1f306f;
            --fs-ink: #0f172a;
            --fs-ink-2: #1f2937;
            --fs-slate: #475569;
            --fs-slate-2: #64748b;
            --fs-muted: #94a3b8;
            --fs-line: #e2e8f0;
            --fs-line-soft: #eef2f7;
            --fs-surface: #ffffff;
            --fs-surface-2: #f7f9fc;
            --fs-canvas: #f1f4f9;
            --fs-gold: #c19d56;
            --fs-gold-2: #a98239;
            --fs-emerald: #047857;
            --fs-emerald-soft: #ecfdf5;
            --fs-amber: #b45309;
            --fs-amber-soft: #fffbeb;
            --fs-blue: #1d4ed8;
            --fs-blue-soft: #eff6ff;
            --fs-rose: #be123c;
            --fs-rose-soft: #fff1f2;
            --fs-shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.05);
            --fs-shadow-md: 0 6px 18px rgba(15, 23, 42, 0.06);
            --fs-shadow-lg: 0 24px 60px -28px rgba(15, 23, 42, 0.32);
            --fs-ring: 0 0 0 3px rgba(193, 157, 86, 0.18);

            font-family: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
                Helvetica, Arial, sans-serif;
            color: var(--fs-ink);
            letter-spacing: -0.005em;
        }

        .fs-pro-enterprise.hotel-search-redesign {
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .fs-pro-enterprise *,
        .fs-pro-enterprise *::before,
        .fs-pro-enterprise *::after {
            box-sizing: border-box;
        }

        .fs-pro-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 308px;
            gap: 1.25rem;
            align-items: start;
        }

        @media (max-width: 1100px) {
            .fs-pro-layout {
                grid-template-columns: 1fr;
            }
        }

        /* ===== Primary card ===== */
        .fs-pro-card {
            position: relative;
            border-radius: 18px;
            border: 1px solid var(--fs-line);
            background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
            box-shadow: var(--fs-shadow-lg);
            padding: 1.5rem 1.65rem 1.5rem;
            overflow: hidden;
        }

        .fs-pro-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 3px;
            background: linear-gradient(90deg, var(--fs-navy) 0%, var(--fs-navy-3) 55%, var(--fs-gold) 100%);
            opacity: 0.92;
        }

        .fs-pro-card__head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.35rem;
            padding-bottom: 1.05rem;
            border-bottom: 1px dashed var(--fs-line);
        }

        .fs-pro-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
            font-size: 0.66rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--fs-slate);
            margin-bottom: 0.55rem;
        }

        .fs-pro-eyebrow__dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.18);
            animation: fs-pro-pulse 2.4s ease-in-out infinite;
        }

        @keyframes fs-pro-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.32); }
            50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        }

        .fs-pro-eyebrow__label {
            color: var(--fs-emerald);
        }

        .fs-pro-eyebrow__sep {
            color: var(--fs-muted);
        }

        .fs-pro-eyebrow__meta {
            color: var(--fs-slate);
            font-feature-settings: "tnum" 1, "lnum" 1;
        }

        .fs-pro-card__title {
            margin: 0;
            font-size: 1.7rem;
            line-height: 1.15;
            font-weight: 700;
            color: var(--fs-ink);
            letter-spacing: -0.025em;
        }

        .fs-pro-card__subtitle {
            margin: 0.4rem 0 0;
            font-size: 0.86rem;
            font-weight: 500;
            color: var(--fs-slate-2);
            letter-spacing: 0;
            line-height: 1.5;
        }

        .fs-pro-specials {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .fs-pro-special-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.7rem 0.4rem 0.5rem;
            border-radius: 10px;
            border: 1px solid var(--fs-line);
            background: var(--fs-surface);
            font-weight: 600;
            font-size: 0.74rem;
            color: var(--fs-ink-2);
            text-decoration: none;
            cursor: pointer;
            transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.12s ease;
            box-shadow: var(--fs-shadow-sm);
        }

        .fs-pro-special-chip:hover {
            border-color: var(--fs-navy-3);
            box-shadow: var(--fs-shadow-md);
            transform: translateY(-1px);
        }

        .fs-pro-special-chip__icon {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .fs-pro-special-chip__icon--a2a {
            background: linear-gradient(140deg, #fee2e2 0%, #fecaca 100%);
            color: #b91c1c;
        }

        .fs-pro-special-chip__icon--akbar {
            background: linear-gradient(140deg, #dbeafe 0%, #bfdbfe 100%);
            color: var(--fs-blue);
        }

        .fs-pro-special-chip__text {
            letter-spacing: -0.005em;
        }

        .fs-pro-badge-new {
            font-size: 0.6rem;
            font-weight: 700;
            padding: 0.13rem 0.4rem;
            border-radius: 999px;
            background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
            line-height: 1.2;
            letter-spacing: 0.04em;
            box-shadow: 0 1px 3px rgba(220, 38, 38, 0.35);
        }

        .fs-pro-badge-new--blue {
            background: linear-gradient(180deg, #2563eb 0%, var(--fs-blue) 100%);
            box-shadow: 0 1px 3px rgba(29, 78, 216, 0.35);
        }

        .fs-pro-enterprise .mono {
            font-variant-numeric: tabular-nums;
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .fs-pro-enterprise .options-dropdown-wrapper--from {
            left: auto;
            right: auto;
        }

        /* ===== Controls row (trip-type + meta) ===== */
        .fs-pro-controls-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.05rem;
        }

        .fs-pro-trip-types {
            margin-bottom: 0 !important;
            gap: 0 !important;
            background: var(--fs-canvas);
            padding: 4px;
            border-radius: 11px;
            border: 1px solid var(--fs-line);
        }

        .fs-pro-meta-row {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .fs-pro-meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.62rem;
            font-size: 0.74rem;
            font-weight: 600;
            color: var(--fs-ink-2);
            background: var(--fs-surface);
            border: 1px solid var(--fs-line);
            border-radius: 9px;
            cursor: pointer;
        }

        .fs-pro-meta-chip i {
            color: var(--fs-slate);
        }

        .fs-pro-meta-chip__chevron {
            font-size: 0.85rem;
            opacity: 0.55;
        }

        .fs-pro-meta-link {
            display: inline-flex;
            align-items: center;
            gap: 0.32rem;
            padding: 0.4rem 0.5rem;
            font-size: 0.74rem;
            font-weight: 600;
            color: var(--fs-slate);
            text-decoration: none;
            border-radius: 8px;
            transition: color 0.15s ease, background 0.15s ease;
        }

        .fs-pro-meta-link:hover {
            color: var(--fs-navy-3);
            background: var(--fs-canvas);
        }

        .fs-pro-meta-link i {
            font-size: 1rem;
        }

        /* ===== Route ===== */
        .fs-pro-route-sheet {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            margin-bottom: 1.05rem;
        }

        .fs-pro-route-pair {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 0;
            align-items: stretch;
            border-radius: 14px;
            background: var(--fs-surface);
            border: 1px solid var(--fs-line);
            box-shadow: var(--fs-shadow-sm);
            overflow: visible;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .fs-pro-route-pair:hover {
            border-color: #c8d1e0;
            box-shadow: var(--fs-shadow-md);
        }

        @media (max-width: 640px) {
            .fs-pro-route-pair {
                grid-template-columns: 1fr;
            }

            .fs-pro-swap-wrap {
                left: 50% !important;
                top: 100% !important;
                transform: translate(-50%, -50%) rotate(90deg) !important;
            }
        }

        .fs-pro-route-field {
            position: relative;
            overflow: visible;
            min-width: 0;
        }

        .fs-pro-route-field--from {
            border-right: 1px solid var(--fs-line);
        }

        .fs-pro-route-field__shell {
            background: transparent;
            border: none;
            transition: background-color 0.18s ease;
            min-height: 92px;
            display: flex;
            align-items: stretch;
        }

        .fs-pro-route-field__shell:hover {
            background: var(--fs-surface-2);
        }

        .fs-pro-route-field--from .fs-pro-route-field__shell {
            border-radius: 14px 0 0 14px;
        }

        .fs-pro-route-field--to .fs-pro-route-field__shell {
            border-radius: 0 14px 14px 0;
        }

        .fs-pro-route-field .hs-field__inner {
            cursor: pointer;
            padding: 0.85rem 1rem !important;
            width: 100%;
        }

        .fs-pro-route-field__label {
            font-weight: 700;
            font-size: 0.66rem !important;
            letter-spacing: 0.16em !important;
            text-transform: uppercase;
            color: var(--fs-muted) !important;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .fs-pro-route-chosen {
            margin-top: 0.32rem;
        }

        .fs-pro-route-chosen__city {
            display: block;
            font-size: 1.32rem !important;
            font-weight: 700 !important;
            color: var(--fs-ink);
            letter-spacing: -0.025em;
            line-height: 1.15;
        }

        .fs-pro-route-chosen__airport {
            margin-top: 0.2rem;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--fs-slate-2);
            line-height: 1.4;
            display: flex;
            align-items: center;
            gap: 0.32rem;
            flex-wrap: wrap;
        }

        .fs-pro-route-chosen__airport .mono {
            color: var(--fs-navy-3);
            font-weight: 700;
            font-size: 0.72rem;
            background: var(--fs-canvas);
            padding: 0.12rem 0.42rem;
            border-radius: 5px;
            border: 1px solid var(--fs-line);
            letter-spacing: 0.02em;
        }

        .fs-pro-route-input {
            padding-left: 0 !important;
            font-weight: 600 !important;
            background: transparent !important;
        }

        .fs-pro-route-inline-icon {
            opacity: 0.3;
            font-size: 1.2rem;
            color: var(--fs-navy-3);
        }

        .fs-pro-swap-wrap {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 4;
        }

        .fs-pro-swap-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid var(--fs-line);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            color: var(--fs-navy);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1), 0 1px 0 #fff inset;
            font-size: 1.25rem;
            transition: transform 0.25s cubic-bezier(.2, .8, .2, 1), box-shadow 0.2s ease, color 0.18s ease;
        }

        .fs-pro-swap-btn:hover {
            color: var(--fs-gold-2);
            transform: rotate(180deg);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.14), 0 1px 0 #fff inset, var(--fs-ring);
        }

        .fs-pro-swap-btn:active {
            transform: rotate(180deg) scale(0.96);
        }

        /* ===== Dates ===== */
        .fs-pro-date-pair {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0;
            background: var(--fs-surface);
            border: 1px solid var(--fs-line);
            border-radius: 14px;
            box-shadow: var(--fs-shadow-sm);
            overflow: visible;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .fs-pro-date-pair:hover {
            border-color: #c8d1e0;
            box-shadow: var(--fs-shadow-md);
        }

        .fs-pro-date-cell {
            position: relative;
            min-width: 0;
        }

        .fs-pro-date-cell + .fs-pro-date-cell {
            border-left: 1px solid var(--fs-line);
        }

        .fs-pro-date-cell .hs-field__inner {
            position: relative;
            border-radius: 0;
            border: none !important;
            background: transparent;
            cursor: pointer;
            min-height: 92px;
            padding: 0.85rem 1rem !important;
            transition: background 0.18s ease;
        }

        .fs-pro-date-cell:first-child .hs-field__inner {
            border-radius: 14px 0 0 14px;
        }

        .fs-pro-date-cell:last-child .hs-field__inner {
            border-radius: 0 14px 14px 0;
        }

        .fs-pro-date-cell:hover .hs-field__inner {
            background: var(--fs-surface-2);
        }

        .fs-pro-date-label {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem;
            font-weight: 700 !important;
            font-size: 0.66rem !important;
            letter-spacing: 0.16em !important;
            text-transform: uppercase;
            color: var(--fs-muted) !important;
        }

        .fs-pro-date-label i {
            font-size: 0.9rem;
            color: var(--fs-slate);
        }

        .fs-pro-date-chevron {
            font-size: 10px !important;
            opacity: 0.45;
            margin-left: 0 !important;
            margin-top: 0 !important;
        }

        .fs-pro-enterprise .hs-date-display {
            display: flex;
            align-items: baseline;
            gap: 0.55rem;
            margin-top: 0.3rem;
        }

        .fs-pro-enterprise .hs-date-display__day {
            font-variant-numeric: tabular-nums;
            font-size: 2rem !important;
            font-weight: 700 !important;
            color: var(--fs-ink) !important;
            line-height: 1 !important;
            letter-spacing: -0.04em !important;
        }

        .fs-pro-enterprise .hs-date-display__meta {
            display: flex;
            flex-direction: column;
            gap: 1px;
            padding-bottom: 0.05rem;
        }

        .fs-pro-enterprise .hs-date-display__month {
            font-size: 0.84rem !important;
            font-weight: 700 !important;
            color: var(--fs-ink-2) !important;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            line-height: 1.1;
        }

        .fs-pro-enterprise .hs-date-display__weekday {
            font-size: 0.72rem !important;
            font-weight: 500 !important;
            color: var(--fs-slate-2) !important;
            line-height: 1.2;
        }

        .fs-pro-return-inner {
            padding-right: 2.2rem !important;
        }

        .fs-pro-return-cell--soft .hs-field__inner {
            background: repeating-linear-gradient(
                135deg,
                #fafbfc 0,
                #fafbfc 6px,
                #f5f7fa 6px,
                #f5f7fa 12px
            );
        }

        .fs-pro-return-cell--soft .hs-date-display__day,
        .fs-pro-return-cell--soft .hs-date-display__month {
            color: var(--fs-muted) !important;
        }

        .fs-pro-return-clear {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 26px;
            height: 26px;
            padding: 0;
            border: 1px solid var(--fs-line);
            border-radius: 50%;
            background: #fff;
            color: var(--fs-slate-2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            box-shadow: var(--fs-shadow-sm);
            transition: color 0.15s ease, background 0.15s ease, border-color 0.15s ease;
        }

        .fs-pro-return-clear:hover {
            color: var(--fs-rose);
            border-color: var(--fs-rose);
        }

        /* ===== Footer (pax / cabin / airlines / submit) ===== */
        .fs-pro-footer {
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
            margin-top: 1.15rem;
            padding-top: 1.15rem;
            border-top: 1px solid var(--fs-line);
        }

        .fs-pro-pax-cabin-row {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr) minmax(0, 1fr);
            gap: 0.65rem;
            align-items: stretch;
        }

        @media (max-width: 720px) {
            .fs-pro-pax-cabin-row {
                grid-template-columns: 1fr;
            }
        }

        .fs-pro-travellers {
            min-width: 0;
        }

        .fs-pro-travellers__inner.hs-field__inner {
            cursor: pointer;
            border-radius: 11px !important;
            border: 1px solid var(--fs-line) !important;
            background: #fff !important;
            padding: 0.55rem 0.85rem !important;
            min-height: 56px;
            box-shadow: var(--fs-shadow-sm);
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .fs-pro-travellers__inner.hs-field__inner:hover {
            border-color: #c8d1e0 !important;
            box-shadow: var(--fs-shadow-md);
        }

        .fs-pro-label {
            font-weight: 700 !important;
            letter-spacing: 0.14em !important;
            font-size: 0.62rem !important;
            text-transform: uppercase;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.3rem;
            color: var(--fs-muted) !important;
        }

        .fs-pro-chevron {
            font-size: 11px !important;
            opacity: 0.45;
            margin-left: 0 !important;
        }

        .fs-pro-pax-line {
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            color: var(--fs-ink) !important;
            margin-top: 0.2rem;
            display: block;
        }

        .fs-pro-select-group {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .fs-pro-select-group__label {
            font-weight: 700;
            letter-spacing: 0.14em;
            font-size: 0.62rem;
            text-transform: uppercase;
            color: var(--fs-muted);
            margin-bottom: 0.32rem;
            padding: 0 0.2rem;
        }

        .fs-pro-select-wrap {
            position: relative;
            border-radius: 11px;
            border: 1px solid var(--fs-line);
            background: #fff;
            overflow: visible;
            display: flex;
            align-items: center;
            min-height: 56px;
            box-shadow: var(--fs-shadow-sm);
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .fs-pro-select-wrap:hover,
        .fs-pro-select-wrap:focus-within {
            border-color: #c8d1e0;
            box-shadow: var(--fs-shadow-md);
        }

        .fs-pro-select-wrap__icon {
            position: absolute;
            left: 14px;
            font-size: 1.15rem;
            color: var(--fs-navy-3);
            opacity: 0.5;
            pointer-events: none;
        }

        .fs-pro-select-el {
            appearance: none;
            width: 100%;
            padding: 0.65rem 2.2rem 0.65rem 2.75rem !important;
            border: none;
            margin: 0;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600 !important;
            color: var(--fs-ink);
            outline: none;
            background: transparent;
        }

        .fs-pro-select-el-chevron {
            position: absolute;
            right: 14px;
            font-size: 1rem;
            color: var(--fs-muted);
            pointer-events: none;
        }

        /* Airline preference */
        .fs-pro-airline-pref {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding: 0.85rem 0.95rem;
            background: linear-gradient(180deg, #fafbfc 0%, #f6f8fb 100%);
            border: 1px solid var(--fs-line);
            border-radius: 12px;
        }

        .fs-pro-airline-pref__label {
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--fs-slate);
            padding-right: 0.85rem;
            border-right: 1px dashed var(--fs-line);
        }

        .fs-pro-airline-pref__label i {
            color: var(--fs-navy-3);
            font-size: 1rem;
        }

        .fs-pro-airline-pref__toggles {
            display: flex;
            flex-wrap: wrap;
            gap: 0.42rem;
            flex: 1;
        }

        .fs-air-chip {
            border: 1px solid var(--fs-line);
            background: #fff;
            color: var(--fs-slate);
            font-family: inherit;
            font-size: 0.78rem;
            font-weight: 600 !important;
            padding: 0.42rem 0.85rem !important;
            border-radius: 8px !important;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            cursor: pointer;
            transition: border-color 0.15s ease, background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
            box-shadow: var(--fs-shadow-sm);
        }

        .fs-air-chip__indicator {
            width: 12px;
            height: 12px;
            border-radius: 4px;
            border: 1.5px solid #cbd5e1;
            background: #fff;
            position: relative;
            flex-shrink: 0;
            transition: inherit;
        }

        .fs-air-chip:hover {
            border-color: var(--fs-navy-3);
            color: var(--fs-navy);
        }

        .fs-air-chip.is-active {
            border-color: var(--fs-navy-3);
            background: linear-gradient(180deg, #f2f6ff 0%, #e9efff 100%);
            color: var(--fs-navy);
            box-shadow: inset 0 0 0 1px rgba(31, 48, 111, 0.08), var(--fs-shadow-sm);
        }

        .fs-air-chip.is-active .fs-air-chip__indicator {
            border-color: var(--fs-navy-3);
            background: var(--fs-navy-3);
        }

        .fs-air-chip.is-active .fs-air-chip__indicator::after {
            content: "";
            position: absolute;
            left: 3px;
            top: 0px;
            width: 3px;
            height: 7px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        /* Actions footer */
        .fs-pro-actions-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .fs-pro-actions-footer .fs-search-filters {
            margin-bottom: 0 !important;
            gap: 0.42rem !important;
        }

        .fs-pro-search-btn {
            border-radius: 11px !important;
            padding: 0.85rem 1.45rem !important;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.92rem !important;
            font-weight: 700 !important;
            letter-spacing: -0.005em !important;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            color: #fff !important;
            background: linear-gradient(180deg, var(--fs-navy-2) 0%, var(--fs-navy) 100%) !important;
            box-shadow:
                0 12px 28px -8px rgba(12, 24, 68, 0.45),
                inset 0 1px 0 rgba(255, 255, 255, 0.12),
                inset 0 -1px 0 rgba(0, 0, 0, 0.18) !important;
            transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
            position: relative;
            overflow: hidden;
        }

        .fs-pro-search-btn::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(193, 157, 86, 0.25) 100%);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .fs-pro-search-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow:
                0 16px 32px -10px rgba(12, 24, 68, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.18),
                inset 0 -1px 0 rgba(0, 0, 0, 0.2) !important;
        }

        .fs-pro-search-btn:hover:not(:disabled)::before {
            opacity: 1;
        }

        .fs-pro-search-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
            background: linear-gradient(180deg, #6b7691 0%, #545d75 100%) !important;
            box-shadow: var(--fs-shadow-sm) !important;
        }

        .fs-pro-search-btn__arrow {
            font-size: 1.2rem;
            transition: transform 0.18s ease;
            position: relative;
            z-index: 1;
        }

        .fs-pro-search-btn:hover:not(:disabled) .fs-pro-search-btn__arrow {
            transform: translateX(2px);
        }

        /* Trust strip */
        .fs-pro-trust-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1.2rem;
            padding-top: 0.6rem;
            border-top: 1px dashed var(--fs-line);
        }

        .fs-pro-trust-item {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.72rem;
            font-weight: 500;
            color: var(--fs-slate-2);
        }

        .fs-pro-trust-item i {
            color: var(--fs-emerald);
            font-size: 1rem;
        }

        /* ===== Aside ===== */
        .fs-pro-aside {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .fs-pro-aside-card {
            background: #fff;
            border: 1px solid var(--fs-line);
            border-radius: 14px;
            padding: 0.95rem 0.95rem 1rem;
            box-shadow: var(--fs-shadow-sm);
        }

        .fs-pro-aside-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.65rem;
        }

        .fs-pro-aside-card__label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--fs-muted);
        }

        .fs-pro-aside-card__action {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--fs-slate-2);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .fs-pro-aside-card__action:hover {
            color: var(--fs-navy-3);
        }

        .fs-pro-tile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.55rem;
        }

        .fs-pro-tile {
            border-radius: 11px;
            padding: 0.75rem 0.7rem !important;
            text-decoration: none !important;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0.55rem;
            color: var(--fs-ink) !important;
            line-height: 1.25;
            cursor: pointer;
            border: 1px solid var(--fs-line);
            background: var(--fs-surface-2);
            position: relative;
            transition: transform 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
        }

        .fs-pro-tile:hover {
            transform: translateY(-1px);
            border-color: var(--fs-navy-3);
            background: #fff;
            box-shadow: var(--fs-shadow-md);
        }

        .fs-pro-tile__icon {
            align-self: flex-start;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem !important;
            background: #fff;
            border: 1px solid var(--fs-line);
        }

        .fs-pro-tile__meta {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .fs-pro-tile__title {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--fs-ink);
            letter-spacing: -0.005em;
        }

        .fs-pro-tile__hint {
            font-size: 0.66rem;
            font-weight: 500;
            color: var(--fs-slate-2);
            letter-spacing: 0;
        }

        .fs-pro-tile--offline .fs-pro-tile__icon { color: var(--fs-amber); background: var(--fs-amber-soft); border-color: rgba(180, 83, 9, 0.18); }
        .fs-pro-tile--import .fs-pro-tile__icon { color: var(--fs-blue); background: var(--fs-blue-soft); border-color: rgba(29, 78, 216, 0.18); }
        .fs-pro-tile--hold .fs-pro-tile__icon { color: var(--fs-emerald); background: var(--fs-emerald-soft); border-color: rgba(4, 120, 87, 0.18); }
        .fs-pro-tile--calendar .fs-pro-tile__icon { color: var(--fs-rose); background: var(--fs-rose-soft); border-color: rgba(190, 18, 60, 0.18); }

        /* Recent panel rows */
        .fs-pro-recent-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 0.5rem;
            border-radius: 8px;
            text-decoration: none !important;
            color: var(--fs-ink);
            transition: background 0.15s ease;
            cursor: pointer;
        }

        .fs-pro-recent-row + .fs-pro-recent-row {
            border-top: 1px dashed var(--fs-line);
        }

        .fs-pro-recent-row:hover {
            background: var(--fs-canvas);
        }

        .fs-pro-recent-row__route {
            display: inline-flex;
            align-items: center;
            gap: 0.32rem;
            font-weight: 700;
            font-size: 0.84rem;
            letter-spacing: 0.02em;
            color: var(--fs-ink);
            font-variant-numeric: tabular-nums;
        }

        .fs-pro-recent-row__city {
            background: var(--fs-canvas);
            border: 1px solid var(--fs-line);
            padding: 0.08rem 0.4rem;
            border-radius: 5px;
            font-size: 0.74rem;
            font-weight: 700;
            color: var(--fs-navy-3);
        }

        .fs-pro-recent-row__arrow {
            color: var(--fs-muted);
            font-size: 1rem;
        }

        .fs-pro-recent-row__meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.05rem;
            font-feature-settings: "tnum" 1, "lnum" 1;
        }

        .fs-pro-recent-row__dates {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--fs-slate);
        }

        .fs-pro-recent-row__pax {
            font-size: 0.66rem;
            font-weight: 600;
            color: var(--fs-muted);
            letter-spacing: 0.04em;
        }

        /* Quick utility links */
        .fs-pro-aside-card--quick {
            padding: 0.55rem 0.6rem;
        }

        .fs-pro-util-links {
            display: flex;
            flex-direction: column;
            gap: 0 !important;
        }

        .fs-util-link {
            display: flex !important;
            align-items: center !important;
            gap: 0.62rem !important;
            padding: 0.6rem 0.5rem !important;
            border-radius: 9px !important;
            margin: 0 !important;
            cursor: pointer;
            line-height: 1.25 !important;
            color: var(--fs-ink);
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            transition: background 0.14s ease;
            border: 0;
            background: transparent;
        }

        .fs-util-link + .fs-util-link {
            border-top: 1px dashed var(--fs-line);
        }

        .fs-util-link:hover {
            background: var(--fs-canvas);
        }

        .fs-util-link__text {
            display: flex;
            flex-direction: column;
            gap: 0.05rem;
            flex: 1;
        }

        .fs-util-link__text em {
            font-style: normal;
            font-size: 0.68rem;
            font-weight: 500;
            color: var(--fs-slate-2);
        }

        .fs-util-dot {
            width: 30px !important;
            height: 30px !important;
            flex-shrink: 0;
            border-radius: 8px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1rem !important;
            border: 1px solid transparent;
        }

        .fs-util-dot--amber {
            background: var(--fs-amber-soft);
            color: var(--fs-amber);
            border-color: rgba(180, 83, 9, 0.18);
        }

        .fs-util-dot--emerald {
            background: var(--fs-emerald-soft);
            color: var(--fs-emerald);
            border-color: rgba(4, 120, 87, 0.18);
        }

        .fs-util-dot--rose {
            background: var(--fs-rose-soft);
            color: var(--fs-rose);
            border-color: rgba(190, 18, 60, 0.18);
        }

        .fs-util-dot--sky {
            background: var(--fs-blue-soft);
            color: var(--fs-blue);
            border-color: rgba(29, 78, 216, 0.18);
        }

        /* ===== Promotions strip ===== */
        .fs-pro-promos {
            grid-column: 1 / -1;
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-top: 0.25rem;
        }

        .fs-promo {
            position: relative;
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            text-decoration: none !important;
            border-radius: 14px;
            padding: 1.1rem 1.15rem !important;
            cursor: pointer;
            min-height: 96px;
            color: inherit;
            box-shadow: var(--fs-shadow-md);
            border: 1px solid var(--fs-line);
            overflow: hidden;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            isolation: isolate;
        }

        .fs-promo:hover {
            transform: translateY(-2px);
            box-shadow: var(--fs-shadow-lg);
        }

        .fs-promo__body {
            display: flex;
            flex-direction: column;
            gap: 0.18rem;
            z-index: 1;
        }

        .fs-promo__kicker {
            font-size: 0.62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            opacity: 0.78;
        }

        .fs-promo__title {
            display: block !important;
            font-size: 1.02rem;
            font-weight: 700;
            letter-spacing: -0.015em;
            line-height: 1.25;
        }

        .fs-promo__cta {
            display: inline-flex;
            align-items: center;
            gap: 0.32rem;
            margin-top: 0.5rem;
            font-size: 0.74rem;
            font-weight: 600;
            opacity: 0.88;
            letter-spacing: 0;
            text-transform: none;
        }

        .fs-promo__cta i {
            transition: transform 0.18s ease;
        }

        .fs-promo:hover .fs-promo__cta i {
            transform: translateX(3px);
        }

        .fs-promo__art {
            font-size: 4rem;
            line-height: 1;
            opacity: 0.14;
            position: absolute;
            right: -0.5rem;
            bottom: -0.6rem;
            transform: rotate(-12deg);
            z-index: 0;
        }

        .fs-promo--gold {
            background: linear-gradient(135deg, #1f1a12 0%, #2a2317 55%, #3a2f1b 100%);
            color: #f4d8a3;
            border-color: rgba(193, 157, 86, 0.35);
        }

        .fs-promo--gold .fs-promo__kicker {
            color: var(--fs-gold);
        }

        .fs-promo--gold .fs-promo__art {
            color: var(--fs-gold);
        }

        .fs-promo--ocean {
            background: linear-gradient(135deg, #0c1844 0%, #15246a 60%, #1d2f86 100%);
            color: #dbe4ff;
            border-color: rgba(29, 78, 216, 0.45);
        }

        .fs-promo--ocean .fs-promo__kicker {
            color: #93c5fd;
        }

        .fs-promo--ocean .fs-promo__art {
            color: #60a5fa;
        }

        .fs-promo--night {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            border-color: rgba(148, 163, 184, 0.22);
        }

        .fs-promo--night .fs-promo__kicker {
            color: var(--fs-gold);
        }

        .fs-promo--night .fs-promo__art {
            color: #94a3b8;
        }

        /* ===== Trip type segmented control ===== */
        .fs-pro-enterprise .fs-trip-types {
            display: inline-flex;
            flex-wrap: nowrap;
        }

        .fs-pro-enterprise .fs-trip-types__item {
            border: none;
            background: transparent;
            color: var(--fs-slate);
            border-radius: 8px;
            padding: 0.5rem 0.95rem;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            min-width: 0;
            cursor: pointer;
            transition: color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease, transform 0.12s ease;
            position: relative;
            letter-spacing: -0.005em;
        }

        .fs-pro-enterprise .fs-trip-types__item:hover {
            color: var(--fs-navy);
            background: rgba(15, 23, 42, 0.035);
            box-shadow: none;
        }

        .fs-pro-enterprise .fs-trip-types__item.active {
            color: var(--fs-navy);
            background: #fff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1), 0 0 0 1px rgba(15, 23, 42, 0.04) inset;
        }

        .fs-pro-enterprise .fs-trip-types__item.active::after {
            content: "";
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: -1px;
            width: 18px;
            height: 2px;
            background: var(--fs-gold);
            border-radius: 999px;
        }

        .fs-trip-types__icon {
            font-size: 0.95rem;
            opacity: 0.7;
        }

        .fs-pro-enterprise .fs-trip-types__item.active .fs-trip-types__icon {
            opacity: 1;
            color: var(--fs-gold-2);
        }

        /* Legacy dot (no longer used but kept hidden if rendered) */
        .fs-trip-types__dot { display: none; }

        /* ===== Quick filter chips (Direct / Nearby / Student) ===== */
        .fs-search-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.42rem;
            margin-bottom: 0 !important;
        }

        .fs-pro-enterprise .fs-filter-chip {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-height: 38px;
            padding: 0.45rem 0.85rem;
            border: 1px solid var(--fs-line);
            border-radius: 9px;
            background: #fff;
            color: var(--fs-slate);
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: var(--fs-shadow-sm);
            transition: border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .fs-pro-enterprise .fs-filter-chip input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .fs-pro-enterprise .fs-filter-chip:hover {
            border-color: var(--fs-navy-3);
            color: var(--fs-navy);
            box-shadow: var(--fs-shadow-md);
        }

        .fs-pro-enterprise .fs-filter-chip.active {
            border-color: var(--fs-navy-3);
            background: linear-gradient(180deg, #f2f6ff 0%, #e9efff 100%);
            color: var(--fs-navy);
            box-shadow: inset 0 0 0 1px rgba(31, 48, 111, 0.08);
        }

        .fs-pro-enterprise .fs-filter-chip__box {
            width: 14px;
            height: 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 4px;
            background: #fff;
            position: relative;
            flex-shrink: 0;
            transition: inherit;
        }

        .fs-pro-enterprise .fs-filter-chip.active .fs-filter-chip__box {
            border-color: var(--fs-navy-3);
            background: var(--fs-navy-3);
            box-shadow: 0 0 0 3px rgba(31, 48, 111, 0.12);
        }

        .fs-pro-enterprise .fs-filter-chip.active .fs-filter-chip__box::after {
            content: "";
            position: absolute;
            left: 3px;
            top: 0px;
            width: 3px;
            height: 7px;
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
            color: var(--fs-slate-2);
            font-weight: 600;
            max-width: 150px;
        }

        /* ===== Multi-city ===== */
        .fs-multicity {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            margin-bottom: 0.95rem;
        }

        .fs-multicity__row {
            border: 1px solid var(--fs-line);
            border-radius: 12px;
            background: #fff;
            box-shadow: var(--fs-shadow-sm);
            overflow: visible;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .fs-multicity__row:hover {
            border-color: #c8d1e0;
            box-shadow: var(--fs-shadow-md);
        }

        .fs-multicity__grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.2fr) minmax(160px, 0.9fr) 48px;
            align-items: stretch;
        }

        .fs-multicity__field {
            border-right: 1px solid var(--fs-line);
        }

        .fs-multicity__field .hs-field__label,
        .fs-multicity__date .hs-field__label {
            font-weight: 700 !important;
            font-size: 0.66rem !important;
            letter-spacing: 0.16em !important;
            color: var(--fs-muted) !important;
            text-transform: uppercase;
        }

        .fs-multicity__field .hs-field__inner {
            padding: 0.85rem 1rem !important;
        }

        .fs-multicity__date {
            border-right: 1px solid var(--fs-line);
            min-width: 0;
        }

        .fs-multicity__date .hs-field__inner {
            padding: 0.85rem 1rem;
            cursor: pointer;
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
            color: var(--fs-slate-2);
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: color 0.15s ease, background 0.15s ease;
        }

        .fs-multicity__remove:hover {
            color: var(--fs-rose);
            background: var(--fs-rose-soft);
        }

        .fs-multicity__actions {
            display: flex;
            justify-content: flex-end;
        }

        .fs-add-city-btn {
            border: 1px dashed var(--fs-line);
            background: #fff;
            color: var(--fs-navy);
            border-radius: 10px;
            padding: 0.55rem 0.95rem;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
        }

        .fs-add-city-btn:hover:not(:disabled) {
            border-color: var(--fs-navy-3);
            background: var(--fs-canvas);
        }

        .fs-add-city-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        @media (max-width: 991px) {
            .fs-multicity__grid {
                grid-template-columns: 1fr;
            }

            .fs-multicity__field,
            .fs-multicity__date {
                border-right: none;
                border-bottom: 1px solid var(--fs-line);
            }

            .fs-multicity__remove {
                min-height: 46px;
            }
        }

        /* ===== Dropdown polish ===== */
        .fs-pro-enterprise .options-dropdown {
            border-radius: 12px;
            border: 1px solid var(--fs-line);
            box-shadow: var(--fs-shadow-lg);
        }

        .fs-pro-enterprise .options-dropdown__header {
            background: var(--fs-surface-2);
            border-bottom: 1px solid var(--fs-line);
            padding: 0.5rem 0.85rem;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--fs-muted);
        }

        .fs-pro-enterprise .options-dropdown-list__item {
            padding: 0.65rem 0.85rem;
            cursor: pointer;
            transition: background 0.12s ease;
            border-radius: 0;
        }

        .fs-pro-enterprise .options-dropdown-list__item:hover {
            background: var(--fs-canvas);
        }

        .fs-pro-enterprise .options-dropdown-list__item .name {
            font-weight: 600;
            font-size: 0.82rem;
            color: var(--fs-ink);
        }

        .fs-pro-enterprise .options-dropdown-list__item .sub-text {
            font-size: 0.72rem;
            color: var(--fs-slate-2);
            font-weight: 500;
        }

        .fs-pro-enterprise .quantity-counter__btn {
            border: 1px solid var(--fs-line);
            background: #fff;
            color: var(--fs-ink);
            transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease;
        }

        .fs-pro-enterprise .quantity-counter__btn:hover {
            border-color: var(--fs-navy-3);
            color: var(--fs-navy);
        }

        .fs-pro-enterprise .quantity-counter__btn--quantity {
            color: var(--fs-ink);
            font-weight: 700;
            background: var(--fs-canvas);
        }
    </style>
@endpush
