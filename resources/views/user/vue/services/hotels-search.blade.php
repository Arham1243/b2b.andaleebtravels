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
        return arr.find(o => {
            const value = o[key];
            return value && value.toLowerCase().trim() === q;
        });
    };

    const startsWith = (arr, key, q) =>
        arr.filter(o => {
            const value = o[key];
            return value && value.toLowerCase().startsWith(q);
        });

    const byField = (arr, field, value) =>
        arr.filter(o => o[field] === value);

    const formatResults = ({ countries, provinces, locations }) => ({
        destinations: {
            countries,
            provinces,
            locations
        }
    });

    window.HotelGlobalSearchAPI = async qRaw => {
        const q = qRaw.trim().toLowerCase();

        if (!q) {
            return formatResults({
                countries: [],
                provinces: [],
                locations: []
            });
        }

        const { countries, provinces, locations } = await hotelsDataPromise;

        // COUNTRY EXACT
        const cMatch = exactMatch(countries, 'name', q);
        if (cMatch) {
            const provs = byField(provinces, 'country_id', cMatch.id);
            provs.unshift({ ...cMatch, name: cMatch.name });

            return formatResults({
                countries: [],
                provinces: provs,
                locations: []
            });
        }

        // PROVINCE EXACT
        const pMatch = exactMatch(provinces, 'name', q);
        if (pMatch) {
            const locs = byField(locations, 'province_id', pMatch.id);
            locs.unshift({ ...pMatch, name: pMatch.name });

            return formatResults({
                countries: [],
                provinces: [],
                locations: locs
            });
        }

        // LOCATION EXACT
        const lMatch = exactMatch(locations, 'name', q);
        if (lMatch) {
            return formatResults({
                countries: [],
                provinces: [],
                locations: [lMatch]
            });
        }

        // PARTIAL MATCHES
        const cs = startsWith(countries, 'name', q);
        const ps = startsWith(provinces, 'name', q);
        const ls = startsWith(locations, 'name', q);

        return formatResults({
            countries: cs,
            provinces: ps,
            locations: ls
        });
    };
</script>