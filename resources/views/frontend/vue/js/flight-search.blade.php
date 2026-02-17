<script>
    const FlightSearch = createApp({
        setup() {
            const tripType = ref('one-way');
            const departureDate = ref(null);
            const classType = ref('Economy');
            const returnDate = ref(null);

            const {
                open: paxOpen,
                wrapper: paxRef,
                toggle: togglePax
            } = useDropdown();

            const {
                open: fromDropdownOpen,
                wrapper: fromWrapperRef,
                toggle: toggleFromDropdown
            } = useDropdown();

            const pax = ref({
                adults: 0,
                children: 0,
                infants: 0
            });

            const totalTravellerText = computed(() => {
                const total =
                    pax.value.adults + pax.value.children + pax.value.infants;

                return total === 1 ?
                    "1 Traveller" :
                    `${total} Travellers`;
            });

            const isFlightSearchEnabled = computed(() => {
                const hasDeparture = departureDate.value && departureDate.value.value !== '';
                const hasReturn = returnDate.value && returnDate.value.value !== '';
                const hasFrom = fromInputValue.value && fromInputValue.value.trim() !== '';
                const hasTo = toInputValue.value && toInputValue.value.trim() !== '';
                const adultOk = pax.value.adults >= 1;

                return hasDeparture && hasReturn && hasFrom && hasTo && adultOk;
            });

            const increment = (key) => {
                pax.value[key] = pax.value[key] + 1;
            };

            const decrement = (key) => {
                if (pax.value[key] > 0) {
                    pax.value[key] = pax.value[key] - 1;
                }
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

            function useAirportDropdown(fetchAirportsFn) {
                const query = ref('');
                const inputValue = ref('');
                const selected = ref(null);
                const airports = ref([]);
                const loading = ref(false);

                const filteredAirports = computed(() => {
                    if (!query.value) return airports.value;
                    return airports.value.filter(a =>
                        a.name.toLowerCase().includes(query.value.toLowerCase()) ||
                        a.city.toLowerCase().includes(query.value.toLowerCase()) ||
                        a.code.toLowerCase().includes(query.value.toLowerCase())
                    );
                });

                const loadAirports = async (searchQuery = '') => {
                    loading.value = true;
                    try {
                        airports.value = await fetchAirportsFn(searchQuery);
                    } finally {
                        loading.value = false;
                    }
                };

                const selectAirport = (airport, toggleDropdownfn) => {
                    selected.value = airport;
                    inputValue.value = `${airport.city} ${airport.code}`;
                    query.value = '';
                    toggleDropdownfn();
                };

                watch(query, (newQuery) => {
                    loadAirports(newQuery);
                });

                return {
                    query,
                    inputValue,
                    selected,
                    airports,
                    filteredAirports,
                    loading,
                    loadAirports,
                    selectAirport
                };
            }

            const getAirports = (searchQuery = '') => {
                return new Promise(resolve => {
                    setTimeout(() => {
                        const allAirports = [{
                                code: 'DXB',
                                name: 'Dubai International Airport',
                                city: 'Dubai',
                                country: 'United Arab Emirates'
                            },
                            {
                                code: 'AUH',
                                name: 'Abu Dhabi International Airport',
                                city: 'Abu Dhabi',
                                country: 'United Arab Emirates'
                            },
                            {
                                code: 'SHJ',
                                name: 'Sharjah International Airport',
                                city: 'Sharjah',
                                country: 'United Arab Emirates'
                            },
                            {
                                code: 'LHR',
                                name: 'London Heathrow Airport',
                                city: 'London',
                                country: 'United Kingdom'
                            },
                            {
                                code: 'JFK',
                                name: 'John F. Kennedy International Airport',
                                city: 'New York',
                                country: 'USA'
                            },
                            {
                                code: 'CDG',
                                name: 'Paris Charles de Gaulle Airport',
                                city: 'Paris',
                                country: 'France'
                            }
                        ];

                        if (!searchQuery) return resolve(allAirports);

                        const filtered = allAirports.filter(a =>
                            a.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                            a.city.toLowerCase().includes(searchQuery.toLowerCase()) ||
                            a.code.toLowerCase().includes(searchQuery.toLowerCase())
                        );
                        resolve(filtered);
                    }, 300);
                });
            };

            const fromInputRef = ref(null);
            const onFromBoxClick = () => {
                toggleFromDropdown();
                fromInputRef.value?.focus();
            };

            const {
                query: fromQuery,
                inputValue: fromInputValue,
                selected: selectedFrom,
                filteredAirports: filteredFromAirports,
                loading: loadingFrom,
                loadAirports: loadFromAirports,
                selectAirport: selectFrom
            } = useAirportDropdown(getAirports);


            const toInputRef = ref(null);
            const onToBoxClick = () => {
                toggleToDropdown();
                toInputRef.value?.focus();
            };

            const {
                open: toDropdownOpen,
                wrapper: toWrapperRef,
                toggle: toggleToDropdown
            } = useDropdown();

            const {
                query: toQuery,
                inputValue: toInputValue,
                selected: selectedTo,
                filteredAirports: filteredToAirports,
                loading: loadingTo,
                loadAirports: loadToAirports,
                selectAirport: selectTo
            } = useAirportDropdown(getAirports);

            onBeforeMount(async () => {
                loadFromAirports();
                loadToAirports();
            });

            return {
                tripType,
                classType,

                // Dates
                departureDate,
                returnDate,

                // Pax
                paxOpen,
                paxRef,
                togglePax,
                totalTravellerText,
                increment,
                decrement,
                pax,

                // From
                fromInputValue,
                fromDropdownOpen,
                fromWrapperRef,
                toggleFromDropdown,
                fromQuery,
                selectedFrom,
                filteredFromAirports,
                loadingFrom,
                selectFrom,
                fromInputRef,
                onFromBoxClick,

                // TO
                toInputValue,
                toDropdownOpen,
                toWrapperRef,
                toggleToDropdown,
                toQuery,
                selectedTo,
                filteredToAirports,
                loadingTo,
                selectTo,
                toInputRef,
                onToBoxClick,
                isFlightSearchEnabled,
            };
        },
    });
    FlightSearch.mount('#flight-search');
</script>
@push('css')
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/daterangepicker.css') }}" />
@endpush
@push('js')
    <script src="{{ asset('frontend/assets/js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('frontend/assets/js/daterangepicker.min.js') }}"></script>
    <script>
        function initSingleDatePicker(wrapperId, inputId, dayDisplayId) {
            const format = "MMM D, YYYY";
            const $wrapper = $(`#${wrapperId}`);
            const $input = $(`#${inputId}`);
            const $dayDisplay = $(`#${dayDisplayId}`);

            if (!$wrapper.length || !$input.length || !$dayDisplay.length) return;

            // Initialize daterangepicker
            $input.daterangepicker({
                singleDatePicker: true,
                autoApply: true,
                showDropdowns: true,
                minDate: moment(),
                autoUpdateInput: false,
                locale: {
                    format
                }
            });

            $input.on("apply.daterangepicker", function(ev, picker) {
                $input.val(picker.startDate.format(format));
                $dayDisplay.text(picker.startDate.format("dddd"));
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
            initSingleDatePicker("departure-box", "departure-input", "departure-day");
            initSingleDatePicker("return-box", "return-input", "return-day");

            const $departureInput = $("#departure-input");
            const $returnInput = $("#return-input");

            // Sync return date with departure date
            $departureInput.on("apply.daterangepicker", function(ev, picker) {
                const departureDate = picker.startDate;
                const returnPicker = $returnInput.data('daterangepicker');

                if (returnPicker) {
                    // Set minimum date for return (day after departure)
                    returnPicker.minDate = departureDate.clone().add(1, 'day');

                    // Navigate return calendar to same month as departure
                    returnPicker.setStartDate(departureDate.clone().add(1, 'day'));

                    // If current return date is before new minimum, reset it
                    if ($returnInput.val()) {
                        const currentReturn = moment($returnInput.val(), "MMM D, YYYY");
                        if (currentReturn.isSameOrBefore(departureDate)) {
                            $returnInput.val('');
                            $("#return-day").text('');
                        }
                    }
                }
            });
        });
    </script>
@endpush
