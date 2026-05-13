@push('js')
<script>
    const FLIGHT_SEARCH_ACTION = @json(route('user.flights.search'));
    const RECENT_FLIGHTS_KEY = 'b2b_flight_recent_searches_v1';
    const MAX_RECENT_FLIGHTS = 4;

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

            const cabinOptions = ['Economy', 'Premium Economy', 'Business', 'First'];

            const {
                open: onwardCabinOpen,
                wrapper: onwardCabinRef,
                toggle: toggleOnwardCabin,
                close: closeOnwardCabin
            } = useDropdown();

            const {
                open: returnCabinOpen,
                wrapper: returnCabinRef,
                toggle: toggleReturnCabin,
                close: closeReturnCabin
            } = useDropdown();

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

            const recentSearches = ref([]);

            const loadRecentSearches = () => {
                try {
                    const raw = localStorage.getItem(RECENT_FLIGHTS_KEY);
                    const parsed = raw ? JSON.parse(raw) : [];
                    recentSearches.value = Array.isArray(parsed)
                        ? parsed
                            .filter((e) => e && typeof e.queryString === 'string')
                            .slice(0, MAX_RECENT_FLIGHTS)
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
                recentSearches.value = next.slice(0, MAX_RECENT_FLIGHTS);
                try {
                    localStorage.setItem(RECENT_FLIGHTS_KEY, JSON.stringify(recentSearches.value));
                } catch {
                    /* ignore quota */
                }
            };

            const clearRecentSearches = () => {
                recentSearches.value = [];
                try {
                    localStorage.removeItem(RECENT_FLIGHTS_KEY);
                } catch {
                    /* ignore */
                }
            };

            const buildRecentEntry = (queryString) => {
                let fromCity = ' - ';
                let toCity = ' - ';
                let dateLine = ' - ';

                if (tripType.value === 'multi_city') {
                    const segs = multiCitySegments.value;
                    const first = segs[0];
                    const last = segs[segs.length - 1];
                    fromCity = first?.selectedFrom?.city || first?.selectedFrom?.code || fromCity;
                    toCity = last?.selectedTo?.city || last?.selectedTo?.code || toCity;
                    const parts = segs
                        .map((s) => (s.departureDate ? formatIsoDateForDisplay(s.departureDate) : ''))
                        .filter(Boolean);
                    dateLine = parts.length ? parts.join(' · ') : ' - ';
                } else {
                    fromCity = selectedFrom.value?.city || selectedFrom.value?.code || fromCity;
                    toCity = selectedTo.value?.city || selectedTo.value?.code || toCity;
                    const dep = departureDate.value || '';
                    const ret = tripType.value === 'round_trip' ? (returnDate.value || '') : '';
                    if (dep && ret) {
                        dateLine = `${dep} | ${ret}`;
                    } else {
                        dateLine = dep || ret || ' - ';
                    }
                }

                return {
                    queryString,
                    fromCity,
                    toCity,
                    dateLine,
                    tripType: tripType.value
                };
            };

            const applyRecentSearch = (item) => {
                const qs = item.queryString || '';
                window.location.href = qs
                    ? `${FLIGHT_SEARCH_ACTION}?${qs}`
                    : FLIGHT_SEARCH_ACTION;
            };

            const pickOnwardCabin = (value) => {
                onwardCabin.value = value;
                closeOnwardCabin();
            };

            const pickReturnCabin = (value) => {
                returnCabin.value = value;
                closeReturnCabin();
            };

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
                        day: ' - ',
                        month: '\u00a0',
                        weekday: '\u00a0'
                    };
                }

                const parsed = new Date(`${value}T00:00:00`);
                if (Number.isNaN(parsed.getTime())) {
                    return {
                        day: ' - ',
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
                    if (type !== 'multi_city' && typeof window.initFlightDateRangePicker === 'function') {
                        window.initFlightDateRangePicker();
                    }
                });            };

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

                try {
                    const fd = new FormData(event.currentTarget);
                    const queryString = new URLSearchParams(fd).toString();
                    pushRecentSearch(buildRecentEntry(queryString));
                } catch (err) {
                    console.warn('Recent searches persist failed', err);
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
                loadRecentSearches();
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
                onwardCabinOpen,
                onwardCabinRef,
                toggleOnwardCabin,
                pickOnwardCabin,
                returnCabinOpen,
                returnCabinRef,
                toggleReturnCabin,
                pickReturnCabin,
                cabinOptions,
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
                recentSearches,
                clearRecentSearches,
                applyRecentSearch,
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

        /* syncFlightReturnPickerMinDate removed — replaced by unified range picker */

        

        /**
         * Unified date-range picker for Depart + Return.
         * - round_trip: range mode (2 months side-by-side, pick start + end in one popup)
         * - one_way:    single-date mode (1 month)
         * Clicking either cell opens the same picker. Re-called on every trip-type change.
         */
        function initFlightDateRangePicker() {
            const vue     = window.__flightsSearchVue;
            const $depBox = $('#flight-departure-box');
            const $retBox = $('#flight-return-box');
            const $depIn  = $('#flight-departure-input');
            const $retIn  = $('#flight-return-input');

            if (!$depBox.length || !$depIn.length) return;

            const fmt     = 'MMM D, YYYY';
            const isRound = vue?.tripType === 'round_trip';

            // Tear down previous instance and delegated click handlers
            if ($depIn.data('daterangepicker')) {
                $depIn.data('daterangepicker').remove();
            }
            $depBox.off('click.drp');
            $retBox.off('click.drp');

            const depVal = ($depIn.val() || '').trim();
            const retVal = ($retIn.val() || '').trim();
            const depM   = depVal ? moment(depVal, fmt, true) : null;
            const retM   = retVal ? moment(retVal, fmt, true) : null;

            const opts = {
                autoApply:        !isRound,
                showDropdowns:    true,
                minDate:          moment().startOf('day'),
                autoUpdateInput:  false,
                parentEl:         $depBox,
                opens:            'center',
                drops:            'down',
                linkedCalendars:  false,
                singleDatePicker: !isRound,
                locale:           { format: fmt }
            };

            if (depM?.isValid()) opts.startDate = depM.clone();
            if (isRound && retM?.isValid()) opts.endDate = retM.clone();

            $depIn.daterangepicker(opts);

            $depIn.on('apply.daterangepicker', function (ev, picker) {
                const dep = picker.startDate;
                $depIn.val(dep.format(fmt));
                updateFlightDateDisplay('flight-departure', dep);
                if (vue) vue.departureDate = dep.format(fmt);

                if (!picker.singleDatePicker) {
                    const ret = picker.endDate;
                    $retIn.val(ret.format(fmt));
                    updateFlightDateDisplay('flight-return', ret);
                    if (vue) vue.returnDate = ret.format(fmt);
                }
            });

            // Depart cell click -> open the picker
            $depBox.on('click.drp', function (e) {
                if (!$(e.target).is($depIn)) {
                    $depIn.data('daterangepicker')?.show();
                }
            });

            // Return cell click: switch to round_trip (reinits picker in range mode), then open
            $retBox.on('click.drp', function () {
                if (vue?.tripType !== 'round_trip') {
                    if (vue && typeof vue.setTripType === 'function') {
                        vue.setTripType('round_trip');
                        // setTripType -> nextTick -> initFlightDateRangePicker() opens in range mode
                    }
                    return;
                }
                $depIn.data('daterangepicker')?.show();
            });
        }

        window.initFlightDateRangePicker = initFlightDateRangePicker;
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
            initFlightDateRangePicker();
            initFlightMultiCityDatePickers();

            (function populateFromUrl() {
                const params = new URLSearchParams(window.location.search);
                const vue = window.__flightsSearchVue;
                if (!vue) return;

                const fmt      = 'MMM D, YYYY';
                const dep      = params.get('departure_date');
                const ret      = params.get('return_date');
                const tripParam = params.get('trip_type');

                if (dep) {
                    const d = moment(dep, fmt);
                    if (d.isValid()) {
                        $('#flight-departure-input').val(d.format(fmt));
                        updateFlightDateDisplay('flight-departure', d);
                        vue.departureDate = d.format(fmt);
                    }
                }

                const useReturn = ret && tripParam !== 'one_way' && tripParam !== 'multi_city';
                if (useReturn) {
                    const r = moment(ret, fmt);
                    if (r.isValid()) {
                        $('#flight-return-input').val(r.format(fmt));
                        updateFlightDateDisplay('flight-return', r);
                        vue.returnDate = r.format(fmt);
                    }
                } else {
                    $('#flight-return-input').val('');
                    clearFlightDateDisplay('flight-return');
                    vue.returnDate = '';
                }

                // Re-init picker so it picks up the pre-populated dates
                initFlightDateRangePicker();
            })();
        });    </script>
@endpush
