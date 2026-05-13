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
            const tripType = ref('one_way');
            const directFlight = ref(false);
            const nearbyAirports = ref(true);
            const studentFare = ref(false);
            const onwardCabin = ref('Economy');
            const returnCabin = ref('Economy');
            const airlineAll = ref(true);
            const airlineLowCost = ref(true);
            const airlineGds = ref(true);
            const airlineNdc = ref(false);

            const fromInput = ref('');
            const toInput = ref('');
            const fromQuery = ref('');
            const toQuery = ref('');
            const selectedFrom = ref(null);
            const selectedTo = ref(null);
            const departureDate = ref('');
            const returnDate = ref('');

            const minMultiCitySegments = 2;
            const maxMultiCitySegments = 5;
            let multiCitySegmentKey = 0;

            const fromInputRef = ref(null);
            const toInputRef = ref(null);
            const segmentFromRefs = ref([]);
            const segmentToRefs = ref([]);

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

            const createEmptySegment = () => ({
                key: ++multiCitySegmentKey,
                fromInput: '',
                toInput: '',
                fromQuery: '',
                toQuery: '',
                selectedFrom: null,
                selectedTo: null,
                departureDate: '',
                fromDropdownOpen: false,
                toDropdownOpen: false
            });

            const multiCitySegments = ref([
                createEmptySegment(),
                createEmptySegment()
            ]);

            const normalize = (val) => (val || '').toString().toLowerCase();
            const todayIso = () => new Date().toISOString().split('T')[0];
            const parseDisplayDateToIso = (value) => {
                if (!value) return '';
                const parsed = new Date(value);
                if (Number.isNaN(parsed.getTime())) return '';
                return parsed.toISOString().split('T')[0];
            };
            const formatIsoDateForDisplay = (value) => {
                if (!value) return '';
                const parsed = new Date(`${value}T00:00:00`);
                if (Number.isNaN(parsed.getTime())) return '';
                return parsed.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                }).replace(',', '');
            };
            const segmentDateParts = (value) => {
                if (!value) {
                    return {
                        day: '—',
                        month: '\u00a0',
                        weekday: '\u00a0'
                    };
                }

                const parsed = new Date(`${value}T00:00:00`);
                if (Number.isNaN(parsed.getTime())) {
                    return {
                        day: '—',
                        month: '\u00a0',
                        weekday: '\u00a0'
                    };
                }

                return {
                    day: parsed.toLocaleDateString('en-US', { day: 'numeric' }),
                    month: parsed.toLocaleDateString('en-US', {
                        month: 'short',
                        year: '2-digit'
                    }).replace(' ', "'"),
                    weekday: parsed.toLocaleDateString('en-US', { weekday: 'long' })
                };
            };

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

            const formatAirportInput = (airport) => {
                if (!airport) return '';
                return `${airport.code} - ${airport.city}`;
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

            const swapRoutes = () => {
                const tmpSel = selectedFrom.value;
                selectedFrom.value = selectedTo.value;
                selectedTo.value = tmpSel;

                const tmpIn = fromInput.value;
                fromInput.value = toInput.value;
                toInput.value = tmpIn;
                fromQuery.value = '';
                toQuery.value = '';
                closeFromDropdown();
                closeToDropdown();
            };

            const ensureMinSegments = () => {
                while (multiCitySegments.value.length < minMultiCitySegments) {
                    multiCitySegments.value.push(createEmptySegment());
                }
            };

            const closeAllSegmentDropdowns = () => {
                multiCitySegments.value.forEach((segment) => {
                    segment.fromDropdownOpen = false;
                    segment.toDropdownOpen = false;
                });
            };

            const setSegmentFieldRef = (index, field, el) => {
                const target = field === 'from' ? segmentFromRefs.value : segmentToRefs.value;
                target[index] = el;
            };

            const openSegmentDropdown = (index, field) => {
                closeAllSegmentDropdowns();
                const segment = multiCitySegments.value[index];
                if (!segment) return;
                segment[`${field}DropdownOpen`] = true;
            };

            const onSegmentAirportInput = (index, field) => {
                const segment = multiCitySegments.value[index];
                if (!segment) return;
                segment[field === 'from' ? 'selectedFrom' : 'selectedTo'] = null;
                segment[`${field}Query`] = segment[`${field}Input`];
                openSegmentDropdown(index, field);
            };

            const filteredSegmentAirports = (segment, field) => {
                const query = segment[`${field}Query`] || segment[`${field}Input`];
                const excludedAirport = field === 'from' ? segment.selectedTo : segment.selectedFrom;
                return filterAirports(query).filter((airport) => airport.code !== excludedAirport?.code);
            };

            const selectSegmentAirport = (index, field, airport) => {
                const segment = multiCitySegments.value[index];
                if (!segment) return;

                const selectedKey = field === 'from' ? 'selectedFrom' : 'selectedTo';
                segment[selectedKey] = airport;
                segment[`${field}Input`] = formatAirportInput(airport);
                segment[`${field}Query`] = '';
                segment[`${field}DropdownOpen`] = false;

                if (field === 'to' && index < multiCitySegments.value.length - 1) {
                    const nextSegment = multiCitySegments.value[index + 1];
                    if (nextSegment && !nextSegment.selectedFrom) {
                        nextSegment.selectedFrom = airport;
                        nextSegment.fromInput = formatAirportInput(airport);
                        nextSegment.fromQuery = '';
                    }
                }
            };

            const addMultiCitySegment = () => {
                if (multiCitySegments.value.length >= maxMultiCitySegments) return;

                const newSegment = createEmptySegment();
                const previousSegment = multiCitySegments.value[multiCitySegments.value.length - 1];

                if (previousSegment?.selectedTo) {
                    newSegment.selectedFrom = previousSegment.selectedTo;
                    newSegment.fromInput = formatAirportInput(previousSegment.selectedTo);
                }

                if (previousSegment?.departureDate) {
                    newSegment.departureDate = previousSegment.departureDate;
                }

                multiCitySegments.value.push(newSegment);
                nextTick(() => {
                    if (typeof window.initFlightMultiCityDatePickers === 'function') {
                        window.initFlightMultiCityDatePickers();
                    }
                });
            };

            const removeMultiCitySegment = (index) => {
                if (multiCitySegments.value.length <= minMultiCitySegments) return;
                multiCitySegments.value.splice(index, 1);
                segmentFromRefs.value.splice(index, 1);
                segmentToRefs.value.splice(index, 1);
                ensureMinSegments();
                nextTick(() => {
                    if (typeof window.initFlightMultiCityDatePickers === 'function') {
                        window.initFlightMultiCityDatePickers();
                    }
                });
            };

            const segmentMinDate = (index) => {
                if (index === 0) {
                    return todayIso();
                }

                const previousDate = multiCitySegments.value[index - 1]?.departureDate;
                if (previousDate) {
                    return previousDate;
                }

                return todayIso();
            };

            const segmentDisplayDate = (value) => {
                return formatIsoDateForDisplay(value);
            };

            const clearReturnDate = () => {
                returnDate.value = '';
                const $returnInput = $("#flight-return-input");
                $returnInput.val('');
                clearFlightDateDisplay('flight-return');
            };

            const clearQuickReturn = () => {
                clearReturnDate();
            };

            const seedMultiCityFromSingle = () => {
                const first = createEmptySegment();
                const second = createEmptySegment();

                if (selectedFrom.value) {
                    first.selectedFrom = selectedFrom.value;
                    first.fromInput = formatAirportInput(selectedFrom.value);
                }

                if (selectedTo.value) {
                    first.selectedTo = selectedTo.value;
                    first.toInput = formatAirportInput(selectedTo.value);
                    second.selectedFrom = selectedTo.value;
                    second.fromInput = formatAirportInput(selectedTo.value);
                }

                if (departureDate.value) {
                    first.departureDate = parseDisplayDateToIso(departureDate.value);
                }

                if (returnDate.value) {
                    second.departureDate = parseDisplayDateToIso(returnDate.value);
                }

                multiCitySegments.value = [first, second];
            };

            const setTripType = (type) => {
                tripType.value = type;
                closeFromDropdown();
                closeToDropdown();
                closeAllSegmentDropdowns();

                if (type === 'one_way') {
                    clearReturnDate();
                }

                if (type === 'multi_city' && multiCitySegments.value.every((segment) =>
                    !segment.selectedFrom && !segment.selectedTo && !segment.departureDate
                )) {
                    seedMultiCityFromSingle();
                }

                nextTick(() => {
                    if (typeof window.initFlightMultiCityDatePickers === 'function') {
                        window.initFlightMultiCityDatePickers();
                    }
                });
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

            const parseSegmentsFromUrl = (params) => {
                const segmentsMap = {};

                for (const [key, value] of params.entries()) {
                    const match = key.match(/^segments\[(\d+)\]\[(from|to|departure_date)\]$/);
                    if (!match) continue;

                    const index = Number(match[1]);
                    const field = match[2];
                    segmentsMap[index] = segmentsMap[index] || {};
                    segmentsMap[index][field] = value;
                }

                return Object.keys(segmentsMap)
                    .map((index) => segmentsMap[index])
                    .filter((segment) => segment.from || segment.to || segment.departure_date);
            };

            const applyParamsFromUrl = () => {
                const params = new URLSearchParams(window.location.search);
                const tripTypeParam = params.get('trip_type');
                const rawTrip = tripTypeParam || (params.get('return_date') ? 'round_trip' : 'one_way');
                tripType.value = ['one_way', 'round_trip', 'multi_city'].includes(rawTrip) ? rawTrip : 'one_way';
                directFlight.value = ['1', 'true', 'on'].includes((params.get('direct_flight') || '').toLowerCase());
                nearbyAirports.value = ['1', 'true', 'on'].includes((params.get('nearby_airports') || '').toLowerCase());
                studentFare.value = ['1', 'true', 'on'].includes((params.get('student_fare') || '').toLowerCase());

                const fromCode = (params.get('from') || '').toUpperCase();
                const toCode = (params.get('to') || '').toUpperCase();

                if (fromCode) {
                    const fromAirport = airports.value.find(a => a.code === fromCode);
                    selectedFrom.value = fromAirport || {
                        code: fromCode,
                        city: fromCode
                    };
                    fromInput.value = formatAirportInput(selectedFrom.value);
                }

                if (toCode) {
                    const toAirport = airports.value.find(a => a.code === toCode);
                    selectedTo.value = toAirport || {
                        code: toCode,
                        city: toCode
                    };
                    toInput.value = formatAirportInput(selectedTo.value);
                }

                const adultsParam = parseInt(params.get('adults') || '1', 10);
                const childrenParam = parseInt(params.get('children') || '0', 10);
                const infantsParam = parseInt(params.get('infants') || '0', 10);
                adults.value = Number.isNaN(adultsParam) || adultsParam < 1 ? 1 : adultsParam;
                children.value = Number.isNaN(childrenParam) || childrenParam < 0 ? 0 : childrenParam;
                infants.value = Number.isNaN(infantsParam) || infantsParam < 0 ? 0 : infantsParam;

                if (tripType.value === 'multi_city') {
                    const segments = parseSegmentsFromUrl(params);
                    if (segments.length) {
                        multiCitySegments.value = segments.map((segment) => {
                            const entry = createEmptySegment();
                            const fromAirport = airports.value.find(a => a.code === (segment.from || '').toUpperCase());
                            const toAirport = airports.value.find(a => a.code === (segment.to || '').toUpperCase());

                            if (segment.from) {
                                entry.selectedFrom = fromAirport || {
                                    code: segment.from.toUpperCase(),
                                    city: segment.from.toUpperCase()
                                };
                                entry.fromInput = formatAirportInput(entry.selectedFrom);
                            }

                            if (segment.to) {
                                entry.selectedTo = toAirport || {
                                    code: segment.to.toUpperCase(),
                                    city: segment.to.toUpperCase()
                                };
                                entry.toInput = formatAirportInput(entry.selectedTo);
                            }

                            if (segment.departure_date) {
                                entry.departureDate = parseDisplayDateToIso(segment.departure_date);
                            }

                            return entry;
                        });
                        ensureMinSegments();
                    }
                }
            };

            const adults = ref(1);
            const children = ref(0);
            const infants = ref(0);

            const incrementAdults = () => {
                adults.value++;
            };
            const decrementAdults = () => {
                if (adults.value > 1) adults.value--;
                if (infants.value > adults.value) infants.value = adults.value;
            };
            const incrementChildren = () => {
                children.value++;
            };
            const decrementChildren = () => {
                if (children.value > 0) children.value--;
            };
            const incrementInfants = () => {
                if (infants.value < adults.value) infants.value++;
            };
            const decrementInfants = () => {
                if (infants.value > 0) infants.value--;
            };

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

            const totalPaxCount = computed(() =>
                adults.value + children.value + infants.value);

            const travellersTextCompact = computed(() => {
                const t = totalPaxCount.value;
                return `${t} ${t === 1 ? 'Passenger' : 'Passengers'}`;
            });

            const isSearching = ref(false);
            const isSearchEnabled = computed(() => {
                if (tripType.value === 'multi_city') {
                    return multiCitySegments.value.length >= minMultiCitySegments && multiCitySegments.value.every((segment) => {
                        return !!(segment.selectedFrom && segment.selectedTo && segment.departureDate);
                    });
                }

                if (!selectedFrom.value || !selectedTo.value || !departureDate.value) {
                    return false;
                }

                if (tripType.value === 'round_trip' && !returnDate.value) {
                    return false;
                }

                return true;
            });

            const onFlightSearchSubmit = (event) => {
                if (!isSearchEnabled.value) {
                    event.preventDefault();
                    if (typeof showToast === 'function') {
                        showToast('error', tripType.value === 'multi_city'
                            ? 'Select origin, destination, and departure date for each segment.'
                            : 'Please complete your route and travel dates before searching.');
                    }
                    return;
                }

                if (infants.value > adults.value) {
                    event.preventDefault();
                    if (typeof showToast === 'function') {
                        showToast('error', 'Infants cannot exceed the number of adults.');
                    }
                    return;
                }

                isSearching.value = true;
                window.__enablePageLoaderOnNav = true;
                if (typeof window.showPageLoader === 'function') {
                    window.showPageLoader('Finding the best flights for your trip...');
                }
            };

            const handleDocumentClick = (event) => {
                const clickedInsideSegmentField = [...segmentFromRefs.value, ...segmentToRefs.value]
                    .filter(Boolean)
                    .some((el) => el.contains(event.target));

                if (!clickedInsideSegmentField) {
                    closeAllSegmentDropdowns();
                }
            };

            onMounted(async () => {
                await loadAirports();
                applyParamsFromUrl();
                document.addEventListener('click', handleDocumentClick);
                nextTick(() => {
                    if (typeof window.initFlightMultiCityDatePickers === 'function') {
                        window.initFlightMultiCityDatePickers();
                    }
                });
            });

            onBeforeUnmount(() => {
                document.removeEventListener('click', handleDocumentClick);
            });

            return {
                airports,
                loadingAirports,
                tripType,
                directFlight,
                nearbyAirports,
                studentFare,
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
                swapRoutes,
                clearQuickReturn,
                onwardCabin,
                returnCabin,
                airlineAll,
                airlineLowCost,
                airlineGds,
                airlineNdc,
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
                travellersTextCompact,
                multiCitySegments,
                minMultiCitySegments,
                maxMultiCitySegments,
                setTripType,
                setSegmentFieldRef,
                openSegmentDropdown,
                onSegmentAirportInput,
                filteredSegmentAirports,
                selectSegmentAirport,
                addMultiCitySegment,
                removeMultiCitySegment,
                segmentMinDate,
                segmentDisplayDate,
                segmentDateParts,
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

        function syncFlightReturnPickerMinDate() {
            const $departureInput = $("#flight-departure-input");
            const $returnInput = $("#flight-return-input");
            const returnPicker = $returnInput.data('daterangepicker');
            if (!returnPicker) return;
            const depVal = ($departureInput.val() || '').trim();
            if (depVal) {
                const depMoment = moment(depVal, 'MMM D, YYYY', true);
                if (depMoment.isValid()) {
                    returnPicker.minDate = depMoment.clone().add(1, 'day');
                    return;
                }
            }
            returnPicker.minDate = moment().startOf('day');
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
                    const vue = window.__flightsSearchVue;
                    vue.returnDate = picker.startDate.format(format);
                    if (vue.tripType !== 'round_trip' && typeof vue.setTripType === 'function') {
                        vue.setTripType('round_trip');
                    }
                }
            });

            $wrapper.on("click", function(e) {
                const vue = window.__flightsSearchVue;

                if (wrapperId === "flight-return-box") {
                    if (vue?.tripType !== 'round_trip') {
                        if (vue && typeof vue.setTripType === 'function') {
                            vue.setTripType('round_trip');
                        }
                        setTimeout(function() {
                            if ($(e.target).is($input)) return;
                            const pickerInstance = $input.data('daterangepicker');
                            if (!pickerInstance || window.__flightsSearchVue?.tripType !== 'round_trip') return;
                            syncFlightReturnPickerMinDate();
                            pickerInstance.show();
                        }, 50);
                        return;
                    }
                }

                if (!$(e.target).is($input)) {
                    const pickerInstance = $input.data('daterangepicker');
                    if (pickerInstance) {
                        if (wrapperId === "flight-return-box") syncFlightReturnPickerMinDate();
                        pickerInstance.show();
                    }
                }
            });
        }

        function initFlightMultiCityDatePickers() {
            const vue = window.__flightsSearchVue;
            if (!vue) return;

            $('.fs-multicity__date').each(function() {
                const $wrapper = $(this);
                const index = Number($wrapper.data('index'));
                const $input = $wrapper.find('.fs-multicity__date-input');
                const segment = vue.multiCitySegments?.[index];
                if (!$input.length || !segment) return;

                const minIso = typeof vue.segmentMinDate === 'function' ? vue.segmentMinDate(index) : null;
                const minMoment = minIso ? moment(minIso, 'YYYY-MM-DD') : moment();
                const existingPicker = $input.data('daterangepicker');

                if (existingPicker) {
                    existingPicker.minDate = minMoment.clone();

                    if (segment.departureDate) {
                        const currentMoment = moment(segment.departureDate, 'YYYY-MM-DD', true);
                        if (currentMoment.isValid()) {
                            existingPicker.setStartDate(currentMoment);
                            $input.val(currentMoment.format('MMM D, YYYY'));
                        }
                    } else {
                        $input.val('');
                    }
                    return;
                }

                $input.daterangepicker({
                    singleDatePicker: true,
                    autoApply: true,
                    showDropdowns: true,
                    minDate: minMoment,
                    autoUpdateInput: false,
                    parentEl: $wrapper,
                    locale: {
                        format: "MMM D, YYYY"
                    }
                });

                if (segment.departureDate) {
                    const currentMoment = moment(segment.departureDate, 'YYYY-MM-DD', true);
                    if (currentMoment.isValid()) {
                        const picker = $input.data('daterangepicker');
                        picker.setStartDate(currentMoment);
                        $input.val(currentMoment.format('MMM D, YYYY'));
                    }
                }

                $input.on("apply.daterangepicker", function(ev, picker) {
                    const currentVue = window.__flightsSearchVue;
                    const currentIndex = Number($wrapper.data('index'));
                    const currentSegment = currentVue?.multiCitySegments?.[currentIndex];
                    if (!currentSegment) return;

                    currentSegment.departureDate = picker.startDate.format('YYYY-MM-DD');
                    $input.val(picker.startDate.format('MMM D, YYYY'));

                    nextTick(() => {
                        window.initFlightMultiCityDatePickers?.();
                    });
                });

                $wrapper.on("click", function(e) {
                    if (!$(e.target).is($input)) {
                        const pickerInstance = $input.data('daterangepicker');
                        if (pickerInstance) {
                            pickerInstance.show();
                        }
                    }
                });
            });
        }

        window.initFlightMultiCityDatePickers = initFlightMultiCityDatePickers;

        $(document).ready(function() {
            initFlightSingleDatePicker("flight-departure-box", "flight-departure-input", "flight-departure");
            initFlightSingleDatePicker("flight-return-box", "flight-return-input", "flight-return");
            initFlightMultiCityDatePickers();

            const $departureInput = $("#flight-departure-input");
            const $returnInput = $("#flight-return-input");

            (function populateFromUrl() {
                const params = new URLSearchParams(window.location.search);
                const vue = window.__flightsSearchVue;
                if (!vue) return;

                const dep = params.get('departure_date');
                const ret = params.get('return_date');
                const tripParam = params.get('trip_type');

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

                const shouldUseReturnDate = ret && tripParam !== 'one_way' && tripParam !== 'multi_city';

                if (shouldUseReturnDate) {
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
                } else {
                    $returnInput.val('');
                    clearFlightDateDisplay('flight-return');
                    vue.returnDate = '';
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
