<script>
    const hotelsDataPromise = Promise.all([
        fetch("{{ asset('user/mocks/yalago_countries.json') }}").then(r => r.json()),
        fetch("{{ asset('user/mocks/yalago_provinces.json') }}").then(r => r.json())
    ]).then(([countries, provinces]) => ({
        countries,
        provinces
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

    const formatResults = ({ countries, provinces }) => ({
        destinations: {
            countries,
            provinces
        }
    });

    window.HotelGlobalSearchAPI = async qRaw => {
        const q = qRaw.trim().toLowerCase();

        if (!q) {
            return formatResults({
                countries: [],
                provinces: []
            });
        }

        const { countries, provinces } = await hotelsDataPromise;

        // COUNTRY EXACT
        const cMatch = exactMatch(countries, 'name', q);
        if (cMatch) {
            const provs = byField(provinces, 'country_id', cMatch.id);
            provs.unshift({ ...cMatch, name: cMatch.name });

            return formatResults({
                countries: [],
                provinces: provs
            });
        }

        // PROVINCE EXACT
        const pMatch = exactMatch(provinces, 'name', q);
        if (pMatch) {
            return formatResults({
                countries: [],
                provinces: [pMatch]
            });
        }

        // PARTIAL MATCH
        const cs = startsWith(countries, 'name', q);
        const ps = startsWith(provinces, 'name', q);

        return formatResults({
            countries: cs,
            provinces: ps
        });
    };
</script>