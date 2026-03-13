<script>
    const hotelsDataPromise = Promise.all([
        fetch("{{ asset('user/mocks/provinces.json') }}")
        .then(r => r.json())
        .catch(() => []),
        fetch("{{ asset('user/mocks/countries.json') }}")
        .then(r => r.json())
        .catch(() => []),
    ]).then(([provinces, countries]) => ({
        provinces,
        countries
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

    const formatResults = items => ({
        destinations: {
            all: items
        }
    });

    window.HotelGlobalSearchAPI = async qRaw => {
        const q = qRaw.trim().toLowerCase();

        if (!q) {
            return formatResults([]);
        }

        const { provinces, countries } = await hotelsDataPromise;

        // PROVINCE EXACT
        const pMatch = exactMatch(provinces, 'name', q);
        if (pMatch) {
            return formatResults([{
                ...pMatch,
                type: 'province'
            }]);
        }

        // COUNTRY EXACT
        const cMatch = exactMatch(countries, 'name', q);
        if (cMatch) {
            return formatResults([{
                ...cMatch,
                type: 'country'
            }]);
        }

        // PARTIAL MATCHES
        const ps = startsWith(provinces, 'name', q).map(item => ({
            ...item,
            type: 'province'
        }));
        const cs = startsWith(countries, 'name', q).map(item => ({
            ...item,
            type: 'country'
        }));

        return formatResults([...ps, ...cs]);
    };
</script>
