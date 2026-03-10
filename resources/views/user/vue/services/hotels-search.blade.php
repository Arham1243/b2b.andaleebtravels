<script>
    const hotelsDataPromise = fetch("{{ asset('user/mocks/provinces.json') }}")
        .then(r => r.json())
        .then(provinces => ({
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

    const formatResults = provinces => ({
        destinations: {
            provinces
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
            return formatResults([pMatch]);
        }

        // PARTIAL MATCHES
        const ps = startsWith(provinces, 'name', q);
        return formatResults(ps);
    };
</script>
