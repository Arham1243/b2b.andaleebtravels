<script>
    const countriesDataPromise = fetch(
        "{{ asset('frontend/mocks/yalago_countries.json') }}"
    ).then((r) => r.json());

    const formatCountries = (countries) => ({
        destinations: {
            countries
        },
    });

    window.InsuranceSearchAPI = async (qRaw) => {
        const q = qRaw.trim().toLowerCase();
        if (!q) return formatCountries([]);

        const countries = await countriesDataPromise;

        const cMatch = exactMatch(countries, "name", q);
        if (cMatch) {
            return formatCountries([cMatch]);
        }

        const cs = startsWith(countries, "name", q);
        return formatCountries(cs);
    };
</script>
