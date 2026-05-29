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
                            <span class="fs-pro-special-chip__badge-row">
                                <span class="fs-pro-badge-new">NEW</span>
                            </span>
                            <span class="fs-pro-special-chip__main">
                                <span class="fs-pro-special-chip__icon fs-pro-special-chip__icon--a2a"><i class='bx bxs-plane'></i></span>
                                <span class="fs-pro-special-chip__text">A2A Special Fare</span>
                            </span>
                        </a>
                        <a href="#" class="fs-pro-special-chip" @click.prevent>
                            <span class="fs-pro-special-chip__badge-row">
                                <span class="fs-pro-badge-new fs-pro-badge-new--blue">NEW</span>
                            </span>
                            <span class="fs-pro-special-chip__main">
                                <span class="fs-pro-special-chip__icon fs-pro-special-chip__icon--ata"><i class='bx bxs-plane-alt'></i></span>
                                <span class="fs-pro-special-chip__text">ATA Special Fare</span>
                            </span>
                        </a>
                    </div>
                </header>

                <div class="fs-pro-controls-row">
                    <div class="fs-trip-types fs-pro-trip-types" role="tablist" aria-label="Trip type">
                        <button type="button" class="fs-trip-types__item" role="tab"
                            :class="{ active: tripType === 'one_way' }" :aria-selected="tripType === 'one_way'"
                            @click="setTripType('one_way')">
                            One Way
                        </button>
                        <button type="button" class="fs-trip-types__item" role="tab"
                            :class="{ active: tripType === 'round_trip' }"
                            :aria-selected="tripType === 'round_trip'" @click="setTripType('round_trip')">
                            Round Trip
                        </button>
                        <button type="button" class="fs-trip-types__item" role="tab"
                            :class="{ active: tripType === 'multi_city' }"
                            :aria-selected="tripType === 'multi_city'" @click="setTripType('multi_city')">
                            Multi City
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
                                        <span class="fs-pro-route-field__label hs-field__label">From</span>
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
                                        <input type="hidden" name="from" :value="resolveLocationCode(selectedFrom)">
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
                                                        <span class="sub-text">@{{ airport.city }}, @{{ airport.country }}<template v-if="airport.cityCode && airport.cityCode !== airport.code"> · Metro @{{ airport.cityCode }}</template></span>
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
                                        <span class="fs-pro-route-field__label hs-field__label">To</span>
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
                                        <input type="hidden" name="to" :value="resolveLocationCode(selectedTo)">
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
                                                        <span class="sub-text">@{{ airport.city }}, @{{ airport.country }}<template v-if="airport.cityCode && airport.cityCode !== airport.code"> · Metro @{{ airport.cityCode }}</template></span>
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

                        <div class="fs-pro-date-pair" id="flight-date-pair-wrap">
                            <div class="fs-pro-date-cell hs-field hs-field--date" id="flight-departure-box">
                                <div class="hs-field__inner fs-pro-date-inner">
                                    <div class="hs-field__label fs-pro-date-label"><i class='bx bx-calendar'></i><span>Depart</span>
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
                                    <div class="hs-field__label fs-pro-date-label"><i class='bx bx-calendar'></i><span>Return</span>
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
                                        :value="resolveLocationCode(segment.selectedFrom)">
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
                                                    <span class="sub-text">@{{ airport.city }}, @{{ airport.country }}<template v-if="airport.cityCode && airport.cityCode !== airport.code"> · Metro @{{ airport.cityCode }}</template></span>
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
                                        :value="resolveLocationCode(segment.selectedTo)">
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
                                                    <span class="sub-text">@{{ airport.city }}, @{{ airport.country }}<template v-if="airport.cityCode && airport.cityCode !== airport.code"> · Metro @{{ airport.cityCode }}</template></span>
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
                                <div class="hs-field__label fs-pro-label">Travellers
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

                        <div class="fs-pro-select-group fs-pro-select-group--cabin" ref="onwardCabinRef">
                            <span class="fs-pro-select-group__label">Onward Cabin Class</span>
                            <button type="button" class="fs-pro-cabin-trigger" @click.stop="toggleOnwardCabin">
                                <span class="fs-pro-cabin-trigger__text">@{{ onwardCabin }}</span>
                                <i class='bx bx-chevron-down fs-pro-cabin-trigger__chev'></i>
                            </button>
                            <div class="fs-pro-cabin-dropdown" :class="{ 'is-open': onwardCabinOpen }">
                                <button type="button" class="fs-pro-cabin-option"
                                    :class="{ 'is-active': opt === onwardCabin }"
                                    v-for="opt in cabinOptions" :key="'onward-' + opt"
                                    @click.stop="pickOnwardCabin(opt)">@{{ opt }}</button>
                            </div>
                        </div>

                        <div class="fs-pro-select-group fs-pro-select-group--cabin" ref="returnCabinRef"
                            v-show="tripType === 'round_trip'">
                            <span class="fs-pro-select-group__label">Return Cabin Class</span>
                            <button type="button" class="fs-pro-cabin-trigger" @click.stop="toggleReturnCabin">
                                <span class="fs-pro-cabin-trigger__text">@{{ returnCabin }}</span>
                                <i class='bx bx-chevron-down fs-pro-cabin-trigger__chev'></i>
                            </button>
                            <div class="fs-pro-cabin-dropdown" :class="{ 'is-open': returnCabinOpen }">
                                <button type="button" class="fs-pro-cabin-option"
                                    :class="{ 'is-active': opt === returnCabin }"
                                    v-for="opt in cabinOptions" :key="'ret-' + opt"
                                    @click.stop="pickReturnCabin(opt)">@{{ opt }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="fs-pro-airline-pref">
                        <div class="fs-pro-airline-pref__label">
                            <span>Airline preference</span>
                        </div>
                        <div class="fs-pro-airline-pref__toggles">
                            <button type="button" class="fs-air-chip fs-air-chip--all"
                                :class="{ 'is-active': airlineAll }"
                                @click.prevent="airlineAll = !airlineAll">
                                <span>All Airlines</span>
                                <i class='bx bx-x fs-air-chip__close' v-if="airlineAll" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="fs-air-chip" :class="{ 'is-active': airlineLowCost }"
                                @click.prevent="airlineLowCost = !airlineLowCost">
                                <span class="fs-air-chip__indicator"></span>
                                Low Cost Airlines
                            </button>
                            <button type="button" class="fs-air-chip" :class="{ 'is-active': airlineGds }"
                                @click.prevent="airlineGds = !airlineGds">
                                <span class="fs-air-chip__indicator"></span>
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
                                <span>Search Flights</span>
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
                        <span class="fs-pro-tile__icon"><i class='bx bx-file'></i></span>
                        <span class="fs-pro-tile__meta">
                            <span class="fs-pro-tile__title">Offline Request</span>
                        </span>
                    </a>
                    <a href="#" class="fs-pro-tile fs-pro-tile--import" @click.prevent>
                        <span class="fs-pro-tile__icon"><i class='bx bx-import'></i></span>
                        <span class="fs-pro-tile__meta">
                            <span class="fs-pro-tile__title">Import PNR</span>
                        </span>
                    </a>
                    <a href="#" class="fs-pro-tile fs-pro-tile--hold" @click.prevent>
                        <span class="fs-pro-tile__icon"><i class='bx bx-time-five'></i></span>
                        <span class="fs-pro-tile__meta">
                            <span class="fs-pro-tile__title">Hold Itineraries</span>
                        </span>
                    </a>
                    <a href="#" class="fs-pro-tile fs-pro-tile--calendar" @click.prevent>
                        <span class="fs-pro-tile__icon"><i class='bx bx-calendar-event'></i></span>
                        <span class="fs-pro-tile__meta">
                            <span class="fs-pro-tile__title">Travel Calendar</span>
                        </span>
                    </a>
                </div>
            </div>

            <div class="fs-pro-aside-card fs-pro-recent-panel" v-if="recentSearches.length">
                <div class="fs-pro-aside-card__head">
                    <span class="fs-pro-aside-card__label">Recent Searches</span>
                    <button type="button"
                        class="fs-pro-aside-card__action fs-pro-aside-card__action--btn text-sm fs-pro-clear-recent"
                        @click="clearRecentSearches">Clear</button>
                </div>
                <a v-for="(item, idx) in recentSearches" :key="item.fingerprint + '-' + idx" href="#"
                    class="fs-pro-recent-row" @click.prevent="applyRecentSearch(item)">
                    <span class="fs-pro-recent-row__route">
                        <span class="fs-pro-recent-row__city">@{{ item.fromCity }}</span>
                        <i class='bx bxs-plane fs-pro-recent-row__arrow'></i>
                        <span class="fs-pro-recent-row__city">@{{ item.toCity }}</span>
                    </span>
                    <span class="fs-pro-recent-row__meta">
                        <span class="fs-pro-recent-row__dates">@{{ item.dateLine }}</span>
                    </span>
                </a>
            </div>
        </aside>

        <div class="fs-pro-promos">
            <a href="#" class="fs-promo fs-promo--gold" @click.prevent>
                <div class="fs-promo__body">
                    <span class="fs-promo__kicker">Exclusive Deal</span>
                    <span class="fs-promo__title">Hotel Bookings</span>
                </div>
                <i class='bx bxs-buildings fs-promo__art'></i>
            </a>
            <a href="#" class="fs-promo fs-promo--ocean" @click.prevent>
                <div class="fs-promo__body">
                    <span class="fs-promo__kicker">Fly from Dubai, Effortlessly</span>
                    <span class="fs-promo__title">Al Maktoum to<br>Saudi Arabia</span>
                    <span class="fs-promo__cta">Operates 4 days a week from DWC to RUH</span>
                </div>
                <i class='bx bxs-plane-alt fs-promo__art'></i>
            </a>
            <a href="#" class="fs-promo fs-promo--night" @click.prevent>
                <div class="fs-promo__body">
                    <span class="fs-promo__title">NDC FARES</span>
                    <span class="fs-promo__cta">Now Available Exclusively on Online</span>
                </div>
                <i class='bx bxs-plane-alt fs-promo__art'></i>
            </a>
        </div>
    </form>
</div>
@push('css')
    @include('user.vue.partials.fs-pro-enterprise-styles')
@endpush
