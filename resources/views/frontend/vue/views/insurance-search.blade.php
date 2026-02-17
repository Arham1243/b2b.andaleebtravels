<div class="search-options" v-cloak>
    <form class="search-options-wrapper" method="GET" action="{{ route('frontend.travel-insurance.index') }}">
        <!-- SELECT ORIGIN -->
        <div class="departure-wrapper" ref="insuranceFromWrapperRef">
            <div class="search-box" @click.stop="onInsuranceFromBoxClick">
                <div class="search-box__label">From</div>
                <input type="text" autocomplete="off" class="search-box__input" v-model="insuranceFromInputValue"
                    @input="insuranceFromQuery = insuranceFromInputValue" placeholder="Select Origin"
                    ref="insuranceFromInputRef" name="origin">
                <div class="search-box__label">
                    @{{ selectedInsuranceFrom?.name || '' }}
                </div>
            </div>

            <!-- From Dropdown -->
            <div class="options-dropdown-wrapper options-dropdown-wrapper--from"
                :class="{
                    open: insuranceFromDropdownOpen,
                    scroll: filteredInsuranceFromCountries.length > 9
                }">
                <div class="options-dropdown">
                    <div class="options-dropdown__body p-0">
                        <ul class="options-dropdown-list">
                            <!-- Skeletons -->
                            <li v-if="loadingInsuranceFrom" class="options-dropdown-list__item no-hover" v-for="n in 5"
                                :key="'ins-from-skel-' + n">
                                <div class="skeleton"></div>
                            </li>
                            <!-- No Matches -->
                            <li v-else-if="!filteredInsuranceFromCountries.length"
                                class="options-dropdown-list__item no-hover">
                                <span class="text-danger" style="font-size:14px; font-weight:500;">No Matches
                                    Found</span>
                            </li>
                            <!-- Items -->
                            <li v-else class="options-dropdown-list__item"
                                v-for="country in filteredInsuranceFromCountries" :key="country.id"
                                @click="selectInsuranceFrom(country, toggleInsuranceFromDropdown)">
                                <div class="icon"><i class="bx bx-map"></i></div>
                                <div class="info">
                                    <div class="name">@{{ country.name }}</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- SELECT DESTINATION -->
        <div class="arrival-wrapper" ref="insuranceToWrapperRef">
            <div class="search-box" @click.stop="onInsuranceToBoxClick">
                <div class="search-box__label">To</div>
                <input type="text" autocomplete="off" class="search-box__input" v-model="insuranceToInputValue"
                    @input="insuranceToQuery = insuranceToInputValue" placeholder="Select Destination"
                    ref="insuranceToInputRef" name="destination">
                <div class="search-box__label">
                    @{{ selectedInsuranceTo?.name || '' }}
                </div>
            </div>

            <!-- To Dropdown -->
            <div class="options-dropdown-wrapper options-dropdown-wrapper--to"
                :class="{
                    open: insuranceToDropdownOpen,
                    scroll: filteredInsuranceToCountries.length > 9
                }">
                <div class="options-dropdown">
                    <div class="options-dropdown__body p-0">
                        <ul class="options-dropdown-list">
                            <li v-if="loadingInsuranceTo" class="options-dropdown-list__item no-hover" v-for="n in 5"
                                :key="'ins-to-skel-' + n">
                                <div class="skeleton"></div>
                            </li>
                            <li v-else-if="!filteredInsuranceToCountries.length"
                                class="options-dropdown-list__item no-hover">
                                <span class="text-danger" style="font-size:14px; font-weight:500;">No Matches
                                    Found</span>
                            </li>
                            <li v-else class="options-dropdown-list__item"
                                v-for="country in filteredInsuranceToCountries" :key="country.id"
                                @click="selectInsuranceTo(country, toggleInsuranceToDropdown)">
                                <div class="icon"><i class="bx bx-map"></i></div>
                                <div class="info">
                                    <div class="name">@{{ country.name }}</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- START DATE -->
        <div class="search-box-wrapper  departure-wrapper tight-width" id="insurance-start-box">
            <div class="search-box">
                <div class="search-box__label">Start Date</div>
                <input readonly autocomplete="off" type="text" class="search-box__input cursor-pointer"
                    name="start_date" ref="insuranceStartDate" placeholder="Start Date" id="insurance-start-input">
                <div class="search-box__label" id='insurance-start-day'>&nbsp;</div>
            </div>
        </div>

        <!-- RETURN DATE -->
        <div class="search-box-wrapper departure-wrapper tight-width" id="insurance-return-box">
            <div class="search-box">
                <div class="search-box__label">Return Date</div>
                <input type="text" autocomplete="off" class="search-box__input cursor-pointer"
                    placeholder="Return Date" ref="insuranceReturnDate" name="return_date" id="insurance-return-input">
                <div class="search-box__label" id='insurance-return-day'>&nbsp;</div>
            </div>
        </div>

        <!-- COUNTRY OF RESIDENCE -->
        <div class="arrival-wrapper" ref="insuranceResidenceWrapperRef">
            <div class="search-box" @click.stop="onInsuranceResidenceBoxClick">
                <div class="search-box__label">Country of Residence</div>
                <input type="text" autocomplete="off" class="search-box__input"
                    v-model="insuranceResidenceInputValue"
                    @input="insuranceResidenceQuery = insuranceResidenceInputValue" placeholder="Residence"
                    ref="insuranceResidenceInputRef" name="residence_country">
                <div class="search-box__label">
                    @{{ selectedInsuranceResidence?.name || '' }}
                </div>
            </div>

            <!-- Residence Dropdown -->
            <div class="options-dropdown-wrapper options-dropdown-wrapper--to"
                :class="{
                    open: insuranceResidenceDropdownOpen,
                    scroll: filteredInsuranceResidenceCountries.length > 9
                }">
                <div class="options-dropdown">
                    <div class="options-dropdown__body p-0">
                        <ul class="options-dropdown-list">
                            <li v-if="loadingInsuranceResidence" class="options-dropdown-list__item no-hover"
                                v-for="n in 5" :key="'ins-res-skel-' + n">
                                <div class="skeleton"></div>
                            </li>
                            <li v-else-if="!filteredInsuranceResidenceCountries.length"
                                class="options-dropdown-list__item no-hover">
                                <span class="text-danger" style="font-size:14px; font-weight:500;">No Matches
                                    Found</span>
                            </li>
                            <li v-else class="options-dropdown-list__item"
                                v-for="country in filteredInsuranceResidenceCountries"
                                :key="country.id"
                                @click="selectInsuranceResidence(country, toggleInsuranceResidenceDropdown)">
                                <div class="icon"><i class="bx bx-map"></i></div>
                                <div class="info">
                                    <div class="name">@{{ country.name }}</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- PERSONS -->
        <div class="pax-wrapper" ref="insurancePaxRef">
            <div class="search-box" @click.stop="toggleInsurancePax">
                <div class="search-box__label">Persons</div>
                <input readonly type="text" class="search-box__input cursor-pointer"
                    :value="totalInsurancePersonsText">
            </div>

            <!-- Persons Dropdown -->
            <div class="options-dropdown-wrapper options-dropdown-wrapper--pax" :class="{ open: insurancePaxOpen }">
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
                                        @click.stop="decrementInsurance('adults')"><i
                                            class='bx bx-minus'></i></button>
                                    <input type="hidden" name="adult_count" :value="insurancePax.adults">
                                    <span
                                        class="quantity-counter__btn quantity-counter__btn--quantity">@{{ insurancePax.adults }}</span>

                                    <button type="button" class="quantity-counter__btn"
                                        @click.stop="incrementInsurance('adults')"><i class='bx bx-plus'></i></button>
                                </div>
                            </li>

                            <!-- Children -->
                            <li class="paxs-item">
                                <div class="info">
                                    <div class="name">Children</div>
                                    <span>2-17 years</span>
                                </div>
                                <div class="quantity-counter">
                                    <button type="button" class="quantity-counter__btn"
                                        @click.stop="decrementInsurance('children')"><i
                                            class='bx bx-minus'></i></button>
                                    <input type="hidden" name="children_count" :value="insurancePax.children">
                                    <span
                                        class="quantity-counter__btn quantity-counter__btn--quantity">@{{ insurancePax.children }}</span>

                                    <button type="button" class="quantity-counter__btn"
                                        @click.stop="incrementInsurance('children')"><i
                                            class='bx bx-plus'></i></button>
                                </div>
                            </li>

                            <!-- Infant -->
                            <li class="paxs-item">
                                <div class="info">
                                    <div class="name">Infant</div>
                                    <span>0-1 year</span>
                                </div>
                                <div class="quantity-counter">
                                    <button type="button" class="quantity-counter__btn"
                                        @click.stop="decrementInsurance('children')"><i
                                            class='bx bx-minus'></i></button>
<input type="hidden" name="infant_count" :value="insurancePax.infant">
                                    <span
                                        class="quantity-counter__btn quantity-counter__btn--quantity">@{{ insurancePax.infant }}</span>

                                    <button type="button" class="quantity-counter__btn"
                                        @click.stop="incrementInsurance('infant')"><i
                                            class='bx bx-plus'></i></button>
                                </div>
                            </li>
                        </ul>

                        <!-- Age Inputs -->
                        <div class="child-ages" v-if="insurancePax.adults > 0 || insurancePax.children > 0">
                            <!-- Adult Ages -->
                            <div class="room-section w-100" v-if="insuranceAdultAges.length > 0">
                                <div class="child-ages child-ages-search">
                                    <div class="child-age child-age--half" v-for="(age, index) in insuranceAdultAges"
                                        :key="'adult-' + index">
                                        <label>Adult @{{ index + 1 }} Age</label>
                                        <select v-model="insuranceAdultAges[index]" name="adult_ages[]" + (index + 1)"
                                            class="form-control">
                                            <option value="">Select</option>
                                            <option v-for="n in 82" :key="n" :value="n + 17">
                                                @{{ n + 17 }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!-- Child Ages -->
                            <div class="room-section w-100" v-if="insuranceChildAges.length > 0">
                                <div class="child-ages child-ages-search">
                                    <div class="child-age child-age--half" v-for="(age, index) in insuranceChildAges"
                                        :key="'child-' + index">
                                        <label>Child @{{ index + 1 }} Age</label>
                                        <select v-model="insuranceChildAges[index]" name="children_ages[]"
                                            class="form-control">
                                            <option value="">Select</option>
                                            <option v-for="n in 16" :key="n" :value="n + 1">
                                                @{{ n + 1 }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BUTTON -->
        <div class="search-button">
            <button type="submit" 
                class="themeBtn themeBtn--primary">Search</button>
        </div>
    </form>
</div>
