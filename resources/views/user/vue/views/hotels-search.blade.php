<div class="hotel-search-redesign flight-search-redesign fs-pro-enterprise" v-cloak>
    <form method="GET" action="{{ route('user.hotels.search') }}" @submit="onHotelSearchSubmit"
        class="fs-pro-layout">
        <div class="fs-pro-layout__main">
            <div class="fs-pro-card">

                <header class="fs-pro-card__head">
                    <div class="fs-pro-card__title-wrap">
                        <div class="fs-pro-eyebrow">
                            <span class="fs-pro-eyebrow__dot"></span>
                            <span class="fs-pro-eyebrow__label">Live inventory</span>
                            <span class="fs-pro-eyebrow__sep">·</span>
                            <span class="fs-pro-eyebrow__meta">International · Domestic</span>
                        </div>
                        <h2 class="fs-pro-card__title">Search Hotels</h2>
                        <p class="fs-pro-card__subtitle">Domestic &amp; international properties,
                            consolidator-grade pricing.</p>
                    </div>
                </header>

                <div class="fs-pro-route-sheet">
                    <div class="fs-pro-route-pair fs-pro-route-pair--hotel-destination-only">
                        <div class="fs-pro-route-field hs-field hs-field--destination fs-pro-route-field--from"
                            ref="hotelDestinationWrapperRef">
                            <div class="fs-pro-route-field__shell">
                                <div class="fs-pro-route-field__inner hs-field__inner"
                                    @click.stop="onHotelDestinationBoxClick">
                                    <span class="fs-pro-route-field__label hs-field__label">Destination</span>
                                    <div class="hs-field__value-row">
                                        <input type="text" autocomplete="off" class="hs-field__input fs-pro-route-input"
                                            v-model="hotelDestinationInputValue"
                                            @input="onHotelDestinationInput" placeholder="City or area"
                                            ref="hotelDestinationInputRef" name="destination">
                                        <input type="hidden" name="destination_type"
                                            :value="selectedHotelDestinationType">
                                        <i class='bx bx-world fs-pro-route-inline-icon'></i>
                                    </div>
                                </div>
                            </div>

                            <div class="options-dropdown-wrapper options-dropdown-wrapper--from"
                                :class="{
                                    open: hotelDestinationDropdownOpen,
                                    scroll: (hotelDestinations?.length || 0) > 9
                                }">
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

                                <div class="options-dropdown"
                                    v-if="!loadingHotelDestination && (hotelDestinations?.length || 0) > 0">
                                    <div class="options-dropdown__header">
                                        <span>Destinations</span>
                                    </div>
                                    <div class="options-dropdown__body p-0">
                                        <ul class="options-dropdown-list">
                                            <li class="options-dropdown-list__item" v-for="item in hotelDestinations"
                                                :key="'destination-' + item.type + '-' + item.id"
                                                @click="selectHotelDestination(item)">

                                                <div class="icon">
                                                    <i class='bx bx-map-pin'></i>
                                                </div>

                                                <div class="info">
                                                    <div class="name">@{{ item.name }}</div>

                                                    <span class="sub-text">@{{ item.country_name }}</span>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="options-dropdown options-dropdown--norm"
                                    v-if="!loadingHotelDestination && !(hotelDestinations?.length || 0)">
                                    <div class="options-dropdown__header justify-content-center">
                                        <span class="text-danger" style="font-weight: 500;">No Matches Found</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fs-pro-date-pair fs-pro-date-pair--hotel-range" id="hotel-date-range-shell">
                        <div class="fs-pro-date-cell fs-pro-date-cell--stay-range hs-field hs-field--date"
                            id="hotel-stay-dates-box">
                            <div class="hs-field__inner fs-pro-date-inner">
                                <div class="hs-field__label fs-pro-date-label">
                                    <i class='bx bx-calendar'></i>
                                    <span>Check in - Check out</span>
                                    <i class='bx bx-chevron-down fs-pro-date-chevron'></i>
                                </div>
                                <div class="fs-pro-stay-range-display">
                                    <div class="fs-pro-stay-range-display__col">
                                        <span class="fs-pro-stay-range-display__day"
                                            id="hotel-stay-start-dd">&mdash;</span>
                                        <div class="fs-pro-stay-range-display__meta">
                                            <span class="fs-pro-stay-range-display__month"
                                                id="hotel-stay-start-mon">&nbsp;</span>
                                            <span class="fs-pro-stay-range-display__weekday"
                                                id="hotel-stay-start-day">&nbsp;</span>
                                        </div>
                                    </div>
                                    <div class="fs-pro-stay-range-display__mid" aria-hidden="true">
                                        <i class='bx bx-right-arrow-alt'></i>
                                        <span class="fs-pro-stay-range-display__nights">
                                            <strong>@{{ nightCount }}</strong>
                                            <span class="fs-pro-stay-range-display__nights-label">
                                                night<span v-if="nightCount !== 1">s</span>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="fs-pro-stay-range-display__col fs-pro-stay-range-display__col--end">
                                        <span class="fs-pro-stay-range-display__day"
                                            id="hotel-stay-end-dd">&mdash;</span>
                                        <div class="fs-pro-stay-range-display__meta">
                                            <span class="fs-pro-stay-range-display__month"
                                                id="hotel-stay-end-mon">&nbsp;</span>
                                            <span class="fs-pro-stay-range-display__weekday"
                                                id="hotel-stay-end-day">&nbsp;</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="text" readonly autocomplete="off"
                                    class="fs-pro-hotel-drp-anchor"
                                    id="hotel-drp-anchor"
                                    tabindex="-1"
                                    aria-label="Select check-in and check-out dates">
                                <input type="hidden" name="check_in" id="hotel-checkin-input">
                                <input type="hidden" name="check_out" id="hotel-checkout-input">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fs-pro-footer">
                    <div class="fs-pro-pax-cabin-row fs-pro-pax-cabin-row--hotel-only">
                        <div class="hs-field hs-field--rooms fs-pro-travellers" ref="hotelRoomsRef">
                            <div class="hs-field__inner fs-pro-travellers__inner" @click.stop="toggleHotelRooms">
                                <div class="hs-field__label fs-pro-label">Rooms &amp; guests
                                    <i class='bx bx-chevron-down fs-pro-chevron'></i>
                                </div>
                                <div class="hs-field__value">
                                    <span class="hs-rooms-text fs-pro-pax-line">
                                        <strong>@{{ hotelRoomCount || 0 }}</strong> Room
                                        <strong>@{{ totalGuestsCount }}</strong> Guests
                                    </span>
                                </div>
                            </div>

                            <div class="options-dropdown-wrapper options-dropdown-wrapper--pax"
                                :class="{ open: hotelRoomsOpen, scroll: hotelRoomCount > 1 }">
                                <div class="options-dropdown options-dropdown--norm">
                                    <div class="options-dropdown__body">
                                        <div class="child-ages child-ages-search">
                                            <div class="child-age">
                                                <label>Number of Rooms</label>
                                                <select v-model="hotelRoomCount" name="room_count"
                                                    class="form-control">
                                                    <option value="" selected disabled>Select</option>
                                                    <option v-for="n in 5" :key="n" :value="n">
                                                        @{{ n }} Room<template v-if="n > 1">s</template></option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="child-ages mt-3" v-if="hotelRooms.length > 0">
                                            <div class="room-section w-100" v-for="(room, roomIndex) in hotelRooms"
                                                :key="'room-' + roomIndex">
                                                <div class="title mb-2" style="font-size: 0.85rem; font-weight: 600;">
                                                    Room
                                                    @{{ roomIndex + 1 }}</div>

                                                <input type="hidden" :name="'room_' + (roomIndex + 1) + '_adults'"
                                                    :value="room.adults">
                                                <input type="hidden" :name="'room_' + (roomIndex + 1) + '_children'"
                                                    :value="room.children">

                                                <ul class="paxs-list mt-0">
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

                                                <div class="child-ages child-ages-search mt-2"
                                                    v-if="room.children > 0">
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
                    </div>

                    <div class="fs-pro-actions-footer fs-pro-actions-footer--hotel-only">
                        <button type="submit" class="fs-pro-search-btn" style="min-width: auto"
                            :disabled="!isHotelSearchEnabled || isHotelSearchSubmitting">
                            <template v-if="isHotelSearchSubmitting">
                                <i class='bx bx-loader-alt bx-spin'></i>
                                Searching…
                            </template>
                            <template v-else>
                                <span>Search Hotels</span>
                            </template>
                        </button>
                    </div>

                    <div class="fs-pro-trust-strip">
                        <span class="fs-pro-trust-item">
                            <i class='bx bx-shield-quarter'></i> Secure rates &amp; trusted suppliers
                        </span>
                        <span class="fs-pro-trust-item">
                            <i class='bx bx-time-five'></i> Fast search response
                        </span>
                        <span class="fs-pro-trust-item">
                            <i class='bx bx-support'></i> 24×7 desk support
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <aside class="fs-pro-aside fs-pro-aside--hotel">
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
        </aside>
    </form>
</div>
@push('css')
    @include('user.vue.partials.fs-pro-enterprise-styles')
@endpush
@push('css')
    <style>
        /* Same row as flights: destination | stay dates */
        .hotel-search-redesign.fs-pro-enterprise .fs-pro-layout {
            align-items: stretch;
        }

        .fs-pro-route-pair--hotel-destination-only {
            flex: 1 1 220px;
            min-width: 0;
        }

        .fs-pro-route-pair--hotel-destination-only .fs-pro-route-field--from .hs-field__inner {
            padding-right: 0.95rem !important;
        }

        /* Stretch destination tile to match date row height (same baseline / bottom edge) */
        .hotel-search-redesign .fs-pro-route-pair--hotel-destination-only > .fs-pro-route-field {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            align-self: stretch;
            min-height: 0;
        }

        .hotel-search-redesign .fs-pro-route-pair--hotel-destination-only .fs-pro-route-field__shell {
            flex: 1 1 auto;
            display: flex;
            align-items: stretch;
            min-height: 70px;
        }

        .hotel-search-redesign .fs-pro-route-pair--hotel-destination-only .fs-pro-route-field__shell > .hs-field__inner {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 0;
        }

        .fs-pro-date-pair--hotel-range {
            flex: 1.25 1 min(380px, 100%);
            min-width: 0;
        }

        .fs-pro-date-cell--stay-range {
            flex: 1 1 100%;
            min-width: 0;
        }

        .hotel-search-redesign .fs-pro-date-pair--hotel-range > .fs-pro-date-cell {
            align-self: stretch;
            display: flex;
            flex-direction: column;
        }

        .hotel-search-redesign .fs-pro-date-pair--hotel-range .hs-field__inner.fs-pro-date-inner {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            min-height: 0;
        }

        /* Less vertical padding than default 88px enterprise tiles */
        .hotel-search-redesign .fs-pro-route-field__shell {
            min-height: 70px;
        }

        .hotel-search-redesign .fs-pro-route-field .hs-field__inner {
            padding: 0.5rem 0.85rem !important;
        }

        .hotel-search-redesign .fs-pro-date-cell .hs-field__inner {
            min-height: 70px;
            padding: 0.5rem 0.85rem !important;
        }

        .hotel-search-redesign .fs-pro-card {
            padding: 1.05rem 1.2rem;
        }

        .hotel-search-redesign .fs-pro-route-sheet {
            margin-bottom: 0.6rem;
            gap: 0.55rem;
        }

        .hotel-search-redesign .fs-pro-footer {
            margin-top: 0.5rem;
        }

        .hotel-search-redesign .fs-pro-travellers {
            padding: 0.42rem 0.75rem !important;
        }

        /* Aside: fill column height so blank band uses panel chrome */
        .fs-pro-aside.fs-pro-aside--hotel {
            align-self: stretch;
            box-sizing: border-box;
            background: var(--fs-surface-2);
            border: 1px solid var(--fs-line);
            border-radius: 16px;
            padding: 0.65rem 0.6rem;
        }

        .fs-pro-aside--hotel .fs-pro-tile {
            min-height: 88px;
            padding: 0.65rem 0.72rem 0.72rem !important;
        }

        .fs-pro-aside--hotel .fs-pro-tile-grid {
            gap: 0.55rem;
        }

        .fs-pro-stay-range-display {
            display: flex;
            align-items: center;
            gap: 0.65rem 1rem;
            margin-top: 0.08rem;
            flex-wrap: wrap;
        }

        .fs-pro-stay-range-display__col {
            display: flex;
            align-items: baseline;
            gap: 0.45rem;
            min-width: 0;
            flex: 1 1 120px;
        }

        .fs-pro-stay-range-display__col--end {
            justify-content: flex-end;
            text-align: right;
        }

        .fs-pro-stay-range-display__col--end .fs-pro-stay-range-display__meta {
            align-items: flex-end;
        }

        .fs-pro-stay-range-display__day {
            font-variant-numeric: tabular-nums;
            font-size: 1.32rem !important;
            font-weight: 700 !important;
            color: #111827 !important;
            line-height: 1 !important;
            letter-spacing: -0.02em !important;
        }

        .fs-pro-stay-range-display__meta {
            display: flex;
            flex-direction: column;
            gap: 1px;
            line-height: 1.15;
            min-width: 0;
        }

        .fs-pro-stay-range-display__month {
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            color: #374151 !important;
        }

        .fs-pro-stay-range-display__weekday {
            font-size: 0.7rem !important;
            font-weight: 400 !important;
            color: #6b7280 !important;
        }

        .fs-pro-stay-range-display__mid {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.1rem;
            flex-shrink: 0;
            color: var(--fs-slate-2);
            padding: 0 0.2rem;
        }

        .fs-pro-stay-range-display__mid i {
            font-size: 1.15rem;
            color: var(--fs-muted);
        }

        .fs-pro-stay-range-display__nights {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--fs-brand-2);
            white-space: nowrap;
            text-align: center;
            line-height: 1.2;
        }

        .fs-pro-stay-range-display__nights-label {
            font-weight: 600;
            display: block;
        }

        .fs-pro-date-cell--stay-range .hs-field__inner.fs-pro-date-inner {
            position: relative;
        }

        .fs-pro-hotel-drp-anchor {
            position: absolute;
            inset: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            border: none;
            cursor: pointer;
            background: transparent;
            z-index: 2;
        }

        /* Picker on body must stack above fixed topbar (z-index 1000); main column uses low z-index context */
        body .daterangepicker.hotel-stay-drp {
            z-index: 12050 !important;
        }

        .fs-pro-footer .fs-pro-pax-cabin-row--hotel-only {
            grid-template-columns: 1fr;
            flex-wrap: wrap;
        }

        .fs-pro-actions-footer.fs-pro-actions-footer--hotel-only {
            justify-content: flex-end;
        }

        .fs-pro-actions-footer.fs-pro-actions-footer--hotel-only .fs-pro-search-btn {
            width: auto;
            min-width: 200px;
        }

        @media (max-width: 900px) {

            .fs-pro-route-pair--hotel-destination-only,
            .fs-pro-date-pair--hotel-range {
                flex: 1 1 100%;
            }
        }

        @media (max-width: 640px) {
            .fs-pro-stay-range-display__col--end {
                justify-content: flex-start;
                text-align: left;
            }

            .fs-pro-stay-range-display__col--end .fs-pro-stay-range-display__meta {
                align-items: flex-start;
            }

            .fs-pro-stay-range-display__mid {
                flex-direction: row;
                width: 100%;
                justify-content: center;
                gap: 0.5rem;
                padding: 0.25rem 0;
            }
        }
    </style>
@endpush
