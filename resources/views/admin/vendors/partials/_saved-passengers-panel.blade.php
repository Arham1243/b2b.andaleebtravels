@php
    use App\Models\B2bSavedPassenger;

    $passengersForJs = $savedPassengers->mapWithKeys(function ($passenger) {
        return [$passenger->id => [
            'id' => $passenger->id,
            'passenger_type' => $passenger->passenger_type,
            'title' => $passenger->title,
            'first_name' => $passenger->first_name,
            'last_name' => $passenger->last_name,
            'dob' => $passenger->dob?->format('Y-m-d'),
            'nationality' => $passenger->nationality,
            'issuing_country' => $passenger->issuing_country,
            'passport_no' => $passenger->passport_no,
            'passport_exp' => $passenger->passport_exp?->format('Y-m-d'),
        ]];
    })->all();
@endphp

@push('css')
<style>
    @include('user.flights.partials.hp-pax-autocomplete-styles')
    @include('user.flights.partials.hp-date-picker-styles')
    .vs-ledger-modal .hp-ac-dropdown {
        z-index: 1060;
        border-color: #ebecf0;
    }
    .vs-ledger-modal .hp-date-field .daterangepicker {
        z-index: 1065;
    }
    #vendorPassengerModal .modal-content {
        overflow: visible;
    }
    #vendorPassengerModal .modal-body {
        overflow: visible;
    }
    @media (max-height: 720px) {
        #vendorPassengerModal .modal-body {
            max-height: calc(100vh - 11rem);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #c5c9d2 transparent;
        }
        #vendorPassengerModal .modal-body::-webkit-scrollbar {
            width: 6px;
        }
        #vendorPassengerModal .modal-body::-webkit-scrollbar-thumb {
            background: #c5c9d2;
            border-radius: 999px;
        }
    }
</style>
<link rel="stylesheet" href="{{ asset('user/assets/css/daterangepicker.css') }}" />
@endpush

<div class="vs-tab-panel" id="panel-passengers">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 px-1">
        <p class="mb-0 text-muted" style="font-size:.85rem;">
            Passengers saved for <strong>{{ $vendor->display_agency_name ?: $vendor->name }}</strong> only.
        </p>
        <button type="button"
                class="themeBtn js-vendor-open-passenger-modal"
                style="font-size:.82rem; padding:.4rem 1rem;"
                data-default-type="ADT"
                data-bs-toggle="modal"
                data-bs-target="#vendorPassengerModal">
            <i class="bx bx-plus"></i> Add Passenger
        </button>
    </div>

    @if ($savedPassengers->isNotEmpty())
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Date of Birth</th>
                        <th>Passport</th>
                        <th>Passport Expiry</th>
                        <th>Nationality</th>
                        <th>Issuing Country</th>
                        <th>Saved</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($savedPassengers as $passenger)
                        @php
                            $typeNorm = B2bSavedPassenger::normalizeType($passenger->passenger_type);
                            $typeBadge = match ($typeNorm) {
                                B2bSavedPassenger::TYPE_CHILD => 'bg-warning text-dark',
                                B2bSavedPassenger::TYPE_INFANT => 'bg-info text-dark',
                                default => 'bg-primary',
                            };
                        @endphp
                        <tr>
                            <td>
                                <span class="badge rounded-pill {{ $typeBadge }}" style="font-size:10px;">
                                    {{ $passenger->typeLabel() }}
                                </span>
                            </td>
                            <td class="fw-semibold">
                                {{ $passenger->title }} {{ $passenger->first_name }} {{ $passenger->last_name }}
                            </td>
                            <td style="white-space:nowrap; font-size:12px;">
                                @if ($passenger->dob)
                                    {{ $passenger->dob->format('d M Y') }}
                                    @if ($ageLabel = $passenger->ageLabel())
                                        <div class="text-muted" style="font-size:10px;">Age {{ $ageLabel }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $passenger->passport_no ?: '—' }}</td>
                            <td style="white-space:nowrap; font-size:12px;">{{ $passenger->passport_exp?->format('d M Y') ?: '—' }}</td>
                            <td>{{ $passenger->nationality ?: '—' }}</td>
                            <td>{{ $passenger->issuing_country ?: '—' }}</td>
                            <td style="white-space:nowrap; font-size:12px;">{{ formatDateTime($passenger->created_at) }}</td>
                            <td>
                                <div class="vs-ledger-actions">
                                    <button type="button"
                                            class="vs-view-btn js-vendor-edit-passenger"
                                            data-passenger-id="{{ $passenger->id }}">
                                        <i class="bx bx-edit-alt"></i> Edit
                                    </button>
                                    <form action="{{ route('admin.vendors.saved-passengers.destroy', [$vendor, $passenger]) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Remove this saved passenger for this vendor?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ledger btn-ledger--void">
                                            <i class="bx bx-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-5" style="color:#6b6573;">
            <i class="bx bxs-user-detail" style="font-size:40px; opacity:.35; display:block; margin-bottom:.5rem;"></i>
            <p class="mb-2">This vendor has not saved any passengers yet.</p>
            <button type="button"
                    class="themeBtn js-vendor-open-passenger-modal"
                    style="font-size:.82rem; padding:.4rem 1rem;"
                    data-default-type="ADT"
                    data-bs-toggle="modal"
                    data-bs-target="#vendorPassengerModal">
                <i class="bx bx-plus"></i> Add Passenger
            </button>
        </div>
    @endif
</div>

<div class="modal fade vs-ledger-modal" id="vendorPassengerModal" tabindex="-1" aria-labelledby="vendorPassengerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="vendor-passenger-form"
                  method="POST"
                  action="{{ route('admin.vendors.saved-passengers.store', $vendor) }}">
                @csrf
                <input type="hidden" name="_method" id="vendor-passenger-form-method" value="POST">
                <input type="hidden" name="_passenger_form" id="vendor-passenger-form-mode" value="create">
                <input type="hidden" name="_passenger_id" id="vendor-passenger-id" value="">

                <div class="modal-header">
                    <h5 class="modal-title" id="vendorPassengerModalLabel">Add Passenger</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="modal-body__hint">Saved to this vendor&apos;s profile for faster flight booking.</p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label for="vp_passenger_type">Passenger Type</label>
                                <select name="passenger_type" id="vp_passenger_type" class="field" required>
                                    <option value="ADT">Adult</option>
                                    <option value="CHD">Child</option>
                                    <option value="INF">Infant</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label for="vp_title">Title</label>
                                <select name="title" id="vp_title" class="field" required></select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label for="vp_first_name">First Name</label>
                                <input type="text" name="first_name" id="vp_first_name" class="field" required maxlength="60"
                                       value="{{ old('first_name') }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label for="vp_last_name">Last Name</label>
                                <input type="text" name="last_name" id="vp_last_name" class="field" required maxlength="60"
                                       value="{{ old('last_name') }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label for="vp-dob-display">Date of Birth</label>
                                @include('user.flights.partials.hp-dob-field', [
                                    'name' => 'dob',
                                    'id' => 'vp-dob',
                                    'paxType' => old('passenger_type', 'ADT'),
                                    'travelDate' => now()->format('Y-m-d'),
                                    'required' => false,
                                    'wrapperClass' => false,
                                    'hideLabel' => true,
                                    'inputClass' => 'field hp-date-field__display hp-date-picker-input js-hp-date-display',
                                ])
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label for="vp_passport_no">Passport Number</label>
                                <input type="text" name="passport_no" id="vp_passport_no" class="field" maxlength="20"
                                       value="{{ old('passport_no') }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label for="vp_passport_exp">Passport Expiry</label>
                                <input type="date" name="passport_exp" id="vp_passport_exp" class="field"
                                       value="{{ old('passport_exp') }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label>Nationality</label>
                                <div class="hp-ac-wrap hp-country-ac" data-field-name="nationality">
                                    <input type="text" class="field hp-country-ac-display" placeholder="Type country name or code" autocomplete="off">
                                    <input type="hidden" class="hp-country-ac-value" name="nationality" value="{{ old('nationality') }}">
                                    <div class="hp-ac-dropdown hp-country-ac-dropdown" hidden></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="vs-ledger-modal__field">
                                <label>Issuing Country</label>
                                <div class="hp-ac-wrap hp-country-ac" data-field-name="issuing_country">
                                    <input type="text" class="field hp-country-ac-display" placeholder="Type country name or code" autocomplete="off">
                                    <input type="hidden" class="hp-country-ac-value" name="issuing_country" value="{{ old('issuing_country') }}">
                                    <div class="hp-ac-dropdown hp-country-ac-dropdown" hidden></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="themeBtn"><i class="bx bx-save"></i> Save Passenger</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
    <script src="{{ asset('user/assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('user/assets/js/daterangepicker.min.js') }}"></script>
    @include('user.flights.partials.hp-date-picker-scripts')
    @include('user.flights.partials.hp-passenger-dob-scripts')
    @include('user.flights.partials.hp-pax-autocomplete-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const titleOptions = {
                ADT: @json(B2bSavedPassenger::titleOptionsForType('ADT')),
                CHD: @json(B2bSavedPassenger::titleOptionsForType('CHD')),
                INF: @json(B2bSavedPassenger::titleOptionsForType('INF')),
            };
            const storeUrl = @json(route('admin.vendors.saved-passengers.store', $vendor));
            const updateUrlTemplate = @json(route('admin.vendors.saved-passengers.update', [$vendor, '__ID__']));
            const passengersById = @json($passengersForJs);
            const countries = @json($countries ?? []);
            const referenceDateIso = @json(now()->format('Y-m-d'));

            const modalEl = document.getElementById('vendorPassengerModal');
            const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
            const form = document.getElementById('vendor-passenger-form');
            const methodInput = document.getElementById('vendor-passenger-form-method');
            const modeInput = document.getElementById('vendor-passenger-form-mode');
            const passengerIdInput = document.getElementById('vendor-passenger-id');
            const titleEl = document.getElementById('vendorPassengerModalLabel');
            const typeEl = document.getElementById('vp_passenger_type');
            const titleSelect = document.getElementById('vp_title');
            const dobInput = document.getElementById('vp-dob-value');

            HpPaxForm.init({
                formSelector: '#vendor-passenger-form',
                savedPassengers: [],
                countries: countries,
            });

            HpDatePicker.init({ maxDate: moment().startOf('day'), rootSelector: '#vendorPassengerModal .hp-date-field' });

            HpPassengerDob.init({
                formSelector: '#vendor-passenger-form',
                travelDate: referenceDateIso,
            });

            function syncDobBoundsForType() {
                if (!dobInput || !typeEl) return;
                HpPassengerDob.applyBoundsForType(dobInput, typeEl.value, referenceDateIso);
            }

            function setDobValue(ymd) {
                if (!dobInput || !window.HpDatePicker) return;
                HpDatePicker.setValue(dobInput, ymd || '');
            }

            function fillTitleOptions(type, selected) {
                const options = titleOptions[type] || titleOptions.ADT;
                titleSelect.innerHTML = '';
                Object.entries(options).forEach(function (entry) {
                    const opt = document.createElement('option');
                    opt.value = entry[0];
                    opt.textContent = entry[1];
                    if (selected && selected === entry[0]) {
                        opt.selected = true;
                    }
                    titleSelect.appendChild(opt);
                });
            }

            function countryLabel(code) {
                const normalized = String(code || '').trim().toUpperCase();
                if (!normalized) return '';
                const match = countries.find(function (c) { return c.code === normalized; });
                return match ? match.name + ' (' + match.code + ')' : normalized;
            }

            function setCountryField(fieldName, code) {
                const wrap = form.querySelector('.hp-country-ac[data-field-name="' + fieldName + '"]');
                if (!wrap) return;
                const hidden = wrap.querySelector('.hp-country-ac-value');
                const display = wrap.querySelector('.hp-country-ac-display');
                const normalized = String(code || '').trim().toUpperCase();
                if (hidden) hidden.value = normalized;
                if (display) display.value = countryLabel(normalized);
            }

            function resetForm(defaultType) {
                form.action = storeUrl;
                methodInput.value = 'POST';
                modeInput.value = 'create';
                if (passengerIdInput) passengerIdInput.value = '';
                titleEl.textContent = 'Add Passenger';
                form.reset();
                typeEl.value = defaultType || 'ADT';
                fillTitleOptions(typeEl.value, null);
                setCountryField('nationality', '');
                setCountryField('issuing_country', '');
                setDobValue('');
                syncDobBoundsForType();
            }

            function openCreateModal(defaultType) {
                resetForm(defaultType || 'ADT');
                modal && modal.show();
            }

            function openEditModal(data) {
                if (!data || !data.id) {
                    return;
                }

                form.action = updateUrlTemplate.replace('__ID__', data.id);
                methodInput.value = 'PUT';
                modeInput.value = 'edit';
                if (passengerIdInput) passengerIdInput.value = String(data.id);
                titleEl.textContent = 'Edit Passenger';

                typeEl.value = data.passenger_type || 'ADT';
                fillTitleOptions(typeEl.value, data.title || null);
                document.getElementById('vp_first_name').value = data.first_name || '';
                document.getElementById('vp_last_name').value = data.last_name || '';
                syncDobBoundsForType();
                setDobValue(data.dob || '');
                document.getElementById('vp_passport_no').value = data.passport_no || '';
                document.getElementById('vp_passport_exp').value = data.passport_exp || '';
                setCountryField('nationality', data.nationality || '');
                setCountryField('issuing_country', data.issuing_country || '');

                modal && modal.show();
            }

            document.querySelectorAll('.js-vendor-open-passenger-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openCreateModal(btn.dataset.defaultType || 'ADT');
                });
            });

            document.querySelectorAll('.js-vendor-edit-passenger').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = btn.dataset.passengerId;
                    openEditModal(passengersById[id] || null);
                });
            });

            typeEl?.addEventListener('change', function () {
                fillTitleOptions(typeEl.value, null);
                syncDobBoundsForType();
            });

            @if ($errors->any() && old('_passenger_form'))
                fillTitleOptions(@json(old('passenger_type', 'ADT')), @json(old('title')));
                document.getElementById('vp_first_name').value = @json(old('first_name', ''));
                document.getElementById('vp_last_name').value = @json(old('last_name', ''));
                syncDobBoundsForType();
                setDobValue(@json(old('dob', '')));
                document.getElementById('vp_passport_no').value = @json(old('passport_no', ''));
                document.getElementById('vp_passport_exp').value = @json(old('passport_exp', ''));
                setCountryField('nationality', @json(old('nationality', '')));
                setCountryField('issuing_country', @json(old('issuing_country', '')));

                @if (old('_passenger_form') === 'edit' && old('_passenger_id'))
                    form.action = updateUrlTemplate.replace('__ID__', @json(old('_passenger_id')));
                    methodInput.value = 'PUT';
                    modeInput.value = 'edit';
                    titleEl.textContent = 'Edit Passenger';
                @else
                    form.action = storeUrl;
                    methodInput.value = 'POST';
                    modeInput.value = 'create';
                    titleEl.textContent = 'Add Passenger';
                @endif

                modal && modal.show();
            @endif
        });
    </script>
@endpush
