@include('frontend.vue.services.insurance-search')
<script>
    const InsuranceSearch = createApp({
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

            // Parse URL parameters
            const getUrlParams = () => {
                const params = new URLSearchParams(window.location.search);
                return {
                    origin: params.get('origin'),
                    destination: params.get('destination'),
                    start_date: params.get('start_date'),
                    return_date: params.get('return_date'),
                    residence_country: params.get('residence_country'),
                    adult_count: params.get('adult_count'),
                    children_count: params.get('children_count'),
                    infant_count: params.get('infant_count'),
                    adult_ages: params.getAll('adult_ages[]'),
                    children_ages: params.getAll('children_ages[]')
                };
            };

            onBeforeMount(async () => {
                loadInsuranceFromCountries('a');
                loadInsuranceToCountries('a');
                loadInsuranceResidenceCountries('a');

                // Populate form with URL parameters
                const urlParams = getUrlParams();

                // Set origin
                if (urlParams.origin) {
                    insuranceFromInputValue.value = urlParams.origin;
                    const countries = await getInsuranceCountries(urlParams.origin);
                    const match = countries.find(c => c.name.toLowerCase() === urlParams.origin
                        .toLowerCase());
                    if (match) {
                        selectedInsuranceFrom.value = match;
                    }
                }

                // Set destination
                if (urlParams.destination) {
                    insuranceToInputValue.value = urlParams.destination;
                    const countries = await getInsuranceCountries(urlParams.destination);
                    const match = countries.find(c => c.name.toLowerCase() === urlParams.destination
                        .toLowerCase());
                    if (match) {
                        selectedInsuranceTo.value = match;
                    }
                }

                // Set residence country
                if (urlParams.residence_country) {
                    insuranceResidenceInputValue.value = urlParams.residence_country;
                    const countries = await getInsuranceCountries(urlParams.residence_country);
                    const match = countries.find(c => c.name.toLowerCase() === urlParams
                        .residence_country.toLowerCase());
                    if (match) {
                        selectedInsuranceResidence.value = match;
                    }
                }

                // Set passenger counts
                if (urlParams.adult_count) {
                    insurancePax.value.adults = parseInt(urlParams.adult_count);
                }
                if (urlParams.children_count) {
                    insurancePax.value.children = parseInt(urlParams.children_count);
                }
                if (urlParams.infant_count) {
                    insurancePax.value.infant = parseInt(urlParams.infant_count);
                }

                // Wait for watchers to create age arrays, then populate them
                await nextTick();

                // Set ages by updating existing array elements instead of replacing the array
                if (urlParams.adult_ages && urlParams.adult_ages.length > 0) {
                    urlParams.adult_ages.forEach((age, index) => {
                        if (index < insuranceAdultAges.value.length) {
                            insuranceAdultAges.value[index] = age;
                        }
                    });
                }
                if (urlParams.children_ages && urlParams.children_ages.length > 0) {
                    urlParams.children_ages.forEach((age, index) => {
                        if (index < insuranceChildAges.value.length) {
                            insuranceChildAges.value[index] = age;
                        }
                    });
                }

                // Set dates using jQuery daterangepicker after a short delay
                if (urlParams.start_date || urlParams.return_date) {
                    setTimeout(() => {
                        if (urlParams.start_date) {
                            const $startInput = $('#insurance-start-input');
                            const startMoment = moment(urlParams.start_date, 'MMM D, YYYY');
                            if (startMoment.isValid()) {
                                $startInput.val(startMoment.format('MMM D, YYYY'));
                                $('#insurance-start-day').text(startMoment.format('dddd'));
                                const picker = $startInput.data('daterangepicker');
                                if (picker) {
                                    picker.setStartDate(startMoment);
                                }
                            }
                        }
                        if (urlParams.return_date) {
                            const $returnInput = $('#insurance-return-input');
                            const returnMoment = moment(urlParams.return_date,
                                'MMM D, YYYY');
                            if (returnMoment.isValid()) {
                                $returnInput.val(returnMoment.format('MMM D, YYYY'));
                                $('#insurance-return-day').text(returnMoment.format(
                                'dddd'));
                                const picker = $returnInput.data('daterangepicker');
                                if (picker) {
                                    picker.setStartDate(returnMoment);
                                }
                            }
                        }
                    }, 500);
                }
            });


            const fetchDestinations = async (query) => {
                if (!query) return;
                try {
                    const data = await window.InsuranceSearchAPI(query);
                    return data.destinations;
                } catch (err) {
                    console.error("API Error:", err);
                    return null;
                }
            };

            // Insurance Search Logic
            const insuranceStartDate = ref(null);
            const insuranceReturnDate = ref(null);

            const {
                open: insurancePaxOpen,
                wrapper: insurancePaxRef,
                toggle: toggleInsurancePax
            } = useDropdown();

            const {
                open: insuranceFromDropdownOpen,
                wrapper: insuranceFromWrapperRef,
                toggle: toggleInsuranceFromDropdown
            } = useDropdown();

            const {
                open: insuranceToDropdownOpen,
                wrapper: insuranceToWrapperRef,
                toggle: toggleInsuranceToDropdown
            } = useDropdown();

            const {
                open: insuranceResidenceDropdownOpen,
                wrapper: insuranceResidenceWrapperRef,
                toggle: toggleInsuranceResidenceDropdown
            } = useDropdown();

            const insurancePax = ref({
                adults: 0,
                children: 0,
                infant: 0,
            });

            const insuranceAdultAges = ref([]);
            const insuranceChildAges = ref([]);

            const totalInsurancePersonsText = computed(() => {
                const total = insurancePax.value.adults + insurancePax.value.children;
                return total === 1 ? "1 Person" : `${total} Persons`;
            });

            const incrementInsurance = (key) => {
                insurancePax.value[key] = insurancePax.value[key] + 1;
            };

            const decrementInsurance = (key) => {
                if (insurancePax.value[key] > 0) {
                    insurancePax.value[key] = insurancePax.value[key] - 1;
                }
            };

            // Watch for changes in adults count
            watch(() => insurancePax.value.adults, (newCount, oldCount) => {
                if (newCount > oldCount) {
                    for (let i = oldCount; i < newCount; i++) {
                        insuranceAdultAges.value.push('');
                    }
                } else if (newCount < oldCount) {
                    insuranceAdultAges.value.splice(newCount);
                }
            });

            // Watch for changes in children count
            watch(() => insurancePax.value.children, (newCount, oldCount) => {
                if (newCount > oldCount) {
                    for (let i = oldCount; i < newCount; i++) {
                        insuranceChildAges.value.push('');
                    }
                } else if (newCount < oldCount) {
                    insuranceChildAges.value.splice(newCount);
                }
            });

            function useInsuranceCountryDropdown(fetchCountriesFn) {
                const query = ref('');
                const inputValue = ref('');
                const selected = ref(null);
                const countries = ref([]);
                const loading = ref(false);

                const filteredCountries = computed(() => {
                    if (!query.value) return countries.value;
                    return countries.value.filter(c =>
                        c.name.toLowerCase().includes(query.value.toLowerCase())
                    );
                });

                const loadCountries = async (searchQuery = '') => {
                    loading.value = true;
                    try {
                        countries.value = await fetchCountriesFn(searchQuery);
                    } finally {
                        loading.value = false;
                    }
                };

                const selectCountry = (country, toggleDropdownfn) => {
                    selected.value = country;
                    inputValue.value = country.name;
                    query.value = '';
                    toggleDropdownfn();
                };

                watch(query, (newQuery) => {
                    loadCountries(newQuery);
                });

                return {
                    query,
                    inputValue,
                    selected,
                    countries,
                    filteredCountries,
                    loading,
                    loadCountries,
                    selectCountry
                };
            }

            const getInsuranceCountries = async (searchQuery = '') => {
                try {
                    const data = await window.InsuranceSearchAPI(searchQuery || 'a');
                    return data.destinations.countries || [];
                } catch (err) {
                    console.error("API Error:", err);
                    return [];
                }
            };

            const insuranceFromInputRef = ref(null);
            const onInsuranceFromBoxClick = () => {
                toggleInsuranceFromDropdown();
                insuranceFromInputRef.value?.focus();
            };

            const {
                query: insuranceFromQuery,
                inputValue: insuranceFromInputValue,
                selected: selectedInsuranceFrom,
                filteredCountries: filteredInsuranceFromCountries,
                loading: loadingInsuranceFrom,
                loadCountries: loadInsuranceFromCountries,
                selectCountry: selectInsuranceFrom
            } = useInsuranceCountryDropdown(getInsuranceCountries);

            const insuranceToInputRef = ref(null);
            const onInsuranceToBoxClick = () => {
                toggleInsuranceToDropdown();
                insuranceToInputRef.value?.focus();
            };

            const {
                query: insuranceToQuery,
                inputValue: insuranceToInputValue,
                selected: selectedInsuranceTo,
                filteredCountries: filteredInsuranceToCountries,
                loading: loadingInsuranceTo,
                loadCountries: loadInsuranceToCountries,
                selectCountry: selectInsuranceTo
            } = useInsuranceCountryDropdown(getInsuranceCountries);

            const insuranceResidenceInputRef = ref(null);
            const onInsuranceResidenceBoxClick = () => {
                toggleInsuranceResidenceDropdown();
                insuranceResidenceInputRef.value?.focus();
            };

            const {
                query: insuranceResidenceQuery,
                inputValue: insuranceResidenceInputValue,
                selected: selectedInsuranceResidence,
                filteredCountries: filteredInsuranceResidenceCountries,
                loading: loadingInsuranceResidence,
                loadCountries: loadInsuranceResidenceCountries,
                selectCountry: selectInsuranceResidence
            } = useInsuranceCountryDropdown(getInsuranceCountries);

            const isInsuranceSearchEnabled = computed(() => {
                const hasStartDate = insuranceStartDate.value && insuranceStartDate.value.value !== '';
                const hasReturnDate = insuranceReturnDate.value && insuranceReturnDate.value.value !==
                    '';
                const hasFrom = insuranceFromInputValue.value && insuranceFromInputValue.value
                    .trim() !== '';
                const hasTo = insuranceToInputValue.value && insuranceToInputValue.value.trim() !== '';
                const hasResidence = insuranceResidenceInputValue.value && insuranceResidenceInputValue
                    .value.trim() !== '';
                const hasPersons = insurancePax.value.adults >= 1 || insurancePax.value.children >= 1;

                // Check all adult ages are filled
                const allAdultAgesFilled = insuranceAdultAges.value.length === insurancePax.value
                    .adults &&
                    insuranceAdultAges.value.every(age => age !== '' && age >= 18);

                // Check all child ages are filled
                const allChildAgesFilled = insuranceChildAges.value.length === insurancePax.value
                    .children &&
                    insuranceChildAges.value.every(age => age !== '' && age >= 2);

                return hasStartDate && hasReturnDate && hasFrom && hasTo && hasResidence &&
                    hasPersons &&
                    allAdultAgesFilled && allChildAgesFilled;
            });

            return {
                // Insurance
                insuranceStartDate,
                insuranceReturnDate,
                insurancePaxOpen,
                insurancePaxRef,
                toggleInsurancePax,
                insurancePax,
                insuranceAdultAges,
                insuranceChildAges,
                totalInsurancePersonsText,
                incrementInsurance,
                decrementInsurance,
                insuranceFromInputRef,
                onInsuranceFromBoxClick,
                insuranceFromQuery,
                insuranceFromInputValue,
                selectedInsuranceFrom,
                filteredInsuranceFromCountries,
                loadingInsuranceFrom,
                selectInsuranceFrom,
                insuranceFromDropdownOpen,
                insuranceFromWrapperRef,
                toggleInsuranceFromDropdown,
                insuranceToInputRef,
                onInsuranceToBoxClick,
                insuranceToQuery,
                insuranceToInputValue,
                selectedInsuranceTo,
                filteredInsuranceToCountries,
                loadingInsuranceTo,
                selectInsuranceTo,
                insuranceToDropdownOpen,
                insuranceToWrapperRef,
                toggleInsuranceToDropdown,
                insuranceResidenceInputRef,
                onInsuranceResidenceBoxClick,
                insuranceResidenceQuery,
                insuranceResidenceInputValue,
                selectedInsuranceResidence,
                filteredInsuranceResidenceCountries,
                loadingInsuranceResidence,
                selectInsuranceResidence,
                insuranceResidenceDropdownOpen,
                insuranceResidenceWrapperRef,
                toggleInsuranceResidenceDropdown,
                isInsuranceSearchEnabled,
            };
        },
    });
    InsuranceSearch.mount('#insurance-search');
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
            initSingleDatePicker("insurance-start-box", "insurance-start-input", "insurance-start-day");
            initSingleDatePicker("insurance-return-box", "insurance-return-input", "insurance-return-day");

            const $startInput = $("#insurance-start-input");
            const $returnInput = $("#insurance-return-input");

            // Sync return date with start date
            $startInput.on("apply.daterangepicker", function(ev, picker) {
                const startDate = picker.startDate;
                const returnPicker = $returnInput.data('daterangepicker');

                if (returnPicker) {
                    // Set minimum date for return (day after start)
                    returnPicker.minDate = startDate.clone().add(1, 'day');

                    // Navigate return calendar to same month as start
                    returnPicker.setStartDate(startDate.clone().add(1, 'day'));

                    // If current return date is before new minimum, reset it
                    if ($returnInput.val()) {
                        const currentReturn = moment($returnInput.val(), "MMM D, YYYY");
                        if (currentReturn.isSameOrBefore(startDate)) {
                            $returnInput.val('');
                            $("#insurance-return-day").text('');
                        }
                    }
                }
            });
        });
    </script>
@endpush
