<form action="{{ route('frontend.packages.search') }}#tours" class="packages-search-form holidays-search-form" method="GET" v-cloak>
    <select multiple class="packages-search holidays-search-form__input" style="width:100%"></select>
    <input type="hidden" name="search" class="packages-search-hidden" value="{{ request('search') }}">
    <div class="search-button">
        <button disabled type="submit" class="themeBtn themeBtn--primary packages-search-btn">Search</button>
    </div>
</form>
@push('js')
    <script>
        $(document).ready(function() {
            const $select = $('.packages-search');
            const $hidden = $('.packages-search-hidden');
            const $btn = $('.packages-search-btn');

            $select.select2({
                placeholder: 'Search Holidays',
                dropdownParent: $('.packages-search-form'),
                minimumInputLength: 1,
                maximumSelectionLength: 1,
                ajax: {
                    url: '{{ route('frontend.packages.searchNames') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data, params) {
                        return {
                            results: data.results.map(item => ({
                                id: item.text, // internal value
                                text: item.text,
                                searchTerm: params.term
                            }))
                        };
                    },
                    cache: true
                },
                templateResult: function(data) {
                    if (!data.text) return null;

                    const query = data.searchTerm || '';
                    const escaped = query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                    const highlighted = data.text.replace(
                        new RegExp('(' + escaped + ')', 'gi'),
                        '<strong class="highlighted">$1</strong>'
                    );

                    return $('<span>').html(highlighted);
                },
                templateSelection: function(data) {
                    return data.text || data.id;
                }
            });

            // ✅ AUTO-FILL FROM QUERY PARAM
            const initialValue = $hidden.val();

            if (initialValue) {
                const option = new Option(initialValue, initialValue, true, true);
                $select.append(option).trigger('change');
                $btn.prop('disabled', false);
            }

            // ✅ Enable button while typing
            $(document).on('input', '.select2-search__field', function() {
                const val = $(this).val();
                $hidden.val(val);
                $btn.prop('disabled', !val);
            });

            // ✅ Auto-submit on selection
            $select.on('select2:select', function(e) {
                $hidden.val(e.params.data.text);
                $('.packages-search-form').submit();
            });
        });
    </script>
@endpush
