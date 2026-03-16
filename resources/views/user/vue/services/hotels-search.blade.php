<script>
    const hotelsDataPromise = Promise.all([
        fetch("{{ asset('user/mocks/provinces.json') }}")
        .then(r => r.json())
        .catch(() => []),
    ]).then(([provinces]) => ({
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

        const { provinces } = await hotelsDataPromise;

        // PROVINCE EXACT
        const pMatch = exactMatch(provinces, 'name', q);
        if (pMatch) {
            return formatResults([{
                ...pMatch,
                type: 'province'
            }]);
        }

        // PARTIAL MATCHES
        const ps = startsWith(provinces, 'name', q).map(item => ({
            ...item,
            type: 'province'
        }));

        return formatResults(ps);
    };
</script>
