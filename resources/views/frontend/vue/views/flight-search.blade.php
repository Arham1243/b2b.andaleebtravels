<div class="d-flex align-items-center gap-4">
    <div class="radio-btn">
        <input v-model="tripType" type="radio" id="one-way" class="radio-btn__input" name="trip_type" value="one-way">
        <label class="radio-btn__label" for="one-way">One way</label>
    </div>
    <div class="radio-btn">
        <input v-model="tripType" type="radio" id="round-trip" class="radio-btn__input" name="trip_type"
            value="round-trip">
        <label class="radio-btn__label" for="round-trip">Round Trip</label>
    </div>
    {{-- <div class="radio-btn">
                                    <input v-model="tripType" type="radio" id="multi-city" class="radio-btn__input"
                                        name="trip_type" value="multi-city">
                                    <label class="radio-btn__label" for="multi-city">Multi-City</label>
                                </div> --}}
</div>
{{-- <div class="search-options" v-if="tripType === 'multi-city'">
                                multi city
                            </div> --}}
<div class="search-options" v-cloak>
    <form method="GET">
        <div class="search-options-wrapper">
            <input type="hidden" :value="pax.adults" name='adults'>
            <input type="hidden" :value="pax.children" name='children'>
            <input type="hidden" :value="pax.infants" name='infants'>

            <!-- FROM -->
            <div class="departure-wrapper" ref="fromWrapperRef">
                <div class="search-box" @click.stop="onFromBoxClick">
                    <div class="search-box__label">From</div>
                    <input type="text" autocomplete="off" class="search-box__input" v-model="fromInputValue"
                        @input="fromQuery = fromInputValue" placeholder="Select Origin" ref="fromInputRef">
                    <div class="search-box__label">
                        @{{ selectedFrom?.name || '' }}
                    </div>
                </div>

                <!-- From Dropdown -->
                <div class="options-dropdown-wrapper options-dropdown-wrapper--from"
                    :class="{
                        open: fromDropdownOpen,
                        scroll: filteredFromAirports.length > 9
                    }">
                    <div class="options-dropdown">
                        <div class="options-dropdown__body p-0">
                            <ul class="options-dropdown-list">
                                <!-- Skeletons -->
                                <li v-if="loadingFrom" class="options-dropdown-list__item no-hover" v-for="n in 5"
                                    :key="'dep-skel-' + n">
                                    <div class="skeleton"></div>
                                </li>
                                <!-- No Matches -->
                                <li v-else-if="!filteredFromAirports.length"
                                    class="options-dropdown-list__item no-hover">
                                    <span class="text-danger" style="font-size:14px; font-weight:500;">No Matches
                                        Found</span>
                                </li>
                                <!-- Items -->
                                <li v-else class="options-dropdown-list__item" v-for="airport in filteredFromAirports"
                                    :key="airport.code" @click="selectFrom(airport, toggleFromDropdown)">
                                    <div class="icon"><i class="bx bx-map"></i></div>
                                    <div class="info">
                                        <div class="name">@{{ airport.city }}
                                            (@{{ airport.code }})</div>
                                        <span class="sub-text">
                                            @{{ airport.country }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TO -->
            <div class="arrival-wrapper" ref="toWrapperRef">
                <div class="search-box" @click.stop="onToBoxClick">
                    <div class="search-box__label">To</div>
                    <input type="text" autocomplete="off" class="search-box__input" v-model="toInputValue"
                        @input="toQuery = toInputValue" placeholder="Select Destination" ref="toInputRef">
                    <div class="search-box__label">
                        @{{ selectedTo?.name || '' }}
                    </div>
                </div>

                <!-- To Dropdown -->
                <div class="options-dropdown-wrapper options-dropdown-wrapper--to"
                    :class="{
                        open: toDropdownOpen,
                        scroll: filteredToAirports.length > 9
                    }">
                    <div class="options-dropdown">
                        <div class="options-dropdown__body p-0">
                            <ul class="options-dropdown-list">
                                <li v-if="loadingTo" class="options-dropdown-list__item no-hover" v-for="n in 5"
                                    :key="'to-skel-' + n">
                                    <div class="skeleton"></div>
                                </li>
                                <li v-else-if="!filteredToAirports.length" class="options-dropdown-list__item no-hover">
                                    <span class="text-danger" style="font-size:14px; font-weight:500;">No Matches
                                        Found</span>
                                </li>
                                <li v-else class="options-dropdown-list__item" v-for="airport in filteredToAirports"
                                    :key="airport.code" @click="selectTo(airport, toggleToDropdown)">
                                    <div class="icon"><i class="bx bx-map"></i></div>
                                    <div class="info">
                                        <div class="name">@{{ airport.city }}
                                            (@{{ airport.code }})</div>
                                        <span class="sub-text">
                                            @{{ airport.country }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DEPARTURE DATE -->
            <div class="search-box-wrapper" id="departure-box">
                <div class="search-box">
                    <div class="search-box__label">Departure</div>
                    <input readonly autocomplete="off" type="text" class="search-box__input cursor-pointer"
                        name="departure" ref="departureDate" placeholder="Departure on" id="departure-input">
                    <div class="search-box__label" id='departure-day'>&nbsp;</div>
                </div>
            </div>

            <!-- RETURN DATE -->
            <div class="search-box-wrapper" id="return-box">
                <div class="search-box">
                    <div class="search-box__label">Return</div>
                    <input type="text" autocomplete="off" class="search-box__input cursor-pointer"
                        placeholder="Return on" ref="returnDate" name="return" id="return-input">
                    <div class="search-box__label" id='return-day'>&nbsp;</div>
                </div>
            </div>

            <!-- TRAVELLERS -->
            <div class="pax-wrapper" ref="paxRef">
                <div class="search-box" @click.stop="togglePax">
                    <div class="search-box__label">Travellers</div>
                    <input readonly type="text" class="search-box__input cursor-pointer"
                        :value="totalTravellerText">
                    <div class="search-box__label">
                        @{{ classType }}
                    </div>

                </div>

                <!-- Pax Dropdown -->
                <div class="options-dropdown-wrapper options-dropdown-wrapper--pax" :class="{ open: paxOpen }">
                    <div class="options-dropdown options-dropdown--norm">
                        <div class="options-dropdown__body">
                            <ul class="paxs-list mt-0">
                                <!-- Adults -->
                                <li class="paxs-item">
                                    <div class="info">
                                        <div class="name">Adults</div>
                                        <span>18+ years</span>
                                    </div>
                                    <div class="quantity-counter">
                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="decrement('adults')"><i class='bx bx-minus'></i></button>

                                        <span
                                            class="quantity-counter__btn quantity-counter__btn--quantity">@{{ pax.adults }}</span>

                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="increment('adults')"><i class='bx bx-plus'></i></button>
                                    </div>
                                </li>

                                <!-- Children -->
                                <li class="paxs-item">
                                    <div class="info">
                                        <div class="name">Children
                                        </div>
                                        <span>2-12
                                            years</span>
                                    </div>
                                    <div class="quantity-counter">
                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="decrement('children')"><i class='bx bx-minus'></i></button>

                                        <span
                                            class="quantity-counter__btn quantity-counter__btn--quantity">@{{ pax.children }}</span>

                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="increment('children')"><i class='bx bx-plus'></i></button>
                                    </div>
                                </li>

                                <!-- Infants -->
                                <li class="paxs-item">
                                    <div class="info">
                                        <div class="name">Infants
                                        </div>
                                        <span>Under 2</span>
                                    </div>
                                    <div class="quantity-counter">
                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="decrement('infants')"><i class='bx bx-minus'></i></button>

                                        <span
                                            class="quantity-counter__btn quantity-counter__btn--quantity">@{{ pax.infants }}</span>

                                        <button type="button" class="quantity-counter__btn"
                                            @click.stop="increment('infants')"><i class='bx bx-plus'></i></button>
                                    </div>
                                </li>
                            </ul>

                            <div class="child-ages child-ages-search mt-3">
                                <div class="title">Travel Class</div>
                                <div class="child-age">
                                    <select v-model="classType" name=class_type class="form-control">
                                        <option value="Economy">Economy</option>
                                        <option value="Premium Economy">Premium Economy
                                        </option>
                                        <option value="Business">Business</option>
                                        <option value="First">First</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BUTTON -->
            <div class="search-button">
                <button :disabled="!isFlightSearchEnabled" class="themeBtn themeBtn--primary">Search</button>
            </div>
        </div>
        <div class="radio-btn ms-1">
            <input type="checkbox" id="direct-flights" class="radio-btn__input" name="is_direct_flight"
                value="true">
            <label class="radio-btn__label" for="direct-flights">Direct Flights</label>
        </div>
    </form>
</div>
