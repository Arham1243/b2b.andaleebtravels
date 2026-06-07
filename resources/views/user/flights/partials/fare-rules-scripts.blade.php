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

    function renderFullFareRules(components) {
        if (!Array.isArray(components) || components.length === 0) {
            return '<p class="fd-rules__full-empty">No detailed fare rules returned for this fare.</p>';
        }

        return components.map((component) => {
            let html = '';

            if (component.route) {
                const basis = component.fare_basis ? ` · ${component.fare_basis}` : '';
                html += `<div class="fd-rules__full-route">${escapeHtml(component.route)}${escapeHtml(basis)}</div>`;
            }

            const sections = Array.isArray(component.sections) ? component.sections : [];
            if (sections.length > 0) {
                sections.forEach((section) => {
                    html += '<div class="fd-rules__full-section">';
                    if (section.title) {
                        html += `<h4>${escapeHtml(section.title)}</h4>`;
                    }
                    (section.paragraphs || []).forEach((paragraph) => {
                        html += `<p>${escapeHtml(paragraph)}</p>`;
                    });
                    html += '</div>';
                });
            } else if (component.text) {
                html += `<div class="fd-rules__full-section"><p>${escapeHtml(component.text)}</p></div>`;
            }

            return `<div class="fd-rules__full-component">${html}</div>`;
        }).join('');
    }

    async function loadIntoWrap(fullWrap, itineraryId, fareIndex, customUrl) {
        if (!fullWrap) return;

        if (fullWrap.dataset.loaded === '1' || fullWrap.dataset.loaded === 'loading') {
            return;
        }

        fullWrap.dataset.loaded = 'loading';

        const status = fullWrap.querySelector('[data-fd-rules-status]');
        const body = fullWrap.querySelector('[data-fd-rules-body]');
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

            if (status) status.hidden = true;
            if (body) {
                body.hidden = false;
                body.innerHTML = renderFullFareRules(data.components || []);
            }
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
        renderFullFareRules,
        loadIntoWrap,
        loadForRoot,
        loadForModal,
        initPageBoxes,
    };
})();
</script>
