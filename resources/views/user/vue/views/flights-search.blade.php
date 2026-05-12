<div class="hotel-search-redesign flight-search-redesign" v-cloak>
    <div class="hotel-search-redesign__title">
        <i class='bx bx-plane'></i> Book Domestic and International Flights
    </div>
    <form method="GET" action="{{ route('user.flights.search') }}" @submit="onFlightSearchSubmit">
        <input type="hidden" name="trip_type" :value="tripType">

        <div class="fs-trip-types">
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
            <div class="hs-row hs-row--top">
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
                                    <li class="options-dropdown-list__item" v-for="airport in filteredFromAirports"
                                        :key="'from-' + airport.code" @click="selectFromAirport(airport)">
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

                        <div class="options-dropdown" v-if="!loadingAirports && (filteredToAirports?.length || 0) > 0">
                            <div class="options-dropdown__header">
                                <span>Airports</span>
                            </div>
                            <div class="options-dropdown__body p-0">
                                <ul class="options-dropdown-list">
                                    <li class="options-dropdown-list__item" v-for="airport in filteredToAirports"
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

                <div class="hs-field hs-field--date" id="flight-departure-box">
                    <div class="hs-field__inner">
                        <div class="hs-field__label"><i class='bx bx-calendar'></i> DEPARTURE <i
                                class='bx bx-chevron-down' style="font-size:11px"></i></div>
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

                <div class="hs-trip-badge" :class="{ 'hs-trip-badge--round': tripType === 'round_trip' }">
                    <span class="hs-trip-badge__count">@{{ tripBadgeTop }}</span>
                    <span class="hs-trip-badge__label">@{{ tripBadgeBottom }}</span>
                </div>

                <div class="hs-field hs-field--date" id="flight-return-box">
                    <div class="hs-field__inner">
                        <div class="hs-field__label"><i class='bx bx-calendar'></i> RETURN <i
                                class='bx bx-chevron-down' style="font-size:11px"></i></div>
                        <template v-if="tripType === 'round_trip' && !returnDate">
                            <div class="hs-field__note">Add a return to search round-trip fares.</div>
                        </template>
                        <div class="hs-date-display"
                            v-show="tripType === 'round_trip' && !!returnDate">
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

        <div class="hs-row hs-row--bottom">
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
@push('css')
    <style>
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
