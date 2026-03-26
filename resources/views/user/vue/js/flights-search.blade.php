@push('js')
<script>
    const FlightsSearch = createApp({
        setup() {
            function useDropdown() {
                const open = ref(false);
                const wrapper = ref(null);

                const toggle = () => {
                    open.value = !open.value;
                };
                const openDropdown = () => {
                    open.value = true;
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
                    openDropdown,
                    close
                };
            }

            const airports = ref([]);
            const loadingAirports = ref(true);

            const fromInput = ref('');
            const toInput = ref('');
            const fromQuery = ref('');
            const toQuery = ref('');
            const selectedFrom = ref(null);
            const selectedTo = ref(null);
            const departureDate = ref('');
            const returnDate = ref('');

            const fromInputRef = ref(null);
            const toInputRef = ref(null);

            const {
                open: fromDropdownOpen,
                wrapper: fromWrapperRef,
                openDropdown: openFromDropdown,
                close: closeFromDropdown
            } = useDropdown();

            const {
                open: toDropdownOpen,
                wrapper: toWrapperRef,
                openDropdown: openToDropdown,
                close: closeToDropdown
            } = useDropdown();

            const {
                open: travellersOpen,
                wrapper: travellersRef,
                toggle: toggleTravellers
            } = useDropdown();

            const normalize = (val) => (val || '').toString().toLowerCase();

            const filterAirports = (query) => {
                const q = normalize(query);
                if (!q) return airports.value.slice(0, 20);
                return airports.value.filter((airport) => {
                    const haystack = [
                        airport.code,
                        airport.name,
                        airport.city,
                        airport.country
                    ].map(normalize).join(' ');
                    return haystack.includes(q);
                }).slice(0, 20);
            };

            const filteredFromAirports = computed(() => {
                const filtered = filterAirports(fromQuery.value || fromInput.value);
                if (!selectedTo.value) return filtered;
                return filtered.filter((airport) => airport.code !== selectedTo.value.code);
            });
            const filteredToAirports = computed(() => {
                const filtered = filterAirports(toQuery.value || toInput.value);
                if (!selectedFrom.value) return filtered;
                return filtered.filter((airport) => airport.code !== selectedFrom.value.code);
            });

            const formatAirportInput = (airport) => {
                if (!airport) return '';
                return `${airport.code} - ${airport.city}`;
            };

            const onFromBoxClick = () => {
                openFromDropdown();
                fromInputRef.value?.focus();
            };

            const onToBoxClick = () => {
                openToDropdown();
                toInputRef.value?.focus();
            };

            const onFromInput = () => {
                selectedFrom.value = null;
                fromQuery.value = fromInput.value;
                openFromDropdown();
            };

            const onToInput = () => {
                selectedTo.value = null;
                toQuery.value = toInput.value;
                openToDropdown();
            };

            const selectFromAirport = (airport) => {
                selectedFrom.value = airport;
                fromInput.value = formatAirportInput(airport);
                fromQuery.value = '';
                closeFromDropdown();
            };

            const selectToAirport = (airport) => {
                selectedTo.value = airport;
                toInput.value = formatAirportInput(airport);
                toQuery.value = '';
                closeToDropdown();
            };

            const loadAirports = async () => {
                loadingAirports.value = true;
                try {
                    const response = await fetch("{{ asset('user/mocks/airports.json') }}");
                    const data = await response.json();
                    airports.value = Array.isArray(data) ? data : [];
                } catch (err) {
                    console.error("Airports load error:", err);
                    airports.value = [];
                } finally {
                    loadingAirports.value = false;
                }
            };

            const applyParamsFromUrl = () => {
                const params = new URLSearchParams(window.location.search);
                const fromCode = (params.get('from') || '').toUpperCase();
                const toCode = (params.get('to') || '').toUpperCase();

                if (fromCode) {
                    const fromAirport = airports.value.find(a => a.code === fromCode);
                    selectedFrom.value = fromAirport || { code: fromCode, city: fromCode };
                    fromInput.value = formatAirportInput(selectedFrom.value);
                }

                if (toCode) {
                    const toAirport = airports.value.find(a => a.code === toCode);
                    selectedTo.value = toAirport || { code: toCode, city: toCode };
                    toInput.value = formatAirportInput(selectedTo.value);
                }

                const adultsParam = parseInt(params.get('adults') || '1', 10);
                const childrenParam = parseInt(params.get('children') || '0', 10);
                const infantsParam = parseInt(params.get('infants') || '0', 10);
                adults.value = Number.isNaN(adultsParam) || adultsParam < 1 ? 1 : adultsParam;
                children.value = Number.isNaN(childrenParam) || childrenParam < 0 ? 0 : childrenParam;
                infants.value = Number.isNaN(infantsParam) || infantsParam < 0 ? 0 : infantsParam;
            };

            const adults = ref(1);
            const children = ref(0);
            const infants = ref(0);

            const incrementAdults = () => { adults.value++; };
            const decrementAdults = () => { if (adults.value > 1) adults.value--; };
            const incrementChildren = () => { children.value++; };
            const decrementChildren = () => { if (children.value > 0) children.value--; };
            const incrementInfants = () => { infants.value++; };
            const decrementInfants = () => { if (infants.value > 0) infants.value--; };

            const pluralize = (count, singular, plural) => count === 1 ? singular : plural;
            const travellersText = computed(() => {
                const parts = [];
                parts.push(`${adults.value} ${pluralize(adults.value, 'Adult', 'Adults')}`);
                if (children.value > 0) {
                    parts.push(`${children.value} ${pluralize(children.value, 'Child', 'Children')}`);
                }
                if (infants.value > 0) {
                    parts.push(`${infants.value} ${pluralize(infants.value, 'Infant', 'Infants')}`);
                }
                return parts.join(', ');
            });

            const hasReturnDate = computed(() => !!returnDate.value);
            const tripBadgeTop = computed(() => hasReturnDate.value ? 'ROUND' : 'ONE');
            const tripBadgeBottom = computed(() => hasReturnDate.value ? 'TRIP' : 'WAY');

            const isSearching = ref(false);
            const isSearchEnabled = computed(() => {
                return !!(selectedFrom.value && selectedTo.value && departureDate.value);
            });
            const onFlightSearchSubmit = (event) => {
                if (!isSearchEnabled.value) {
                    event.preventDefault();
                    return;
                }
                isSearching.value = true;
                window.__enablePageLoaderOnNav = true;
                if (typeof window.showPageLoader === 'function') {
                    window.showPageLoader('Finding the best flights for your trip...');
                }
            };

            onMounted(async () => {
                await loadAirports();
                applyParamsFromUrl();
            });

            return {
                airports,
                loadingAirports,
                fromInput,
                toInput,
                fromQuery,
                toQuery,
                selectedFrom,
                selectedTo,
                departureDate,
                returnDate,
                fromInputRef,
                toInputRef,
                fromDropdownOpen,
                toDropdownOpen,
                fromWrapperRef,
                toWrapperRef,
                filteredFromAirports,
                filteredToAirports,
                onFromBoxClick,
                onToBoxClick,
                onFromInput,
                onToInput,
                selectFromAirport,
                selectToAirport,
                travellersOpen,
                travellersRef,
                toggleTravellers,
                adults,
                children,
                infants,
                incrementAdults,
                decrementAdults,
                incrementChildren,
                decrementChildren,
                incrementInfants,
                decrementInfants,
                travellersText,
                hasReturnDate,
                tripBadgeTop,
                tripBadgeBottom,
                isSearching,
                isSearchEnabled,
                onFlightSearchSubmit
            };
        },
    });
    const flightsSearchInstance = FlightsSearch.mount('#flights-search');
    window.__flightsSearchVue = flightsSearchInstance;
</script>
@endpush
@push('css')
    <link rel="stylesheet" href="{{ asset('user/assets/css/daterangepicker.css') }}" />
@endpush
@push('js')
    <script src="{{ asset('user/assets/js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('user/assets/js/daterangepicker.min.js') }}"></script>
    <script>
        function updateFlightDateDisplay(prefix, dateMoment) {
            $(`#${prefix}-dd`).text(dateMoment.format('D'));
            $(`#${prefix}-mon`).text(dateMoment.format("MMM'YY"));
            $(`#${prefix}-day`).text(dateMoment.format('dddd'));
        }

        function clearFlightDateDisplay(prefix) {
            $(`#${prefix}-dd`).html('&mdash;');
            $(`#${prefix}-mon`).html('&nbsp;');
            $(`#${prefix}-day`).html('&nbsp;');
        }

        function initFlightSingleDatePicker(wrapperId, inputId, displayPrefix) {
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
                updateFlightDateDisplay(displayPrefix, picker.startDate);
                if (displayPrefix === 'flight-departure' && window.__flightsSearchVue) {
                    window.__flightsSearchVue.departureDate = picker.startDate.format(format);
                } else if (displayPrefix === 'flight-return' && window.__flightsSearchVue) {
                    window.__flightsSearchVue.returnDate = picker.startDate.format(format);
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
            initFlightSingleDatePicker("flight-departure-box", "flight-departure-input", "flight-departure");
            initFlightSingleDatePicker("flight-return-box", "flight-return-input", "flight-return");

            const $departureInput = $("#flight-departure-input");
            const $returnInput = $("#flight-return-input");

            (function populateFromUrl() {
                const params = new URLSearchParams(window.location.search);
                const vue = window.__flightsSearchVue;
                if (!vue) return;

                const dep = params.get('departure_date');
                const ret = params.get('return_date');

                if (dep) {
                    const depMoment = moment(dep, 'MMM D, YYYY');
                    if (depMoment.isValid()) {
                        const depPicker = $departureInput.data('daterangepicker');
                        if (depPicker) {
                            depPicker.setStartDate(depMoment);
                            $departureInput.val(depMoment.format('MMM D, YYYY'));
                            updateFlightDateDisplay('flight-departure', depMoment);
                            vue.departureDate = depMoment.format('MMM D, YYYY');
                        }
                    }
                }

                if (ret) {
                    const retMoment = moment(ret, 'MMM D, YYYY');
                    if (retMoment.isValid()) {
                        const retPicker = $returnInput.data('daterangepicker');
                        if (retPicker) {
                            retPicker.setStartDate(retMoment);
                            $returnInput.val(retMoment.format('MMM D, YYYY'));
                            updateFlightDateDisplay('flight-return', retMoment);
                            vue.returnDate = retMoment.format('MMM D, YYYY');
                        }
                    }
                }
            })();

            $departureInput.on("apply.daterangepicker", function(ev, picker) {
                const departureDate = picker.startDate;
                const returnPicker = $returnInput.data('daterangepicker');

                if (returnPicker) {
                    returnPicker.minDate = departureDate.clone().add(1, 'day');
                    if ($returnInput.val()) {
                        const currentReturn = moment($returnInput.val(), "MMM D, YYYY");
                        if (currentReturn.isSameOrBefore(departureDate)) {
                            $returnInput.val('');
                            clearFlightDateDisplay('flight-return');
                            if (window.__flightsSearchVue) {
                                window.__flightsSearchVue.returnDate = '';
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
