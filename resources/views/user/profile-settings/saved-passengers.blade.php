@extends('user.layouts.main')

@section('css')
    @include('user.profile-settings._styles')
    <style>
        @include('user.flights.partials.hp-pax-autocomplete-styles')

        .ps-pax-tabs {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            margin-bottom: 1rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding-bottom: 4px;
        }
        .ps-pax-tabs::-webkit-scrollbar { display: none; }
        .ps-pax-tabs__btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
            padding: 7px 14px;
            border-radius: 999px;
            border: 1px solid #e4e9f0;
            background: #fff;
            color: #4a5568;
            font-size: .78rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .12s;
        }
        .ps-pax-tabs__btn:hover {
            border-color: #cbd5e1;
            color: #1a2540;
            text-decoration: none;
        }
        .ps-pax-tabs__btn--active {
            background: #fdf1f4;
            border-color: rgba(205, 27, 79, .25);
            color: var(--c-brand, #cd1b4f);
        }
        .ps-pax-tabs__count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #f1f5f9;
            font-size: .68rem;
            font-weight: 700;
        }
        .ps-pax-tabs__btn--active .ps-pax-tabs__count {
            background: rgba(205, 27, 79, .12);
        }
        .ps-pax-table-wrap {
            overflow-x: auto;
            border: 1px solid #e4e9f0;
            border-radius: 10px;
        }
        .ps-pax-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }
        .ps-pax-table th,
        .ps-pax-table td {
            padding: .65rem .85rem;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
            vertical-align: middle;
        }
        .ps-pax-table th {
            background: #f8fafc;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
        }
        .ps-pax-table tr:last-child td { border-bottom: none; }
        .ps-pax-type {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .ps-pax-type--adt { background: #eff6ff; color: #1d4ed8; }
        .ps-pax-type--chd { background: #fef3c7; color: #b45309; }
        .ps-pax-type--inf { background: #fce7f3; color: #be185d; }
        .ps-pax-actions {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
        }
        .ps-pax-actions__btn {
            border: none;
            background: #f1f5f9;
            color: #475569;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background .12s, color .12s;
        }
        .ps-pax-actions__btn:hover { background: #e2e8f0; color: #1e293b; }
        .ps-pax-actions__btn--danger:hover { background: #fee2e2; color: #b91c1c; }
        .ps-pax-empty {
            text-align: center;
            padding: 2.5rem 1rem;
            color: #64748b;
        }
        .ps-pax-empty i { font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: .5rem; }
        .ps-card--passengers { overflow: visible; }
        .ps-pax-modal .modal-content {
            border: 1px solid #e4e9f0;
            border-radius: 14px;
            overflow: hidden;
        }
        .ps-pax-modal .modal-header {
            background: #f8fafc;
            border-bottom: 1px solid #e4e9f0;
        }
        .ps-pax-modal .modal-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1a2540;
        }
        .ps-pax-modal .ps-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .ps-pax-modal .ps-form-grid .ps-field--full { grid-column: 1 / -1; }
        @media (max-width: 576px) {
            .ps-pax-modal .ps-form-grid { grid-template-columns: 1fr; }
        }
    </style>
@endsection

@section('content')
<div class="ps">
    <div class="container">

        <div class="ps-page-head">
            <div class="ps-page-head__icon">
                <i class="bx bxs-group"></i>
            </div>
            <div>
                <h1 class="ps-page-head__title">Account Settings</h1>
                <p class="ps-page-head__sub">Saved passengers for faster flight booking</p>
            </div>
        </div>

        <div class="ps-shell">
            @include('user.profile-settings._sidebar')

            <main class="ps-main">
                <div class="ps-card ps-card--passengers">
                    <div class="ps-card__head">
                        <h2 class="ps-card__title">
                            <i class="bx bxs-user-detail"></i> Saved Passengers
                        </h2>
                        @if ($passengers->isNotEmpty())
                            <button type="button"
                                class="ps-btn-save js-open-passenger-modal"
                                data-default-type="{{ $filter === 'all' ? 'ADT' : strtoupper($filter === 'chd' ? 'CHD' : ($filter === 'inf' ? 'INF' : 'ADT')) }}"
                                data-bs-toggle="modal"
                                data-bs-target="#passengerModal">
                                <i class="bx bx-plus"></i> Add Passenger
                            </button>
                        @endif
                    </div>
                    <div class="ps-card__body">

                        <div class="ps-pax-tabs">
                            @foreach ([
                                'all' => ['label' => 'All', 'icon' => 'bx-group'],
                                'adt' => ['label' => 'Adults', 'icon' => 'bx-user'],
                                'chd' => ['label' => 'Children', 'icon' => 'bx-child'],
                                'inf' => ['label' => 'Infants', 'icon' => 'bxs-baby-carriage'],
                            ] as $tabKey => $tab)
                                <a href="{{ route('user.profile.savedPassengers', ['type' => $tabKey]) }}"
                                   class="ps-pax-tabs__btn {{ $filter === $tabKey ? 'ps-pax-tabs__btn--active' : '' }}">
                                    <i class="bx {{ $tab['icon'] }}"></i>
                                    {{ $tab['label'] }}
                                    <span class="ps-pax-tabs__count">{{ $counts[$tabKey] ?? 0 }}</span>
                                </a>
                            @endforeach
                        </div>

                        @if ($passengers->isEmpty())
                            @php
                                $emptyCopy = match ($filter) {
                                    'adt' => ['message' => 'No saved adults yet.', 'button' => 'Add Adult'],
                                    'chd' => ['message' => 'No saved children yet.', 'button' => 'Add Child'],
                                    'inf' => ['message' => 'No saved infants yet.', 'button' => 'Add Infant'],
                                    default => ['message' => 'No saved passengers yet.', 'button' => 'Add Passenger'],
                                };
                                $defaultType = $filter === 'all'
                                    ? 'ADT'
                                    : strtoupper($filter === 'chd' ? 'CHD' : ($filter === 'inf' ? 'INF' : 'ADT'));
                            @endphp
                            <div class="ps-pax-empty">
                                <i class="bx bx-user-x"></i>
                                <p>{{ $emptyCopy['message'] }}</p>
                                <button type="button"
                                    class="ps-btn-save js-open-passenger-modal"
                                    style="margin-top:.75rem"
                                    data-default-type="{{ $defaultType }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#passengerModal">
                                    <i class="bx bx-plus"></i> {{ $emptyCopy['button'] }}
                                </button>
                            </div>
                        @else
                            <div class="ps-pax-table-wrap">
                                <table class="ps-pax-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Name</th>
                                            <th>Date of Birth</th>
                                            <th>Passport</th>
                                            <th>Nationality</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($passengers as $passenger)
                                            @php
                                                $typeNorm = \App\Models\B2bSavedPassenger::normalizeType($passenger->passenger_type);
                                                $typeClass = match ($typeNorm) {
                                                    \App\Models\B2bSavedPassenger::TYPE_CHILD => 'ps-pax-type--chd',
                                                    \App\Models\B2bSavedPassenger::TYPE_INFANT => 'ps-pax-type--inf',
                                                    default => 'ps-pax-type--adt',
                                                };
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="ps-pax-type {{ $typeClass }}">{{ $passenger->typeLabel() }}</span>
                                                </td>
                                                <td>
                                                    <strong>{{ $passenger->title }} {{ $passenger->first_name }} {{ $passenger->last_name }}</strong>
                                                </td>
                                                <td>
                                                    @if ($passenger->dob)
                                                        {{ $passenger->dob->format('d M Y') }}
                                                        @if ($ageLabel = $passenger->ageLabel())
                                                            <div style="font-size:.72rem;color:#64748b;">Age {{ $ageLabel }}</div>
                                                        @endif
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($passenger->passport_no)
                                                        {{ $passenger->passport_no }}
                                                        @if ($passenger->passport_exp)
                                                            <div style="font-size:.72rem;color:#64748b;">Exp {{ $passenger->passport_exp->format('d M Y') }}</div>
                                                        @endif
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $passenger->nationality ?: '—' }}</td>
                                                <td>
                                                    <div class="ps-pax-actions">
                                                        <button type="button"
                                                            class="ps-pax-actions__btn js-edit-passenger"
                                                            title="Edit"
                                                            data-passenger-id="{{ $passenger->id }}">
                                                            <i class="bx bx-edit"></i>
                                                        </button>
                                                        <form action="{{ route('user.profile.savedPassengers.destroy', $passenger) }}" method="POST"
                                                              onsubmit="return confirm('Remove this saved passenger?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="ps-pax-actions__btn ps-pax-actions__btn--danger" title="Delete">
                                                                <i class="bx bx-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<div class="modal fade ps-pax-modal" id="passengerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="passenger-form" method="POST" action="{{ route('user.profile.savedPassengers.store') }}">
                @csrf
                <input type="hidden" name="_method" id="passenger-form-method" value="POST">

                <div class="modal-header">
                    <h5 class="modal-title" id="passenger-modal-title">Add Passenger</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="ps-form-grid">
                        <div class="ps-field">
                            <label class="ps-field__label">Passenger Type <span class="req">*</span></label>
                            <select name="passenger_type" id="passenger-type" class="ps-field__input" required>
                                <option value="ADT">Adult</option>
                                <option value="CHD">Child</option>
                                <option value="INF">Infant</option>
                            </select>
                        </div>
                        <div class="ps-field">
                            <label class="ps-field__label">Title <span class="req">*</span></label>
                            <select name="title" id="passenger-title" class="ps-field__input" required></select>
                        </div>
                        <div class="ps-field">
                            <label class="ps-field__label">First Name <span class="req">*</span></label>
                            <input type="text" name="first_name" id="passenger-first-name" class="ps-field__input" required maxlength="60"
                                   value="{{ old('first_name') }}">
                        </div>
                        <div class="ps-field">
                            <label class="ps-field__label">Last Name <span class="req">*</span></label>
                            <input type="text" name="last_name" id="passenger-last-name" class="ps-field__input" required maxlength="60"
                                   value="{{ old('last_name') }}">
                        </div>
                        <div class="ps-field">
                            @include('user.flights.partials.hp-dob-field', [
                                'name' => 'dob',
                                'id' => 'passenger-dob',
                                'paxType' => old('passenger_type', 'ADT'),
                                'travelDate' => now()->format('Y-m-d'),
                                'required' => false,
                                'wrapperClass' => false,
                                'labelClass' => 'ps-field__label',
                                'inputClass' => 'ps-field__input hp-date-field__display hp-date-picker-input js-hp-date-display',
                                'showRequired' => false,
                            ])
                        </div>
                        <div class="ps-field">
                            <label class="ps-field__label">Passport Number</label>
                            <input type="text" name="passport_no" id="passenger-passport-no" class="ps-field__input" maxlength="20"
                                   value="{{ old('passport_no') }}">
                        </div>
                        <div class="ps-field">
                            <label class="ps-field__label">Passport Expiry</label>
                            <input type="date" name="passport_exp" id="passenger-passport-exp" class="ps-field__input"
                                   value="{{ old('passport_exp') }}">
                        </div>
                        <div class="ps-field">
                            <label class="ps-field__label">Nationality</label>
                            <div class="hp-ac-wrap hp-country-ac" data-field-name="nationality">
                                <input type="text" class="ps-field__input hp-country-ac-display" placeholder="Type country name or code" autocomplete="off">
                                <input type="hidden" class="hp-country-ac-value" name="nationality" value="{{ old('nationality') }}">
                                <div class="hp-ac-dropdown hp-country-ac-dropdown" hidden></div>
                            </div>
                        </div>
                        <div class="ps-field">
                            <label class="ps-field__label">Issuing Country</label>
                            <div class="hp-ac-wrap hp-country-ac" data-field-name="issuing_country">
                                <input type="text" class="ps-field__input hp-country-ac-display" placeholder="Type country name or code" autocomplete="off">
                                <input type="hidden" class="hp-country-ac-value" name="issuing_country" value="{{ old('issuing_country') }}">
                                <div class="hp-ac-dropdown hp-country-ac-dropdown" hidden></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ps-btn-save">
                        <i class="bx bx-save"></i> Save Passenger
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('user.flights.partials.hp-dob-picker-assets')

@push('js')
    @php
        $passengersForJs = $passengers->mapWithKeys(function ($passenger) {
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
    @include('user.flights.partials.hp-pax-autocomplete-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const titleOptions = {
                ADT: @json(\App\Models\B2bSavedPassenger::titleOptionsForType('ADT')),
                CHD: @json(\App\Models\B2bSavedPassenger::titleOptionsForType('CHD')),
                INF: @json(\App\Models\B2bSavedPassenger::titleOptionsForType('INF')),
            };
            const storeUrl = @json(route('user.profile.savedPassengers.store'));
            const updateUrlTemplate = @json(route('user.profile.savedPassengers.update', ['passenger' => '__ID__']));
            const passengersById = @json($passengersForJs);

            const modalEl = document.getElementById('passengerModal');
            const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
            const form = document.getElementById('passenger-form');
            const methodInput = document.getElementById('passenger-form-method');
            const titleEl = document.getElementById('passenger-modal-title');
            const typeEl = document.getElementById('passenger-type');
            const titleSelect = document.getElementById('passenger-title');
            const dobInput = document.getElementById('passenger-dob-value');
            const referenceDateIso = @json(now()->format('Y-m-d'));
            let modalMode = 'idle';

            HpPaxForm.init({
                formSelector: '#passenger-form',
                savedPassengers: [],
                countries: @json($countries),
            });

            HpDatePicker.init({ maxDate: moment().startOf('day'), rootSelector: '#passenger-form .hp-date-field' });

            HpPassengerDob.init({
                formSelector: '#passenger-form',
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

            const countries = @json($countries);

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
                modalMode = 'create';
                resetForm(defaultType || 'ADT');
                modal && modal.show();
            }

            function openEditModal(data) {
                if (!data || !data.id) {
                    return;
                }

                modalMode = 'edit';
                form.action = updateUrlTemplate.replace('__ID__', data.id);
                methodInput.value = 'PUT';
                titleEl.textContent = 'Edit Passenger';

                typeEl.value = data.passenger_type || 'ADT';
                fillTitleOptions(typeEl.value, data.title);
                document.getElementById('passenger-first-name').value = data.first_name || '';
                document.getElementById('passenger-last-name').value = data.last_name || '';
                syncDobBoundsForType();
                setDobValue(data.dob || '');
                document.getElementById('passenger-passport-no').value = data.passport_no || '';
                document.getElementById('passenger-passport-exp').value = data.passport_exp || '';
                setCountryField('nationality', data.nationality || '');
                setCountryField('issuing_country', data.issuing_country || '');

                modal && modal.show();
            }

            typeEl && typeEl.addEventListener('change', function () {
                fillTitleOptions(typeEl.value, null);
                syncDobBoundsForType();
            });

            modalEl && modalEl.addEventListener('show.bs.modal', function (event) {
                if (modalMode === 'edit') {
                    modalMode = 'idle';
                    return;
                }

                if (modalMode === 'create') {
                    modalMode = 'idle';
                    return;
                }

                const trigger = event.relatedTarget;
                if (!trigger || !trigger.classList.contains('js-open-passenger-modal')) {
                    return;
                }

                resetForm(trigger.getAttribute('data-default-type') || 'ADT');
            });

            document.addEventListener('click', function (event) {
                const editBtn = event.target.closest('.js-edit-passenger');
                if (!editBtn) {
                    return;
                }

                event.preventDefault();
                const passengerId = editBtn.getAttribute('data-passenger-id');
                const data = passengersById[passengerId] || passengersById[String(passengerId)];
                if (data) {
                    openEditModal(data);
                }
            });

            @if ($errors->any())
                openCreateModal(@json(old('passenger_type', 'ADT')));
                typeEl.value = @json(old('passenger_type', 'ADT'));
                fillTitleOptions(typeEl.value, @json(old('title')));
                syncDobBoundsForType();
                setDobValue(@json(old('dob', '')));
            @endif
        });
    </script>
@endpush
