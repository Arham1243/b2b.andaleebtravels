@push('js')
@include('user.vue.services.hotels-search')
<script>
    const HOTEL_SEARCH_ACTION = @json(route('user.hotels.search'));
    const RECENT_HOTELS_KEY = 'b2b_hotel_recent_searches_v1';
    const MAX_RECENT_HOTELS = 4;

    const HotelSearch = createApp({
        setup() {

            const stableQueryFingerprint = (queryString) => {
                const params = new URLSearchParams(queryString);
                const keys = [...new Set([...params.keys()])].sort();
                const chunks = [];
                keys.forEach((k) => {
                    params.getAll(k).sort().forEach((v) => {
                        chunks.push(`${encodeURIComponent(k)}=${encodeURIComponent(v)}`);
                    });
                });
                return chunks.join('&');
            };

            const hotelSearchParamsFromFormData = (fd) => {
                const params = new URLSearchParams();
                for (const [k, v] of fd.entries()) {
                    if (v === '' || v === null || v === undefined) {
                        continue;
                    }
                    if (
                        k === 'destination' ||
                        k === 'destination_type' ||
                        k === 'check_in' ||
                        k === 'check_out' ||
                        k === 'room_count' ||
                        k.startsWith('room_')
                    ) {
                        params.append(k, v);
                    }
                }
                return params.toString();
            };

            const recentSearches = ref([]);

            const formatHotelRecentDates = (checkIn, checkOut) => {
                const fmt = (s) => {
                    const t = (s || '').trim();
                    if (!t) return '';
                    if (/^\d{4}-\d{2}-\d{2}$/.test(t)) {
                        const [y, mo, d] = t.split('-').map(Number);
                        const dt = new Date(y, mo - 1, d);
                        return Number.isNaN(dt.getTime())
                            ? t
                            : dt.toLocaleDateString('en-US', {
                                  month: 'short',
                                  day: 'numeric',
                                  year: 'numeric',
                              });
                    }
                    return t;
                };
                const a = fmt(checkIn);
                const b = fmt(checkOut);
                if (a && b) return `${a} | ${b}`;
                if (a || b) return a || b;
                return 'Dates not set';
            };

            const buildHotelRecentEntry = (queryString) => {
                const p = new URLSearchParams(queryString);
                const destLabel = (p.get('destination') || '').trim() || '—';
                const checkIn = (p.get('check_in') || '').trim();
                const checkOut = (p.get('check_out') || '').trim();
                const roomCount = Math.max(1, parseInt(p.get('room_count') || '1', 10) || 1);
                let adults = 0;
                let children = 0;
                for (let i = 1; i <= roomCount; i++) {
                    adults += parseInt(p.get(`room_${i}_adults`) || '1', 10) || 0;
                    children += parseInt(p.get(`room_${i}_children`) || '0', 10) || 0;
                }
                const guests = adults + children;
                const roomsBit = `${roomCount} ${roomCount === 1 ? 'Room' : 'Rooms'}`;
                const guestsBit = `${guests} ${guests === 1 ? 'Guest' : 'Guests'}`;
                const paxLine = `${roomsBit} · ${guestsBit}`;
                const dateLine = formatHotelRecentDates(checkIn, checkOut);

                return {
                    queryString,
                    destLabel,
                    dateLine,
                    paxLine,
                };
            };

            const loadRecentSearches = () => {
                try {
                    const raw = localStorage.getItem(RECENT_HOTELS_KEY);
                    const parsed = raw ? JSON.parse(raw) : [];
                    recentSearches.value = Array.isArray(parsed)
                        ? parsed
                              .filter((e) => e && typeof e.queryString === 'string')
                              .map((e) => ({
                                  ...buildHotelRecentEntry(e.queryString),
                                  fingerprint: stableQueryFingerprint(e.queryString),
                              }))
                              .slice(0, MAX_RECENT_HOTELS)
                        : [];
                } catch {
                    recentSearches.value = [];
                }
            };

            const pushRecentSearch = (entry) => {
                const fp = stableQueryFingerprint(entry.queryString);
                const next = recentSearches.value.filter(
                    (e) => stableQueryFingerprint(e.queryString) !== fp
                );
                next.unshift({ ...entry, fingerprint: fp });
                recentSearches.value = next.slice(0, MAX_RECENT_HOTELS);
                try {
                    localStorage.setItem(RECENT_HOTELS_KEY, JSON.stringify(recentSearches.value));
                } catch {
                    /* ignore quota */
                }
            };

            const clearRecentSearches = () => {
                recentSearches.value = [];
                try {
                    localStorage.removeItem(RECENT_HOTELS_KEY);
                } catch {
                    /* ignore */
                }
            };

            const applyRecentSearch = (item) => {
                const qs = item.queryString || '';
                window.location.href = qs ? `${HOTEL_SEARCH_ACTION}?${qs}` : HOTEL_SEARCH_ACTION;
            };

            function useDropdown() {
                const open = ref(false);
                const wrapper = ref(null);

                const toggle = () => {
                    open.value = !open.value;
                };
                const close = () => {
                    open.value = false;
                };

                const onClickOutside = (e) => {
                    if (wrapper.value && !wrapper.value.contains(e.target)) {
                        close();
                    }
                };

                onMounted(() => {
                    document.addEventListener("click", onClickOutside);
                });

                onBeforeUnmount(() => {
                    document.removeEventListener("click", onClickOutside);
                });
                return {
                    open,
                    wrapper,
                    toggle,
                    close
                };
            }

            // Hotel Search Logic
            const hotelCheckInDate = ref(null);
            const hotelCheckOutDate = ref(null);
            const hotelRoomCount = ref('');
            const hotelRooms = ref([]);
            const isHotelSearchSubmitting = ref(false);

            const {
                open: hotelRoomsOpen,
                wrapper: hotelRoomsRef,
                toggle: toggleHotelRooms
            } = useDropdown();

            const {
                open: hotelDestinationDropdownOpen,
                wrapper: hotelDestinationWrapperRef,
                toggle: toggleHotelDestinationDropdown
            } = useDropdown();

            const hotelDestinationQuery = ref('');
            const hotelDestinationInputValue = ref('');
            const selectedHotelDestination = ref('');
            const selectedHotelDestinationType = ref('');
            const hotelDestinations = ref([]);
            const loadingHotelDestination = ref(false);

            const totalHotelGuestsText = computed(() => {
                if (hotelRoomCount.value === '') {
                    return 'Select Rooms & Guests';
                }
                const totalAdults = hotelRooms.value.reduce((sum, room) => sum + room.adults, 0);
                const totalChildren = hotelRooms.value.reduce((sum, room) => sum + room.children, 0);
                const totalGuests = totalAdults + totalChildren;
                const roomText = hotelRoomCount.value === 1 ? '1 Room' :
                    `${hotelRoomCount.value} Rooms`;
                const guestText = totalGuests === 1 ? '1 Guest' : `${totalGuests} Guests`;
                return `${roomText}, ${guestText}`;
            });

            const isValidMoment = (val) => val && typeof val.isValid === 'function' && val.isValid();

            // Night count
            const nightCount = computed(() => {
                if (isValidMoment(hotelCheckInDate.value) && isValidMoment(hotelCheckOutDate.value)) {
                    const diff = hotelCheckOutDate.value.diff(hotelCheckInDate.value, 'days');
                    return diff > 0 ? diff : 1;
                }
                return 1;
            });

            // Total guests count
            const totalGuestsCount = computed(() => {
                const totalAdults = hotelRooms.value.reduce((sum, room) => sum + room.adults, 0);
                const totalChildren = hotelRooms.value.reduce((sum, room) => sum + room.children, 0);
                return totalAdults + totalChildren;
            });

            const hotelDestinationInputRef = ref(null);
            const onHotelDestinationBoxClick = () => {
                toggleHotelDestinationDropdown();
                hotelDestinationInputRef.value?.focus();
            };

            const loadHotelDestinations = async (searchQuery = '') => {
                loadingHotelDestination.value = true;
                try {
                    const data = await window.HotelGlobalSearchAPI(searchQuery);
                    hotelDestinations.value = data.destinations?.all || [];
                } catch (err) {
                    console.error("Hotel API Error:", err);
                    hotelDestinations.value = [];
                } finally {
                    loadingHotelDestination.value = false;
                }
            };

            const selectHotelDestination = (destination) => {
                selectedHotelDestination.value = destination;
                selectedHotelDestinationType.value = destination?.type || '';
                hotelDestinationInputValue.value = destination?.name || destination;
                hotelDestinationQuery.value = '';
                toggleHotelDestinationDropdown();
            };

            const onHotelDestinationInput = () => {
                selectedHotelDestinationType.value = '';
                hotelDestinationQuery.value = hotelDestinationInputValue.value;
            };

            watch(hotelDestinationQuery, (newQuery) => {
                if (!newQuery) {
                    loadHotelDestinations('a');
                } else {
                    loadHotelDestinations(newQuery);
                }
            });

            // Watch room count changes
            watch(hotelRoomCount, (newCount, oldCount) => {
                if (newCount > oldCount) {
                    for (let i = oldCount; i < newCount; i++) {
                        hotelRooms.value.push({
                            adults: 1,
                            children: 0,
                            childAges: []
                        });
                    }
                } else if (newCount < oldCount) {
                    hotelRooms.value.splice(newCount);
                }
            });

            onMounted(() => {
                loadHotelDestinations('a');
                loadRecentSearches();
            });

            const incrementHotelGuests = (roomIndex, key) => {
                hotelRooms.value[roomIndex][key]++;
            };

            const decrementHotelGuests = (roomIndex, key) => {
                if (hotelRooms.value[roomIndex][key] > 0) {
                    if (key === 'adults' && hotelRooms.value[roomIndex][key] === 1) return;
                    hotelRooms.value[roomIndex][key]--;
                }
            };

            // Watch for children count changes in all rooms dynamically
            watch(() => hotelRooms.value.map(room => room.children), (newCounts, oldCounts) => {
                newCounts.forEach((newCount, roomIndex) => {
                    const oldCount = oldCounts[roomIndex] || 0;
                    if (newCount > oldCount) {
                        for (let i = oldCount; i < newCount; i++) {
                            hotelRooms.value[roomIndex].childAges.push('');
                        }
                    } else if (newCount < oldCount) {
                        hotelRooms.value[roomIndex].childAges.splice(newCount);
                    }
                });
            }, {
                deep: true
            });

            const isHotelSearchEnabled = computed(() => {
                const hasCheckIn = isValidMoment(hotelCheckInDate.value);
                const hasCheckOut = isValidMoment(hotelCheckOutDate.value);
                const hasDestination = hotelDestinationInputValue.value && hotelDestinationInputValue
                    .value.trim() !== '';
                const hasRooms = hotelRooms.value.length > 0;

                // Check all rooms have at least 1 adult
                const allRoomsValid = hotelRooms.value.every(room => room.adults >= 1);

                // Check all child ages are filled
                const allChildAgesFilled = hotelRooms.value.every(room =>
                    room.childAges.length === room.children &&
                    room.childAges.every(age => age !== '')
                );

                return hasCheckIn && hasCheckOut && hasDestination && hasRooms && allRoomsValid &&
                    allChildAgesFilled;
            });

            const onHotelSearchSubmit = (event) => {
                if (!isHotelSearchEnabled.value) {
                    event.preventDefault();
                    return;
                }
                try {
                    const fd = new FormData(event.currentTarget);
                    const queryString = hotelSearchParamsFromFormData(fd);
                    pushRecentSearch(buildHotelRecentEntry(queryString));
                } catch (err) {
                    console.warn('Hotel recent searches persist failed', err);
                }
                isHotelSearchSubmitting.value = true;
                window.__enablePageLoaderOnNav = true;
                if (typeof window.showPageLoader === 'function') {
                    window.showPageLoader('Finding the best hotels for your trip...', 'bx bx-restaurant');
                }
            };

            return {
                // Hotel
                hotelCheckInDate,
                hotelCheckOutDate,
                hotelRoomCount,
                hotelRooms,
                hotelRoomsOpen,
                hotelRoomsRef,
                toggleHotelRooms,
                totalHotelGuestsText,
                incrementHotelGuests,
                decrementHotelGuests,
                hotelDestinationQuery,
                hotelDestinationInputValue,
                selectedHotelDestination,
                selectedHotelDestinationType,
                hotelDestinations,
                loadingHotelDestination,
                hotelDestinationInputRef,
                onHotelDestinationBoxClick,
                selectHotelDestination,
                onHotelDestinationInput,
                hotelDestinationDropdownOpen,
                hotelDestinationWrapperRef,
                toggleHotelDestinationDropdown,
                isHotelSearchEnabled,
                isHotelSearchSubmitting,
                onHotelSearchSubmit,
                nightCount,
                totalGuestsCount,

                recentSearches,
                clearRecentSearches,
                applyRecentSearch,
            };
        },
    });
    const hotelSearchInstance = HotelSearch.mount('#hotels-search');
    window.__hotelSearchVue = hotelSearchInstance;
</script>
@endpush
@push('css')
    <link rel="stylesheet" href="{{ asset('user/assets/css/daterangepicker.css') }}" />
@endpush
@push('js')
    <script src="{{ asset('user/assets/js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('user/assets/js/daterangepicker.min.js') }}"></script>
    <script>
        function updateHotelStayDisplay(prefix, dateMoment) {
            $(`#${prefix}-dd`).text(dateMoment.format('D'));
            $(`#${prefix}-mon`).text(dateMoment.format("MMM'YY"));
            $(`#${prefix}-day`).text(dateMoment.format('dddd'));
        }

        /**
         * Unified range picker (two-month calendar) for stay dates.
         * parentEl: body - avoids clipping/stacking under .fs-pro-layout__main (z-index 40) vs fixed topbar (1000).
         */
        function initHotelDateRangePicker() {
            const vue = window.__hotelSearchVue;
            const $anchor = $('#hotel-drp-anchor');
            const $checkinInput = $('#hotel-checkin-input');
            const $checkoutInput = $('#hotel-checkout-input');

            if (!$anchor.length) return;

            const fmt = 'MMM D, YYYY';

            if ($anchor.data('daterangepicker')) {
                $anchor.data('daterangepicker').remove();
            }

            const inVal = ($checkinInput.val() || '').trim();
            const outVal = ($checkoutInput.val() || '').trim();
            const inM = inVal ? moment(inVal, fmt, true) : null;
            const outM = outVal ? moment(outVal, fmt, true) : null;

            const opts = {
                autoApply: true,
                showDropdowns: true,
                minDate: moment().startOf('day'),
                autoUpdateInput: false,
                parentEl: 'body',
                opens: 'center',
                drops: 'down',
                /* true: left/right are always consecutive months - avoids both panes showing the same month
                   (which duplicates start/end highlights = four “selected” days). */
                linkedCalendars: true,
                singleDatePicker: false,
                locale: {
                    format: fmt
                }
            };

            if (inM && inM.isValid()) opts.startDate = inM.clone();
            if (outM && outM.isValid()) opts.endDate = outM.clone();

            $anchor.daterangepicker(opts);

            const drpInst = $anchor.data('daterangepicker');
            if (drpInst && drpInst.container) {
                $(drpInst.container).addClass('flight-search-redesign hotel-stay-drp');
            }

            $anchor.on('show.daterangepicker', function(ev, picker) {
                $(picker.container).addClass('flight-search-redesign hotel-stay-drp');
            });

            $anchor.on('apply.daterangepicker', function(ev, picker) {
                const start = picker.startDate.clone();
                const endRaw = picker.endDate.clone();
                const hasRange = endRaw.isValid() && !endRaw.isSame(start, 'day');
                const end = hasRange ? endRaw : start.clone().add(1, 'day');

                $checkinInput.val(start.format(fmt));
                $checkoutInput.val(end.format(fmt));
                updateHotelStayDisplay('hotel-stay-start', start);
                updateHotelStayDisplay('hotel-stay-end', end);

                if (vue) {
                    vue.hotelCheckInDate = start.clone();
                    vue.hotelCheckOutDate = end.clone();
                }
            });
        }

        $(document).ready(function() {
            initHotelDateRangePicker();

            const $anchor = $('#hotel-drp-anchor');
            const $checkinInput = $('#hotel-checkin-input');
            const $checkoutInput = $('#hotel-checkout-input');

            // Populate all search fields from URL params via jQuery
            (function populateFromUrl() {
                const params = new URLSearchParams(window.location.search);
                const vue = window.__hotelSearchVue;
                if (!vue) return;

                // Destination
                const dest = params.get('destination');
                if (dest) {
                    vue.hotelDestinationInputValue = dest;
                    vue.selectedHotelDestination = dest;
                }
                const destType = params.get('destination_type');
                if (destType) {
                    vue.selectedHotelDestinationType = destType;
                }

                // Rooms & Guests
                const roomCount = parseInt(params.get('room_count') || 1);
                vue.hotelRoomCount = roomCount;

                setTimeout(() => {
                    for (let i = 0; i < roomCount; i++) {
                        const room = vue.hotelRooms[i];
                        if (!room) continue;
                        room.adults = parseInt(params.get(`room_${i + 1}_adults`) || 1);
                        room.children = parseInt(params.get(`room_${i + 1}_children`) || 0);
                    }

                    setTimeout(() => {
                        for (let i = 0; i < roomCount; i++) {
                            const room = vue.hotelRooms[i];
                            if (!room) continue;
                            for (let c = 0; c < room.children; c++) {
                                const ageVal = params.get(`room_${i + 1}_child_age_${c + 1}`);
                                if (ageVal) room.childAges[c] = parseInt(ageVal);
                            }
                        }
                    }, 50);
                }, 50);

                // Dates (range picker on anchor)
                const fmt = 'MMM D, YYYY';
                const checkIn = params.get('check_in');
                const checkOut = params.get('check_out');
                const drp = $anchor.data('daterangepicker');

                if (drp && checkIn && checkOut) {
                    const checkInMoment = moment(checkIn, fmt, true);
                    const checkOutMoment = moment(checkOut, fmt, true);
                    if (checkInMoment.isValid() && checkOutMoment.isValid()) {
                        drp.setStartDate(checkInMoment);
                        drp.setEndDate(checkOutMoment);
                        $checkinInput.val(checkInMoment.format(fmt));
                        $checkoutInput.val(checkOutMoment.format(fmt));
                        updateHotelStayDisplay('hotel-stay-start', checkInMoment);
                        updateHotelStayDisplay('hotel-stay-end', checkOutMoment);
                        vue.hotelCheckInDate = checkInMoment.clone();
                        vue.hotelCheckOutDate = checkOutMoment.clone();
                    }
                }
            })();
        });
    </script>
@endpush
