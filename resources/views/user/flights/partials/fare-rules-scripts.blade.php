<script>
window.FlightFareRules = (function () {
    let apiUrl = @json(route('user.flights.fare-rules'));

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function parseSectionsFromText(text) {
        const sections = [];
        const blocks = String(text || '').split(/\n{2,}/);

        blocks.forEach((block) => {
            const trimmed = block.trim();
            if (trimmed === '') {
                return;
            }

            const lines = trimmed.split(/\r?\n/);
            const firstLine = (lines[0] || '').trim();
            const rest = lines.slice(1).join('\n').trim();

            if (firstLine !== '' && rest !== '' && firstLine.length <= 120) {
                sections.push({
                    title: firstLine,
                    paragraphs: [rest],
                });
            } else {
                sections.push({
                    title: '',
                    paragraphs: [trimmed],
                });
            }
        });

        return sections;
    }

    function normalizeComponents(components) {
        return (Array.isArray(components) ? components : []).map((component) => {
            const sections = Array.isArray(component.sections) ? component.sections : [];

            if (sections.length > 0) {
                return component;
            }

            const text = String(component.text || '').trim();
            if (text === '') {
                return component;
            }

            return {
                ...component,
                sections: parseSectionsFromText(text),
            };
        });
    }

    function collectSectionTitles(components) {
        const seen = new Set();
        const titles = [];

        normalizeComponents(components).forEach((component) => {
            (Array.isArray(component.sections) ? component.sections : []).forEach((section) => {
                const title = String(section.title || '').trim();
                if (title === '') {
                    return;
                }

                const key = title.toUpperCase();
                if (seen.has(key)) {
                    return;
                }

                seen.add(key);
                titles.push(title);
            });
        });

        return titles.sort((a, b) => a.localeCompare(b));
    }

    function sectionMatchesFilter(section, filterTitle) {
        if (!filterTitle || filterTitle === '__all__') {
            return true;
        }

        return String(section.title || '').trim().toUpperCase() === String(filterTitle).trim().toUpperCase();
    }

    function renderFullFareRules(components, activeTitle) {
        const normalized = normalizeComponents(components);

        if (normalized.length === 0) {
            return '<p class="fd-rules__full-empty">No detailed fare rules returned for this fare.</p>';
        }

        const filterTitle = activeTitle && activeTitle !== '__all__' ? String(activeTitle) : '';
        let rendered = '';

        normalized.forEach((component) => {
            let html = '';
            const sections = Array.isArray(component.sections) ? component.sections : [];
            const visibleSections = sections.filter((section) => sectionMatchesFilter(section, filterTitle));

            if (visibleSections.length === 0 && filterTitle !== '') {
                return;
            }

            if (component.route) {
                const basis = component.fare_basis ? ` · ${component.fare_basis}` : '';
                html += `<div class="fd-rules__full-route">${escapeHtml(component.route)}${escapeHtml(basis)}</div>`;
            }

            if (visibleSections.length > 0) {
                visibleSections.forEach((section) => {
                    html += `<div class="fd-rules__full-section" data-fd-rules-section="${escapeHtml(String(section.title || ''))}">`;
                    if (section.title) {
                        html += `<h4>${escapeHtml(section.title)}</h4>`;
                    }
                    (section.paragraphs || []).forEach((paragraph) => {
                        html += `<p>${escapeHtml(paragraph)}</p>`;
                    });
                    html += '</div>';
                });
            } else if (component.text && filterTitle === '') {
                html += `<div class="fd-rules__full-section"><p>${escapeHtml(component.text)}</p></div>`;
            }

            if (html !== '') {
                rendered += `<div class="fd-rules__full-component">${html}</div>`;
            }
        });

        if (rendered === '') {
            return `<p class="fd-rules__full-empty">No fare rules found for “${escapeHtml(filterTitle)}”.</p>`;
        }

        return rendered;
    }

    function setupSectionFilter(fullWrap, components) {
        const toolbar = fullWrap.querySelector('[data-fd-rules-toolbar]');
        const select = fullWrap.querySelector('[data-fd-rules-filter]');
        const body = fullWrap.querySelector('[data-fd-rules-body]');

        if (!toolbar || !select || !body) {
            return;
        }

        const normalized = normalizeComponents(components);
        const titles = collectSectionTitles(normalized);
        fullWrap._fareRulesComponents = normalized;
        fullWrap._fareRulesSectionTitles = titles;

        if (titles.length === 0) {
            toolbar.hidden = true;
            select.innerHTML = '<option value="__all__">All sections</option>';
            return;
        }

        select.innerHTML = '<option value="__all__">All sections</option>' +
            titles.map((title, index) => (
                `<option value="section-${index}">${escapeHtml(title)}</option>`
            )).join('');

        toolbar.hidden = false;

        if (fullWrap._fareRulesFilterHandler) {
            select.removeEventListener('change', fullWrap._fareRulesFilterHandler);
        }

        fullWrap._fareRulesFilterHandler = function () {
            const selected = select.value;
            const activeTitle = selected === '__all__'
                ? '__all__'
                : (titles[parseInt(String(selected).replace('section-', ''), 10)] || '__all__');

            body.innerHTML = renderFullFareRules(normalized, activeTitle);
        };

        select.addEventListener('change', fullWrap._fareRulesFilterHandler);
        select.value = '__all__';
    }

    async function loadIntoWrap(fullWrap, itineraryId, fareIndex, customUrl) {
        if (!fullWrap) return;

        if (fullWrap.dataset.loaded === '1' || fullWrap.dataset.loaded === 'loading') {
            return;
        }

        fullWrap.dataset.loaded = 'loading';

        const status = fullWrap.querySelector('[data-fd-rules-status]');
        const body = fullWrap.querySelector('[data-fd-rules-body]');
        const toolbar = fullWrap.querySelector('[data-fd-rules-toolbar]');
        if (toolbar) {
            toolbar.hidden = true;
        }

        const url = customUrl
            ? new URL(customUrl, window.location.origin)
            : (() => {
                const searchUrl = new URL(apiUrl, window.location.origin);
                searchUrl.searchParams.set('itinerary', String(itineraryId));
                searchUrl.searchParams.set('fare', String(fareIndex));

                return searchUrl;
            })();

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                if (status) {
                    status.innerHTML = `<p class="fd-rules__full-error">${escapeHtml(data.error || 'Unable to load fare rules.')}</p>`;
                }
                fullWrap.dataset.loaded = 'error';
                return;
            }

            const components = normalizeComponents(Array.isArray(data.components) ? data.components : []);

            if (status) status.hidden = true;
            if (body) {
                body.hidden = false;
                body.innerHTML = renderFullFareRules(components, '__all__');
            }

            setupSectionFilter(fullWrap, components);
            fullWrap.dataset.loaded = '1';
        } catch (error) {
            if (status) {
                status.innerHTML = '<p class="fd-rules__full-error">Unable to load fare rules. Please try again.</p>';
            }
            fullWrap.dataset.loaded = 'error';
        }
    }

    function loadForRoot(root) {
        if (!root) return;

        const customUrl = root.dataset.fareRulesUrl || '';
        const itineraryId = root.dataset.itineraryId;
        const fareIndex = root.dataset.fareIndex ?? '0';
        const fullWrap = root.querySelector('[data-fd-rules-full]');

        if (!fullWrap) return;

        if (customUrl) {
            return loadIntoWrap(fullWrap, null, null, customUrl);
        }

        if (!itineraryId) return;

        return loadIntoWrap(fullWrap, itineraryId, fareIndex, '');
    }

    function loadForModal(modal) {
        if (!modal) return;

        const fareIndex = modal.dataset.activeFare ?? '0';
        const panel = modal.querySelector(`.fd-fare-panel[data-fd-panel="fare-rules"][data-fd-fare-panel="${fareIndex}"]`);
        const fullWrap = panel?.querySelector('[data-fd-rules-full]');
        const itineraryId = (modal.id || '').replace(/^fd-/, '');

        if (!fullWrap || !itineraryId) return;

        return loadIntoWrap(fullWrap, itineraryId, fareIndex);
    }

    function initPageBoxes() {
        document.querySelectorAll('[data-flight-fare-rules]').forEach((root) => {
            loadForRoot(root);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPageBoxes);
    } else {
        initPageBoxes();
    }

    return {
        escapeHtml,
        normalizeComponents,
        collectSectionTitles,
        renderFullFareRules,
        setupSectionFilter,
        loadIntoWrap,
        loadForRoot,
        loadForModal,
        initPageBoxes,
    };
})();
</script>
