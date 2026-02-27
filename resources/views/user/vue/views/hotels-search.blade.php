<div class="hotel-search-redesign" v-cloak>
    <div class="hotel-search-redesign__title">Book Domestic and International Hotels</div>
    <form method="GET" action="{{ route('user.hotels.search') }}">
        <!-- ROW 1: Destination + Check In + Night + Check Out -->
        <div class="hs-row hs-row--top">
            <!-- DESTINATION -->
            <div class="hs-field hs-field--destination" ref="hotelDestinationWrapperRef">
                <div class="hs-field__inner" @click.stop="onHotelDestinationBoxClick">
                    <div class="hs-field__label">ENTER YOUR DESTINATION</div>
                    <div class="hs-field__value-row">
                        <input type="text" autocomplete="off" class="hs-field__input"
                            v-model="hotelDestinationInputValue"
                            @input="hotelDestinationQuery = hotelDestinationInputValue" placeholder="Select Destination"
                            ref="hotelDestinationInputRef" name="destination">
                        <i class='bx bx-world hs-field__icon'></i>
                    </div>
                </div>

                <!-- Destination Dropdown -->
                <div class="options-dropdown-wrapper options-dropdown-wrapper--from"
                    :class="{
                        open: hotelDestinationDropdownOpen,
                        scroll: (hotelDestinations?.countries?.length || 0) + (hotelDestinations?.provinces?.length ||
                            0) > 9
                    }">
                    <!-- Loading State -->
                    <div class="options-dropdown" v-if="loadingHotelDestination">
                        <div class="options-dropdown__body p-0">
                            <ul class="options-dropdown-list">
                                <li class="options-dropdown-list__item no-hover" v-for="n in 5"
                                    :key="'hotel-dest-skel-' + n">
                                    <div class="skeleton"></div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Destinations -->
                    <div class="options-dropdown"
                        v-if="!loadingHotelDestination && ((hotelDestinations?.countries?.length || 0) + (hotelDestinations?.provinces?.length || 0) > 0)">
                        <div class="options-dropdown__header">
                            <span>Destinations</span>
                        </div>
                        <div class="options-dropdown__body p-0">
                            <ul class="options-dropdown-list">
                                <li class="options-dropdown-list__item" v-for="item in hotelDestinations.countries"
                                    :key="'country-' + item.id" @click="selectHotelDestination(item.name)">
                                    <div class="icon">
                                        <i class='bx bx-map'></i>
                                    </div>
                                    <div class="info">
                                        <div class="name">@{{ item.name }}</div>
                                    </div>
                                </li>
                                <li class="options-dropdown-list__item" v-for="item in hotelDestinations.provinces"
                                    :key="'location-' + item.id" @click="selectHotelDestination(item.name)">
                                    <div class="icon">
                                        <i class='bx bx-map'></i>
                                    </div>
                                    <div class="info">
                                        <div class="name">@{{ item.name }}</div>
                                        <span class="sub-text" v-if="item.country_name">
                                            @{{ item.country_name }}
                                        </span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- No Matches -->
                    <div class="options-dropdown options-dropdown--norm"
                        v-if="!loadingHotelDestination && !hotelDestinations?.countries?.length && !hotelDestinations?.provinces?.length">
                        <div class="options-dropdown__header justify-content-center">
                            <span class="text-danger" style="font-weight: 500;">No Matches Found</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHECK IN DATE -->
            <div class="hs-field hs-field--date" id="hotel-checkin-box">
                <div class="hs-field__inner">
                    <div class="hs-field__label"><i class='bx bx-calendar'></i> CHECK IN <i class='bx bx-chevron-down'
                            style="font-size:11px"></i></div>
                    <div class="hs-date-display">
                        <span class="hs-date-display__day" id="hotel-checkin-dd">&mdash;</span>
                        <div class="hs-date-display__meta">
                            <span class="hs-date-display__month" id="hotel-checkin-mon">&nbsp;</span>
                            <span class="hs-date-display__weekday" id="hotel-checkin-day">&nbsp;</span>
                        </div>
                    </div>
                    <input readonly autocomplete="off" type="hidden" name="check_in" ref="hotelCheckInDate"
                        id="hotel-checkin-input">
                </div>
            </div>

            <!-- NIGHT BADGE -->
            <div class="hs-night-badge">
                <span class="hs-night-badge__count">@{{ nightCount }}</span>
                <span class="hs-night-badge__label">NIGHT</span>
            </div>

            <!-- CHECK OUT DATE -->
            <div class="hs-field hs-field--date" id="hotel-checkout-box">
                <div class="hs-field__inner">
                    <div class="hs-field__label"><i class='bx bx-calendar'></i> CHECK OUT <i class='bx bx-chevron-down'
                            style="font-size:11px"></i></div>
                    <div class="hs-date-display">
                        <span class="hs-date-display__day" id="hotel-checkout-dd">&mdash;</span>
                        <div class="hs-date-display__meta">
                            <span class="hs-date-display__month" id="hotel-checkout-mon">&nbsp;</span>
                            <span class="hs-date-display__weekday" id="hotel-checkout-day">&nbsp;</span>
                        </div>
                    </div>
                    <input readonly autocomplete="off" type="hidden" name="check_out" ref="hotelCheckOutDate"
                        id="hotel-checkout-input">
                </div>
            </div>
        </div>

        <!-- ROW 2: Rooms & Guests + Search -->
        <div class="hs-row hs-row--bottom">
            <!-- ROOMS & GUESTS -->
            <div class="hs-field hs-field--rooms" ref="hotelRoomsRef">
                <div class="hs-field__inner" @click.stop="toggleHotelRooms">
                    <div class="hs-field__label">ROOMS & GUESTS <i class='bx bx-chevron-down'
                            style="font-size:11px"></i></div>
                    <div class="hs-field__value">
                        <span class="hs-rooms-text">
                            <strong>@{{ hotelRoomCount || 0 }}</strong> Room
                            <strong>@{{ totalGuestsCount }}</strong> Guests
                        </span>
                    </div>
                </div>

                <!-- Rooms Dropdown -->
                <div class="options-dropdown-wrapper options-dropdown-wrapper--pax"
                    :class="{ open: hotelRoomsOpen, scroll: hotelRoomCount > 1 }">
                    <div class="options-dropdown options-dropdown--norm">
                        <div class="options-dropdown__body">
                            <!-- Room Count Selector -->
                            <div class="child-ages child-ages-search">
                                <div class="child-age">
                                    <label>Number of Rooms</label>
                                    <select v-model="hotelRoomCount" name="room_count" class="form-control">
                                        <option value="" selected disabled>Select</option>
                                        <option v-for="n in 5" :key="n" :value="n">
                                            @{{ n }} Room<template v-if="n > 1">s</template></option>
                                    </select>
                                </div>
                            </div>

                            <!-- Rooms Configuration -->
                            <div class="child-ages mt-3" v-if="hotelRooms.length > 0">
                                <div class="room-section w-100" v-for="(room, roomIndex) in hotelRooms"
                                    :key="'room-' + roomIndex">
                                    <div class="title mb-2" style="font-size: 0.85rem; font-weight: 600;">Room
                                        @{{ roomIndex + 1 }}</div>

                                    <!-- Hidden inputs for adults and children count -->
                                    <input type="hidden" :name="'room_' + (roomIndex + 1) + '_adults'"
                                        :value="room.adults">
                                    <input type="hidden" :name="'room_' + (roomIndex + 1) + '_children'"
                                        :value="room.children">

                                    <ul class="paxs-list mt-0">
                                        <!-- Adults -->
                                        <li class="paxs-item">
                                            <div class="info">
                                                <div class="name">Adults</div>
                                                <span>18+ years</span>
                                            </div>
                                            <div class="quantity-counter">
                                                <button type="button" class="quantity-counter__btn"
                                                    @click.stop="decrementHotelGuests(roomIndex, 'adults')">
                                                    <i class='bx bx-minus'></i>
                                                </button>
                                                <span
                                                    class="quantity-counter__btn quantity-counter__btn--quantity">@{{ room.adults }}</span>
                                                <button type="button" class="quantity-counter__btn"
                                                    @click.stop="incrementHotelGuests(roomIndex, 'adults')">
                                                    <i class='bx bx-plus'></i>
                                                </button>
                                            </div>
                                        </li>

                                        <!-- Children -->
                                        <li class="paxs-item">
                                            <div class="info">
                                                <div class="name">Children</div>
                                                <span>1-17 years</span>
                                            </div>
                                            <div class="quantity-counter">
                                                <button type="button" class="quantity-counter__btn"
                                                    @click.stop="decrementHotelGuests(roomIndex, 'children')">
                                                    <i class='bx bx-minus'></i>
                                                </button>
                                                <span
                                                    class="quantity-counter__btn quantity-counter__btn--quantity">@{{ room.children }}</span>
                                                <button type="button" class="quantity-counter__btn"
                                                    @click.stop="incrementHotelGuests(roomIndex, 'children')">
                                                    <i class='bx bx-plus'></i>
                                                </button>
                                            </div>
                                        </li>
                                    </ul>

                                    <!-- Child Ages -->
                                    <div class="child-ages child-ages-search mt-2" v-if="room.children > 0">
                                        <div class="child-age child-age--half"
                                            v-for="(age, childIndex) in room.childAges"
                                            :key="'room-' + roomIndex + '-child-' + childIndex">
                                            <label>Child @{{ childIndex + 1 }} Age</label>
                                            <select v-model="room.childAges[childIndex]"
                                                :name="'room_' + (roomIndex + 1) + '_child_age_' + (childIndex + 1)"
                                                class="form-control">
                                                <option value="">Select</option>
                                                <option v-for="n in 17" :key="n"
                                                    :value="n">
                                                    @{{ n }}
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

            <!-- SEARCH BUTTON -->
            <div class="hs-field hs-field--btn">
                <button type="submit" :disabled="!isHotelSearchEnabled" class="hs-search-btn">Search Hotels</button>
            </div>
        </div>
    </form>
</div>
