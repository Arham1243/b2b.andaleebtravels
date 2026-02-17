<div class="search-options" v-cloak>
    <form class="search-options-wrapper" method="GET" action="{{ route('frontend.hotels.search') }}">
        <!-- GOING TO -->
        <div class="departure-wrapper" ref="hotelDestinationWrapperRef">
            <div class="search-box" @click.stop="onHotelDestinationBoxClick">
                <div class="search-box__label">Going To</div>
                <input type="text" autocomplete="off" class="search-box__input" v-model="hotelDestinationInputValue"
                    @input="hotelDestinationQuery = hotelDestinationInputValue" placeholder="City, Hotel, or Region"
                    ref="hotelDestinationInputRef" name="destination">
                <div class="search-box__label">
                    @{{ selectedHotelDestination || '' }}
                </div>
            </div>

            <!-- Destination Dropdown -->
            <div class="options-dropdown-wrapper options-dropdown-wrapper--from"
                :class="{
                    open: hotelDestinationDropdownOpen,
                    scroll: (hotelDestinations?.countries?.length || 0) + (hotelDestinations?.provinces?.length || 0) +
                        (hotelDestinations?.locations?.length || 0) + (hotelHotels?.length || 0) > 9
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
                    v-if="!loadingHotelDestination && ((hotelDestinations?.countries?.length || 0) + (hotelDestinations?.provinces?.length || 0) + (hotelDestinations?.locations?.length || 0) > 0)">
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
                                :key="'province-' + item.id" @click="selectHotelDestination(item.name)">
                                <div class="icon">
                                    <i class='bx bx-map'></i>
                                </div>
                                <div class="info">
                                    <div class="name">@{{ item.name }}</div>
                                    <span class="sub-text">@{{ item.country_name }}</span>
                                </div>
                            </li>
                            <li class="options-dropdown-list__item" v-for="item in hotelDestinations.locations"
                                :key="'location-' + item.id" @click="selectHotelDestination(item.name)">
                                <div class="icon">
                                    <i class='bx bx-map'></i>
                                </div>
                                <div class="info">
                                    <div class="name">@{{ item.name }}</div>
                                    <span class="sub-text" v-if="item.province_name || item.country_name">
                                        @{{ [item.province_name, item.country_name].filter(Boolean).join(', ') }}
                                    </span>
                                    <span class="sub-text" v-else>
                                        @{{ item.country_name }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Hotels -->
                <div class="options-dropdown" v-if="!loadingHotelDestination && hotelHotels?.length">
                    <div class="options-dropdown__header">
                        <span>Hotels</span>
                    </div>
                    <div class="options-dropdown__body p-0">
                        <ul class="options-dropdown-list">
                            <li class="options-dropdown-list__item" v-for="item in hotelHotels"
                                :key="'hotel-' + item.id" @click="selectHotelDestination(item.name)">
                                <div class="icon">
                                    <i class='bx bx-building'></i>
                                </div>

                                <div class="info">
                                    <div class="name">@{{ item.name }}</div>
                                    <span class="sub-text">@{{ item.location_name }}, @{{ item.province_name }},
                                        @{{ item.country_name }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- No Matches -->
                <div class="options-dropdown options-dropdown--norm"
                    v-if="!loadingHotelDestination && !hotelDestinations?.countries?.length && !hotelDestinations?.provinces?.length && !hotelDestinations?.locations?.length && !hotelHotels?.length">
                    <div class="options-dropdown__header justify-content-center">
                        <span class="text-danger" style="font-weight: 500;">No Matches Found</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHECK IN DATE -->
        <div class="search-box-wrapper departure-wrapper tight-width" id="hotel-checkin-box">
            <div class="search-box">
                <div class="search-box__label">Check In</div>
                <input readonly autocomplete="off" type="text" class="search-box__input cursor-pointer"
                    name="check_in" ref="hotelCheckInDate" placeholder="Check In" id="hotel-checkin-input">
                <div class="search-box__label" id='hotel-checkin-day'>&nbsp;</div>
            </div>
        </div>

        <!-- CHECK OUT DATE -->
        <div class="search-box-wrapper departure-wrapper tight-width" id="hotel-checkout-box">
            <div class="search-box">
                <div class="search-box__label">Check Out</div>
                <input readonly autocomplete="off" type="text" class="search-box__input cursor-pointer"
                    name="check_out" ref="hotelCheckOutDate" placeholder="Check Out" id="hotel-checkout-input">
                <div class="search-box__label" id='hotel-checkout-day'>&nbsp;</div>
            </div>
        </div>

        <!-- ROOMS & GUESTS -->
        <div class="pax-wrapper departure-wrapper" ref="hotelRoomsRef">
            <div class="search-box" @click.stop="toggleHotelRooms">
                <div class="search-box__label">Rooms & Guests</div>
                <input readonly type="text" class="search-box__input cursor-pointer" :value="totalHotelGuestsText">
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
                                    <option value="" selected>Select</option>
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
                                    <div class="child-age child-age--half" v-for="(age, childIndex) in room.childAges"
                                        :key="'room-' + roomIndex + '-child-' + childIndex">
                                        <label>Child @{{ childIndex + 1 }} Age</label>
                                        <select v-model="room.childAges[childIndex]"
                                            :name="'room_' + (roomIndex + 1) + '_child_age_' + (childIndex + 1)"
                                            class="form-control">
                                            <option value="">Select</option>
                                            <option v-for="n in 17" :key="n" :value="n">
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

        <!-- BUTTON -->
        <div class="search-button">
            <button type="submit" :disabled="!isHotelSearchEnabled"
                class="themeBtn themeBtn--primary">Search</button>
        </div>
    </form>
</div>
