<script>
    const hotelsDataPromise = Promise.all([
        fetch("{{ asset('user/mocks/yalago_countries.json') }}").then(r => r.json()),
        fetch("{{ asset('user/mocks/yalago_locations.json') }}").then(r => r.json())
    ]).then(([countries, locations]) => ({
        countries,
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

    const formatResults = ({ countries, locations }) => ({
        destinations: {
            countries,
            locations
        }
    });

    window.HotelGlobalSearchAPI = async qRaw => {
        const q = qRaw.trim().toLowerCase();
        if (!q) return formatResults({ countries: [], locations: [] });

        const { countries, locations } = await hotelsDataPromise;

        const cMatch = exactMatch(countries, 'name', q);
        if (cMatch) {
            const locs = byField(locations, 'country_id', cMatch.id);
            locs.unshift({ ...cMatch, name: cMatch.name });
            return formatResults({ countries: [], locations: locs });
        }

        const lMatch = exactMatch(locations, 'name', q);
        if (lMatch) {
            return formatResults({ countries: [], locations: [lMatch] });
        }

        const cs = startsWith(countries, 'name', q);
        const ls = startsWith(locations, 'name', q);

        return formatResults({ countries: cs, locations: ls });
    };
</script>

