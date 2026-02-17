<script>
    const PackagesSearch = createApp({
        setup() {
            const activitySearchQuery = ref('');

            return {
                // Packages
                activitySearchQuery,
            };
        },
    });
    PackagesSearch.mount('#packages-search');
</script>
