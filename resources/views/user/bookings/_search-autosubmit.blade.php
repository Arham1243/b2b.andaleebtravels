<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.bkp-search-form');
    if (!form) return;
    var input = form.querySelector('input[name="search"]');
    if (!input) return;
    var debounceMs = 450;
    var timer = null;

    input.addEventListener('input', function () {
        if (timer) clearTimeout(timer);
        timer = setTimeout(function () {
            timer = null;
            form.submit();
        }, debounceMs);
    });
});
</script>
