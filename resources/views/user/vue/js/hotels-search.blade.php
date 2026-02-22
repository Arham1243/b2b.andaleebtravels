@include('user.vue.services.hotels-search')
<script>
    const HotelSearch = createApp({
        setup() {


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
            const hotelDestinations = ref({
                countries: [],
                provinces: [],
                locations: []
            });
            const hotelHotels = ref([]);
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

            // Nationality & Residence
            const hotelNationality = ref('UAE');
            const hotelResidence = ref('UAE');
            const countryOptions = ref(['UAE', 'Saudi Arabia', 'Oman', 'Bahrain', 'Kuwait', 'Qatar', 'India', 'Pakistan', 'United Kingdom', 'United States']);
            const nationalitySearch = ref('');
            const residenceSearch = ref('');

            const filteredNationalityOptions = computed(() => {
                if (!nationalitySearch.value) return countryOptions.value;
                const q = nationalitySearch.value.toLowerCase();
                return countryOptions.value.filter(c => c.toLowerCase().includes(q));
            });
            const filteredResidenceOptions = computed(() => {
                if (!residenceSearch.value) return countryOptions.value;
                const q = residenceSearch.value.toLowerCase();
                return countryOptions.value.filter(c => c.toLowerCase().includes(q));
            });

            const {
                open: hotelNationalityOpen,
                wrapper: hotelNationalityRef,
                toggle: toggleHotelNationality
            } = useDropdown();

            const {
                open: hotelResidenceOpen,
                wrapper: hotelResidenceRef,
                toggle: toggleHotelResidence
            } = useDropdown();

            // Night count
            const nightCount = computed(() => {
                if (hotelCheckInDate.value && hotelCheckOutDate.value) {
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
                    hotelDestinations.value = data.destinations;
                    hotelHotels.value = data.hotels?.hotels || [];
                } catch (err) {
                    console.error("Hotel API Error:", err);
                    hotelDestinations.value = {
                        countries: [],
                        provinces: [],
                        locations: []
                    };
                    hotelHotels.value = [];
                } finally {
                    loadingHotelDestination.value = false;
                }
            };

            const selectHotelDestination = (destination) => {
                selectedHotelDestination.value = destination;
                hotelDestinationInputValue.value = destination;
                hotelDestinationQuery.value = '';
                toggleHotelDestinationDropdown();
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

            function waitForPicker(selector, retries = 15) {
                return new Promise(resolve => {
                    const interval = setInterval(() => {
                        const picker = $(selector).data('daterangepicker');
                        if (picker || retries <= 0) {
                            clearInterval(interval);
                            resolve(picker);
                        }
                        retries--;
                    }, 100);
                });
            }

            function getUrlParams() {
                const params = new URLSearchParams(window.location.search);
                const obj = {};

                for (const [key, value] of params.entries()) {
                    obj[key] = value;
                }

                return obj;
            }

            onBeforeMount(async () => {
                loadHotelDestinations('a');

                const urlParams = getUrlParams();

                /* =========================
                   DESTINATION
                ========================= */
                if (urlParams.destination) {
                    hotelDestinationInputValue.value = urlParams.destination;
                    selectedHotelDestination.value = urlParams.destination;
                }

                /* =========================
                   ROOMS
                ========================= */
                const roomCount = parseInt(urlParams.room_count || 1);
                hotelRoomCount.value = roomCount;

                await nextTick();

                for (let i = 0; i < roomCount; i++) {
                    const room = hotelRooms.value[i];
                    if (!room) continue;

                    room.adults = parseInt(urlParams[`room_${i + 1}_adults`] || 1);
                    room.children = parseInt(urlParams[`room_${i + 1}_children`] || 0);

                    await nextTick();

                    for (let c = 0; c < room.children; c++) {
                        const ageKey = `room_${i + 1}_child_age_${c + 1}`;
                        room.childAges[c] = urlParams[ageKey] || '';
                    }
                }

                /* =========================
                   DATES (SYNCED)
                ========================= */
                const checkInMoment = urlParams.check_in ?
                    moment(urlParams.check_in, 'MMM D, YYYY') :
                    null;

                const checkOutMoment = urlParams.check_out ?
                    moment(urlParams.check_out, 'MMM D, YYYY') :
                    null;

                const checkInPicker = await waitForPicker('#hotel-checkin-input');
                const checkOutPicker = await waitForPicker('#hotel-checkout-input');

                if (checkInMoment?.isValid() && checkInPicker) {
                    $('#hotel-checkin-input').val(checkInMoment.format('MMM D, YYYY'));
                    updateDateDisplay('hotel-checkin', checkInMoment);
                    checkInPicker.setStartDate(checkInMoment);

                    hotelCheckInDate.value = checkInMoment.clone();

                    if (checkOutPicker) {
                        checkOutPicker.minDate = checkInMoment.clone();
                        checkOutPicker.setStartDate(
                            checkOutMoment?.isValid() && checkOutMoment.isSameOrAfter(
                                checkInMoment) ?
                            checkOutMoment :
                            checkInMoment.clone()
                        );
                    }
                }

                if (checkOutMoment?.isValid() && checkOutPicker) {
                    $('#hotel-checkout-input').val(checkOutMoment.format('MMM D, YYYY'));
                    updateDateDisplay('hotel-checkout', checkOutMoment);
                    hotelCheckOutDate.value = checkOutMoment.clone();
                }

                /* =========================
                   NATIONALITY & RESIDENCE
                ========================= */
                if (urlParams.nationality) {
                    hotelNationality.value = urlParams.nationality;
                }
                if (urlParams.residence) {
                    hotelResidence.value = urlParams.residence;
                }
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
                const hasCheckIn = hotelCheckInDate.value && hotelCheckInDate.value.value !== '';
                const hasCheckOut = hotelCheckOutDate.value && hotelCheckOutDate.value.value !== '';
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
                hotelDestinations,
                hotelHotels,
                loadingHotelDestination,
                hotelDestinationInputRef,
                onHotelDestinationBoxClick,
                selectHotelDestination,
                hotelDestinationDropdownOpen,
                hotelDestinationWrapperRef,
                toggleHotelDestinationDropdown,
                isHotelSearchEnabled,
                // New fields
                nightCount,
                totalGuestsCount,
                hotelNationality,
                hotelResidence,
                countryOptions,
                hotelNationalityOpen,
                hotelNationalityRef,
                toggleHotelNationality,
                hotelResidenceOpen,
                hotelResidenceRef,
                toggleHotelResidence,
                nationalitySearch,
                residenceSearch,
                filteredNationalityOptions,
                filteredResidenceOptions
            };
        },
    });
    const hotelSearchInstance = HotelSearch.mount('#hotels-search');
    window.__hotelSearchVue = hotelSearchInstance;
</script>
@push('css')
    <link rel="stylesheet" href="{{ asset('user/assets/css/daterangepicker.css') }}" />
@endpush
@push('js')
    <script src="{{ asset('user/assets/js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('user/assets/js/daterangepicker.min.js') }}"></script>
    <script>
        function updateDateDisplay(prefix, dateMoment) {
            $(`#${prefix}-dd`).text(dateMoment.format('D'));
            $(`#${prefix}-mon`).text(dateMoment.format("MMM'YY"));
            $(`#${prefix}-day`).text(dateMoment.format('dddd'));
        }

        function clearDateDisplay(prefix) {
            $(`#${prefix}-dd`).html('&mdash;');
            $(`#${prefix}-mon`).html('&nbsp;');
            $(`#${prefix}-day`).html('&nbsp;');
        }

        function initSingleDatePicker(wrapperId, inputId, displayPrefix) {
            const format = "MMM D, YYYY";
            const $wrapper = $(`#${wrapperId}`);
            const $input = $(`#${inputId}`);
            if (!$wrapper.length || !$input.length) return;

            $input.daterangepicker({
                singleDatePicker: true,
                autoApply: true,
                showDropdowns: true,
                minDate: moment(),
                autoUpdateInput: false,
                parentEl: $wrapper,
                locale: {
                    format
                }
            });

            $input.on("apply.daterangepicker", function(ev, picker) {
                $input.val(picker.startDate.format(format));
                updateDateDisplay(displayPrefix, picker.startDate);

                // Sync back to Vue refs
                if (window.__hotelSearchVue) {
                    if (inputId === 'hotel-checkin-input') {
                        window.__hotelSearchVue.hotelCheckInDate = picker.startDate.clone();
                    } else if (inputId === 'hotel-checkout-input') {
                        window.__hotelSearchVue.hotelCheckOutDate = picker.startDate.clone();
                    }
                }
            });

            $wrapper.on("click", function(e) {
                if (!$(e.target).is($input)) {
                    const pickerInstance = $input.data('daterangepicker');
                    if (pickerInstance) {
                        pickerInstance.show();
                    }
                }
            });
        }

        $(document).ready(function() {
            initSingleDatePicker("hotel-checkin-box", "hotel-checkin-input", "hotel-checkin");
            initSingleDatePicker("hotel-checkout-box", "hotel-checkout-input", "hotel-checkout");

            const $checkinInput = $("#hotel-checkin-input");
            const $checkoutInput = $("#hotel-checkout-input");

            // Sync checkout with checkin
            $checkinInput.on("apply.daterangepicker", function(ev, picker) {
                const checkinDate = picker.startDate;
                const checkoutPicker = $checkoutInput.data('daterangepicker');

                if (checkoutPicker) {
                    checkoutPicker.minDate = checkinDate.clone().add(1, 'day');
                    checkoutPicker.setStartDate(checkinDate.clone().add(1, 'day'));

                    if ($checkoutInput.val()) {
                        const currentCheckout = moment($checkoutInput.val(), "MMM D, YYYY");
                        if (currentCheckout.isSameOrBefore(checkinDate)) {
                            $checkoutInput.val('');
                            clearDateDisplay('hotel-checkout');
                        }
                    }
                }
            });
        });
    </script>
@endpush
