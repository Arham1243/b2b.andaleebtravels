<script>
    const hotelsDataPromise = Promise.all([
        fetch("{{ asset('user/mocks/yalago_countries.json') }}").then(r => r.json()),
        fetch("{{ asset('user/mocks/yalago_provinces.json') }}").then(r => r.json()),
        fetch("{{ asset('user/mocks/yalago_locations.json') }}").then(r => r.json())
    ]).then(([countries, provinces, locations]) => ({
        countries,
        provinces,
        locations
    }));

    const exactMatch = (arr, key, q) => {
        return arr.find((o) => {
            const value = o[key];
            return value && value.toLowerCase().trim() === q;
        });
    };
    const startsWith = (arr, key, q) =>
        arr.filter((o) => {
            const value = o[key];
            return value && value.toLowerCase().startsWith(q);
        });


    const byField = (arr, field, value) => arr.filter(o => o[field] === value);

    const formatHotels = ({
        countries,
        provinces,
        locations,
        hotels
    }) => ({
        destinations: {
            countries,
            provinces,
            locations
        },
        hotels: {
            hotels
        }
    });


    window.HotelGlobalSearchAPI = async qRaw => {
        const q = qRaw.trim().toLowerCase();
        if (!q) return formatHotels({
            countries: [],
            provinces: [],
            locations: [],
            hotels: []
        });
        const {
            countries,
            provinces,
            locations
        } = await hotelsDataPromise;

        const cMatch = exactMatch(countries, 'name', q);
        if (cMatch) {
            const provs = byField(provinces, 'country_id', cMatch.id);
            provs.unshift({
                ...cMatch,
                name: cMatch.name
            });
            return formatHotels({
                countries: [],
                provinces: provs,
                locations: [],
                hotels: []
            });
        }

        const pMatch = exactMatch(provinces, 'name', q);
        if (pMatch) {
            const locs = byField(locations, 'province_id', pMatch.id);
            locs.unshift({
                ...pMatch,
                name: pMatch.name
            });
            return formatHotels({
                countries: [],
                provinces: [],
                locations: locs,
                hotels: []
            });
        }

        const lMatch = exactMatch(locations, 'name', q);
        if (lMatch) {
            try {
                const {
                    data: hotelsForLocation
                } = await axios.get(`{{ route('user.hotels.search-hotels') }}?location_id=${lMatch.id}`);
                return formatHotels({
                    countries: [],
                    provinces: [],
                    locations: [lMatch],
                    hotels: hotelsForLocation
                });
            } catch (error) {
                console.error('Error fetching hotels for location:', error);
                return formatHotels({
                    countries: [],
                    provinces: [],
                    locations: [lMatch],
                    hotels: []
                });
            }
        }


        // Check for partial matches
        const cs = startsWith(countries, 'name', q);
        const ps = startsWith(provinces, 'name', q);
        const ls = startsWith(locations, 'name', q);

        // If no geo data matches, search hotels directly
        if (!cs.length && !ps.length && !ls.length) {
            try {
                const {
                    data
                } = await axios.get(`{{ route('user.hotels.search-hotels') }}?q=${q}`);
                return formatHotels({
                    countries: [],
                    provinces: [],
                    locations: [],
                    hotels: data
                });
            } catch (error) {
                console.error('Error fetching hotels directly:', error);
                return formatHotels({
                    countries: [],
                    provinces: [],
                    locations: [],
                    hotels: []
                });
            }
        }

        return formatHotels({
            countries: cs,
            provinces: ps,
            locations: ls,
            hotels: []
        });
    };
</script>
