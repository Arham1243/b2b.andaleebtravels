@php
    /** @var array $permissionUi */
    $groupsDefined = isset($permissionUi['groups']) && is_array($permissionUi['groups']) ? $permissionUi['groups'] : [];

    /** @var list<string>|null $selected */
    $selected = $selected ?? [];
@endphp

@foreach ($groupsDefined as $groupBlock)
    @php
        $itemsLoop = isset($groupBlock['items']) && is_array($groupBlock['items']) ? $groupBlock['items'] : [];
    @endphp
    <div class="form-box mb-4" data-portal-role-group-wrap>
        <div
            class="form-box__header d-flex justify-content-between align-items-start flex-wrap gap-2 border-bottom pb-3 mb-3">
            <div class="pe-3">
                <div class="title mb-1">{{ $groupBlock['heading'] ?? 'Permissions' }}</div>
                @if (! empty($groupBlock['subheading']))
                    <div class="small text-muted" style="line-height: 1.6;">{{ $groupBlock['subheading'] }}</div>
                @endif
            </div>
            <div class="d-flex gap-3 align-items-center flex-shrink-0 small">
                <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2"
                    data-portal-role-select-all="check" aria-label="Tick every permission in this section">
                    All in section
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 border-0 text-muted shadow-none text-decoration-underline"
                    style="padding-left: .25rem !important;"
                    data-portal-role-select-all="clear" aria-label="Untick every permission in this section">
                    Clear section
                </button>
            </div>
        </div>
        <div class="form-box__body">
            @foreach ($itemsLoop as $key => $spec)
                @php
                    if (! is_string($key)) {
                        continue;
                    }
                    $label = is_array($spec) ? ($spec['label'] ?? $key) : $spec;
                    $help = is_array($spec) ? ($spec['help'] ?? null) : null;
                    $chk = in_array($key, old('permissions', $selected), true);
                @endphp
                <div
                    class="row align-items-start pb-3 mb-3 @if (! $loop->last) border-bottom @endif">
                    <div class="col-12">
                        <div class="d-flex gap-3">
                            <div class="pt-1">
                                <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                    id="perm-{{ $key }}" data-portal-role-permission=""
                                    {{ $chk ? 'checked' : '' }}>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <label for="perm-{{ $key }}" class="mb-1 d-block fw-semibold"
                                    style="cursor:pointer;">{{ $label }}</label>
                                @if (! empty(trim((string) ($help ?? ''))))
                                    <div class="text-muted" style="font-size:.9rem;line-height:1.55;">
                                        {{ $help }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

@push('js')
    <script>
        (function() {
            function applyGroup(wrap, checking) {
                wrap.querySelectorAll('input[data-portal-role-permission][type=checkbox]').forEach(function(cb) {
                    cb.checked = checking;
                });
            }

            document.querySelectorAll('[data-portal-role-group-wrap]').forEach(function(wrap) {
                wrap.querySelectorAll('[data-portal-role-select-all]').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var mode = btn.getAttribute('data-portal-role-select-all');
                        applyGroup(wrap, mode === 'check');
                    });
                });
            });
        })();
    </script>
@endpush
