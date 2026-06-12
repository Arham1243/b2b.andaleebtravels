<script>
(function (window) {
    'use strict';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function countryLabel(country) {
        return country.name + ' (' + country.code + ')';
    }

    function buildCountryMaps(countries) {
        const byCode = {};
        countries.forEach(function (country) {
            byCode[country.code] = country;
        });
        return byCode;
    }

    function filterCountries(countries, query) {
        const q = String(query || '').trim().toLowerCase();
        if (!q) {
            return countries.slice(0, 12);
        }

        return countries.filter(function (country) {
            return country.name.toLowerCase().indexOf(q) !== -1
                || country.code.toLowerCase().indexOf(q) !== -1;
        }).slice(0, 12);
    }

    function setCountryField(wrap, code, byCode) {
        const hidden = wrap.querySelector('.hp-country-ac-value');
        const display = wrap.querySelector('.hp-country-ac-display');
        if (!hidden || !display) {
            return;
        }

        const normalized = String(code || '').trim().toUpperCase();
        if (!normalized) {
            hidden.value = '';
            display.value = '';
            display.setCustomValidity('');
            return;
        }

        const match = byCode[normalized];
        hidden.value = normalized;
        display.value = match ? countryLabel(match) : normalized;
        display.setCustomValidity('');
    }

    function initCountryFields(form, countries) {
        const byCode = buildCountryMaps(countries);

        form.querySelectorAll('.hp-country-ac').forEach(function (wrap) {
            const display = wrap.querySelector('.hp-country-ac-display');
            const hidden = wrap.querySelector('.hp-country-ac-value');
            const dropdown = wrap.querySelector('.hp-country-ac-dropdown');
            if (!display || !hidden || !dropdown) {
                return;
            }

            display.setAttribute('autocomplete', 'off');
            hidden.setAttribute('autocomplete', 'off');

            if (hidden.value) {
                setCountryField(wrap, hidden.value, byCode);
            }

            function closeDropdown() {
                dropdown.hidden = true;
                dropdown.innerHTML = '';
            }

            function renderMatches(query) {
                const matches = filterCountries(countries, query);
                if (!matches.length) {
                    dropdown.innerHTML = '<div class="hp-ac-empty">No matching country</div>';
                    dropdown.hidden = false;
                    return;
                }

                dropdown.innerHTML = matches.map(function (country) {
                    return '<button type="button" class="hp-ac-item" data-code="' + escapeHtml(country.code) + '">' +
                        '<span class="hp-ac-item__title">' + escapeHtml(country.name) + '</span>' +
                        '<span class="hp-ac-item__sub">' + escapeHtml(country.code) + '</span>' +
                        '</button>';
                }).join('');
                dropdown.hidden = false;
            }

            display.addEventListener('focus', function () {
                renderMatches(display.value);
            });

            display.addEventListener('input', function () {
                hidden.value = '';
                renderMatches(display.value);
            });

            dropdown.addEventListener('mousedown', function (event) {
                const item = event.target.closest('.hp-ac-item');
                if (!item) {
                    return;
                }
                event.preventDefault();
                setCountryField(wrap, item.dataset.code, byCode);
                closeDropdown();
            });

            display.addEventListener('blur', function () {
                window.setTimeout(function () {
                    closeDropdown();
                    const typed = display.value.trim();
                    if (!typed) {
                        hidden.value = '';
                        display.setCustomValidity('');
                        return;
                    }

                    const codeMatch = typed.match(/\(([A-Z]{2})\)\s*$/i);
                    const directCode = typed.length === 2 ? typed.toUpperCase() : '';
                    const code = codeMatch ? codeMatch[1].toUpperCase() : directCode;

                    if (code && byCode[code]) {
                        setCountryField(wrap, code, byCode);
                        return;
                    }

                    const nameMatch = countries.find(function (country) {
                        return country.name.toLowerCase() === typed.toLowerCase();
                    });
                    if (nameMatch) {
                        setCountryField(wrap, nameMatch.code, byCode);
                        return;
                    }

                    if (hidden.hasAttribute('required')) {
                        display.setCustomValidity('Please select a country from the list.');
                    }
                }, 120);
            });

            display.addEventListener('change', function () {
                display.setCustomValidity('');
            });
        });

        return {
            setCountryField: function (wrap, code) {
                setCountryField(wrap, code, byCode);
            },
            findWrapByFieldName: function (fieldName) {
                return form.querySelector('.hp-country-ac[data-field-name="' + fieldName + '"]');
            },
        };
    }

    function normalizeSavedPaxType(type) {
        const value = String(type || '').trim().toUpperCase();
        if (value === 'C06' || value === 'CHD' || value === 'CNN' || value === 'CH' || value === 'CHL') {
            return 'CHD';
        }
        if (value === 'INF') {
            return 'INF';
        }
        return 'ADT';
    }

    function passengersForSelect(sel, savedPassengers) {
        const wanted = normalizeSavedPaxType(sel.dataset.paxType || 'ADT');
        return savedPassengers.filter(function (passenger) {
            return normalizeSavedPaxType(passenger.passenger_type || 'ADT') === wanted;
        });
    }

    function initSavedPassengerDropdowns(form, savedPassengers, countryApi) {
        const selects = Array.from(form.querySelectorAll('.hp-saved-pick'));
        if (!selects.length || !savedPassengers.length) {
            return;
        }

        function passengerLabel(passenger) {
            return [passenger.title, passenger.first_name, passenger.last_name].filter(Boolean).join(' ');
        }

        function selectedId(sel) {
            if (!sel || !sel.value) {
                return null;
            }
            try {
                const passenger = JSON.parse(sel.value);
                return passenger && passenger.id != null ? String(passenger.id) : null;
            } catch (e) {
                return null;
            }
        }

        function fieldSelector(idx, name) {
            return '[name="passengers[' + idx + '][' + name + ']"]';
        }

        function clearPassengerFields(idx) {
            ['title', 'first_name', 'last_name', 'dob', 'nationality', 'issuing_country', 'passport_no', 'passport_exp']
                .forEach(function (key) {
                    const el = form.querySelector(fieldSelector(idx, key));
                    if (!el) {
                        return;
                    }
                    el.value = '';
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                });

            ['nationality', 'issuing_country'].forEach(function (key) {
                const wrap = countryApi.findWrapByFieldName('passengers[' + idx + '][' + key + ']');
                if (wrap) {
                    countryApi.setCountryField(wrap, '');
                }
            });
        }

        function fillPassengerFields(idx, passenger) {
            function fill(name, value) {
                const el = form.querySelector(fieldSelector(idx, name));
                if (!el || value == null || value === '') {
                    return;
                }
                let next = value;
                if (typeof next === 'string') {
                    next = next.substring(0, 10);
                }
                if (el.classList.contains('js-hp-date-value') && window.HpDatePicker) {
                    window.HpDatePicker.setValue(el, next);
                    return;
                }
                if (el.type === 'date' && typeof next === 'string') {
                    next = next.substring(0, 10);
                }
                el.value = next;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }

            fill('title', passenger.title);
            fill('first_name', passenger.first_name);
            fill('last_name', passenger.last_name);
            fill('dob', passenger.dob);
            fill('passport_no', passenger.passport_no);
            fill('passport_exp', passenger.passport_exp);

            ['nationality', 'issuing_country'].forEach(function (key) {
                const wrap = countryApi.findWrapByFieldName('passengers[' + idx + '][' + key + ']');
                if (wrap && passenger[key]) {
                    countryApi.setCountryField(wrap, passenger[key]);
                }
            });

            if (!passenger.issuing_country && passenger.nationality) {
                const issuingWrap = countryApi.findWrapByFieldName('passengers[' + idx + '][issuing_country]');
                if (issuingWrap) {
                    countryApi.setCountryField(issuingWrap, passenger.nationality);
                }
            }
        }

        function rebuildAll() {
            selects.forEach(function (sel) {
                const idx = sel.dataset.paxIdx;
                const prevVal = sel.value;

                const othersTaken = new Set();
                selects.forEach(function (other) {
                    if (other === sel) {
                        return;
                    }
                    const takenId = selectedId(other);
                    if (takenId) {
                        othersTaken.add(takenId);
                    }
                });

                sel.innerHTML = '<option value="">- Select saved passenger -</option>';
                passengersForSelect(sel, savedPassengers).forEach(function (passenger) {
                    const passengerId = passenger.id != null ? String(passenger.id) : null;
                    if (passengerId && othersTaken.has(passengerId)) {
                        return;
                    }

                    const opt = document.createElement('option');
                    opt.value = JSON.stringify(passenger);
                    opt.textContent = passengerLabel(passenger);
                    sel.appendChild(opt);
                });

                const stillAvailable = prevVal && Array.from(sel.options).some(function (option) {
                    return option.value === prevVal;
                });

                if (stillAvailable) {
                    sel.value = prevVal;
                } else if (prevVal) {
                    sel.selectedIndex = 0;
                    clearPassengerFields(idx);
                }
            });
        }

        selects.forEach(function (sel) {
            sel.addEventListener('change', function () {
                const idx = sel.dataset.paxIdx;

                if (!sel.value) {
                    clearPassengerFields(idx);
                    rebuildAll();
                    return;
                }

                let passenger;
                try {
                    passenger = JSON.parse(sel.value);
                } catch (e) {
                    return;
                }

                fillPassengerFields(idx, passenger);
                rebuildAll();
            });
        });

        rebuildAll();
    }

    window.HpPaxForm = {
        init: function (config) {
            const form = document.querySelector(config.formSelector);
            if (!form) {
                return;
            }

            const countries = Array.isArray(config.countries) ? config.countries : [];
            const savedPassengers = Array.isArray(config.savedPassengers) ? config.savedPassengers : [];
            const countryApi = initCountryFields(form, countries);
            initSavedPassengerDropdowns(form, savedPassengers, countryApi);

            form.addEventListener('submit', function (event) {
                let valid = true;

                form.querySelectorAll('.hp-country-ac').forEach(function (wrap) {
                    const hidden = wrap.querySelector('.hp-country-ac-value');
                    const display = wrap.querySelector('.hp-country-ac-display');
                    if (!hidden || !display || !hidden.hasAttribute('required')) {
                        return;
                    }
                    if (!hidden.value) {
                        display.setCustomValidity('Please select a country from the list.');
                        display.reportValidity();
                        valid = false;
                    }
                });

                if (!valid) {
                    event.preventDefault();
                }
            });
        },
    };
})(window);
</script>
