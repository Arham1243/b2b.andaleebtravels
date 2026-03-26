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

            const filteredFromAirports = computed(() => filterAirports(fromQuery.value || fromInput.value));
            const filteredToAirports = computed(() => filterAirports(toQuery.value || toInput.value));

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

            const isSearching = ref(false);
            const onFlightSearchSubmit = () => {
                isSearching.value = true;
            };

            onMounted(() => {
                loadAirports();
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
                isSearching,
                onFlightSearchSubmit
            };
        },
    });
    const flightsSearchInstance = FlightsSearch.mount('#flights-search');
    window.__flightsSearchVue = flightsSearchInstance;
</script>
@endpush
