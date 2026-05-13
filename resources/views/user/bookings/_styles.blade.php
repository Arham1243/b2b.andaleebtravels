<style>
/* ════════════════════════════════════════════════════════════
   SHARED BOOKINGS PAGES   bkp-* / bkpd-*
   ════════════════════════════════════════════════════════════ */

/* ── Outer shell ─────────────────────────────────────────── */
.bkp { padding: 28px 0 56px; min-height: 82vh; background: #f7f9fc; }

.bkp-shell {
    display: grid;
    grid-template-columns: 230px 1fr;
    gap: 24px;
    align-items: start;
}

/* ── Sidebar nav ─────────────────────────────────────────── */
.bk-nav {
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 14px;
    overflow: hidden;
    position: sticky;
    top: 80px;
}

.bk-nav__head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 16px 14px;
    border-bottom: 1px solid #f0f3f8;
}

.bk-nav__logo {
    width: 34px;
    height: 34px;
    background: var(--c-brand, #cd1b4f);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.bk-nav__title { font-size: .88rem; font-weight: 800; color: #1a2540; line-height: 1.2; }
.bk-nav__sub   { font-size: .65rem; color: #8492a6; }

.bk-nav__menu { padding: 10px 10px 6px; }

.bk-nav__section-label {
    font-size: .6rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #b0bac8;
    padding: 6px 6px 4px;
}

.bk-nav__item {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 10px;
    border-radius: 8px;
    text-decoration: none;
    color: #4a5568;
    font-size: .82rem;
    font-weight: 500;
    transition: background .12s, color .12s;
    margin-bottom: 2px;
    cursor: pointer;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
}

.bk-nav__item:hover:not(.bk-nav__item--disabled) {
    background: #f5f7fa;
    color: #1a2540;
    text-decoration: none;
}

.bk-nav__item--active {
    background: #fdf1f4 !important;
    color: var(--c-brand, #cd1b4f) !important;
    font-weight: 700;
}

.bk-nav__item--disabled {
    opacity: .45;
    cursor: default;
}

.bk-nav__item-icon { font-size: 1rem; flex-shrink: 0; }
.bk-nav__item-text { flex: 1; }

.bk-nav__item-count {
    background: #e4e9f0;
    color: #4a5568;
    font-size: .62rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 10px;
}

.bk-nav__item--active .bk-nav__item-count {
    background: var(--c-brand, #cd1b4f);
    color: #fff;
}

.bk-nav__item-pill {
    font-size: .6rem;
    font-weight: 700;
    background: #f5f7fa;
    border: 1px solid #e4e9f0;
    color: #8492a6;
    padding: 1px 6px;
    border-radius: 10px;
}

.bk-nav__footer {
    padding: 10px;
    border-top: 1px solid #f0f3f8;
}

.bk-nav__new-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px;
    background: var(--c-brand, #cd1b4f);
    color: #fff;
    border-radius: 8px;
    font-size: .78rem;
    font-weight: 700;
    text-decoration: none;
    transition: background .15s;
}

.bk-nav__new-btn:hover { background: #b01542; color: #fff; }

/* ── Main area ───────────────────────────────────────────── */
.bkp-main { min-width: 0; }

/* ── Page header ─────────────────────────────────────────── */
.bkp-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.bkp-header__title {
    font-size: 1.2rem;
    font-weight: 800;
    color: #1a2540;
    margin: 0 0 3px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bkp-header__title i { color: var(--c-brand, #cd1b4f); }
.bkp-header__sub { font-size: .78rem; color: #8492a6; margin: 0; }

.bkp-header__actions {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.bkp-search-box {
    display: flex;
    align-items: center;
    gap: 7px;
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 8px;
    padding: 7px 11px;
}

.bkp-search-box i { color: #8492a6; }

.bkp-search-box input {
    border: none;
    outline: none;
    background: transparent;
    font-size: .82rem;
    color: #1a2540;
    width: 180px;
}

.bkp-filter-chips { display: flex; gap: 6px; flex-wrap: wrap; }

.bkp-chip {
    border: 1px solid #e4e9f0;
    background: #fff;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: .72rem;
    font-weight: 600;
    color: #8492a6;
    cursor: pointer;
    transition: all .12s;
}

.bkp-chip.active {
    border-color: var(--c-brand, #cd1b4f);
    background: #fdf1f4;
    color: var(--c-brand, #cd1b4f);
}

/* ── Booking row card ────────────────────────────────────── */
.bkp-row {
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 12px;
    margin-bottom: 10px;
    overflow: hidden;
    transition: box-shadow .15s;
}

.bkp-row:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }

.bkp-row__main {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
}

/* airline logo */
.bkp-row__logo-wrap {
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    border-radius: 10px;
    border: 1px solid #e4e9f0;
    overflow: hidden;
    background: #fafbff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bkp-row__logo { width: 40px; height: 40px; object-fit: contain; }

.bkp-row__logo-fallback {
    width: 100%;
    height: 100%;
    align-items: center;
    justify-content: center;
    color: #8492a6;
    font-size: 1.3rem;
}

.bkp-row__logo-fallback--hotel { color: #16a34a; }

/* route */
.bkp-row__route { flex: 1; min-width: 0; }

.bkp-row__cities {
    font-size: 1rem;
    font-weight: 800;
    color: #1a2540;
    letter-spacing: .02em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bkp-row__arrow { color: #8492a6; font-size: .85rem; margin: 0 4px; }

.bkp-row__dates {
    font-size: .74rem;
    color: #8492a6;
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* meta */
.bkp-row__meta { min-width: 130px; flex-shrink: 0; }
.bkp-row__num  { font-size: .78rem; font-weight: 700; color: var(--c-brand, #cd1b4f); }
.bkp-row__pnr  { font-size: .7rem; color: #8492a6; margin-top: 2px; font-family: monospace; }
.bkp-row__pnr strong { color: #1a2540; }
.bkp-row__date { font-size: .68rem; color: #b0bac8; margin-top: 1px; }

.bkp-row__supplier {
    display: inline-flex;
    background: #e8f4fd;
    color: #1976d2;
    font-size: .62rem;
    font-weight: 700;
    text-transform: uppercase;
    padding: 2px 7px;
    border-radius: 4px;
    letter-spacing: .05em;
}

/* amount */
.bkp-row__amount { min-width: 100px; text-align: right; flex-shrink: 0; }
.bkp-row__price  { font-size: .9rem; font-weight: 800; color: #1a2540; }
.bkp-row__hold-tag {
    display: inline-block;
    background: #fef9c3;
    color: #92400e;
    font-size: .62rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
    margin-top: 3px;
}

/* status */
.bkp-row__status {
    min-width: 100px;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
    flex-shrink: 0;
}

/* view btn */
.bkp-row__view {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: .78rem;
    font-weight: 700;
    color: var(--c-brand, #cd1b4f);
    text-decoration: none;
    white-space: nowrap;
    flex-shrink: 0;
    padding: 6px 10px;
    border-radius: 6px;
    background: #fdf1f4;
    transition: background .12s;
}

.bkp-row__view:hover { background: var(--c-brand, #cd1b4f); color: #fff; }

/* hold strip */
.bkp-row__hold-strip {
    background: #fffbeb;
    border-top: 1px solid #fcd34d;
    padding: 8px 16px;
    font-size: .74rem;
    color: #78350f;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bkp-row__hold-strip i { color: #d97706; }
.bkp-row__hold-strip a { color: #d97706; font-weight: 700; text-decoration: none; }

/* ── Badges ──────────────────────────────────────────────── */
.bkp-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .02em;
    white-space: nowrap;
}

.bkp-badge--confirmed { background: #dcfce7; color: #15803d; }
.bkp-badge--hold      { background: #fef9c3; color: #a16207; }
.bkp-badge--cancelled { background: #fee2e2; color: #b91c1c; }
.bkp-badge--failed    { background: #fee2e2; color: #b91c1c; }
.bkp-badge--pending   { background: #fff7ed; color: #c2410c; }
.bkp-badge--paid      { background: #dcfce7; color: #15803d; }
.bkp-badge--ticket    { background: #dbeafe; color: #1d4ed8; }
.bkp-badge--issued    { background: #dbeafe; color: #1d4ed8; }

/* ── Buttons ─────────────────────────────────────────────── */
.bkp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 10px 20px;
    border-radius: 9px;
    font-size: .84rem;
    font-weight: 700;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all .15s;
}

.bkp-btn.w-100 { width: 100%; }
.bkp-btn--primary  { background: var(--c-brand, #cd1b4f); color: #fff; }
.bkp-btn--primary:hover { background: #b01542; color: #fff; }
.bkp-btn--danger   { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.bkp-btn--danger:hover  { background: #dc2626; color: #fff; }
.bkp-btn--warning  { background: #fef9c3; color: #92400e; border: 1px solid #fcd34d; }
.bkp-btn--warning:hover { background: #d97706; color: #fff; }
.bkp-btn--outline  { background: #fff; color: #4a5568; border: 1px solid #e4e9f0; }
.bkp-btn--outline:hover { background: #f5f7fa; }

/* ── Empty state ─────────────────────────────────────────── */
.bkp-empty {
    background: #fff;
    border: 1px dashed #dde3ef;
    border-radius: 14px;
    text-align: center;
    padding: 52px 24px;
    color: #b0bac8;
}

.bkp-empty i    { font-size: 3rem; display: block; margin-bottom: 10px; }
.bkp-empty p    { font-size: .9rem; margin: 0 0 16px; }

/* ── Hold expiry banner (detail pages) ───────────────────── */
.bkpd-hold-expiry {
    display: flex;
    align-items: center;
    gap: 14px;
    background: linear-gradient(135deg, #fffbeb 0%, #fef9c3 100%);
    border: 1.5px solid #fcd34d;
    border-left: 5px solid #d97706;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 18px;
}
.bkpd-hold-expiry--urgent {
    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
    border-color: #fb923c;
    border-left-color: #ea580c;
}
.bkpd-hold-expiry--expired {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border-color: #fca5a5;
    border-left-color: #dc2626;
}

.bkpd-hold-expiry__icon {
    font-size: 1.7rem;
    flex-shrink: 0;
    color: #d97706;
    line-height: 1;
}
.bkpd-hold-expiry--urgent .bkpd-hold-expiry__icon { color: #ea580c; }
.bkpd-hold-expiry--expired .bkpd-hold-expiry__icon { color: #dc2626; }

.bkpd-hold-expiry__body { flex: 1; min-width: 0; }

.bkpd-hold-expiry__title {
    font-size: .9rem;
    font-weight: 700;
    color: #92400e;
    margin-bottom: 4px;
    line-height: 1.3;
}
.bkpd-hold-expiry--urgent .bkpd-hold-expiry__title { color: #9a3412; }
.bkpd-hold-expiry--expired .bkpd-hold-expiry__title { color: #991b1b; }

.bkpd-hold-expiry__meta {
    font-size: .76rem;
    color: #a16207;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    line-height: 1.5;
}
.bkpd-hold-expiry--urgent .bkpd-hold-expiry__meta { color: #c2410c; }
.bkpd-hold-expiry--expired .bkpd-hold-expiry__meta { color: #b91c1c; }

.bkpd-hold-expiry__remaining {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-weight: 700;
    color: #d97706;
}
.bkpd-hold-expiry__remaining--urgent { color: #ea580c; }

/* Right pill */
.bkpd-hold-expiry__pill {
    flex-shrink: 0;
    text-align: center;
    background: #fef3c7;
    border: 1.5px solid #fcd34d;
    border-radius: 10px;
    padding: 8px 14px;
    min-width: 90px;
}
.bkpd-hold-expiry__pill--urgent {
    background: #ffedd5;
    border-color: #fb923c;
}
.bkpd-hold-expiry__pill--expired {
    background: #fee2e2;
    border-color: #fca5a5;
}
.bkpd-hold-expiry__pill-top {
    font-size: .82rem;
    font-weight: 800;
    color: #92400e;
    line-height: 1.2;
}
.bkpd-hold-expiry__pill--urgent .bkpd-hold-expiry__pill-top { color: #9a3412; }
.bkpd-hold-expiry__pill--expired .bkpd-hold-expiry__pill-top { color: #991b1b; }
.bkpd-hold-expiry__pill-bot {
    font-size: .64rem;
    color: #a16207;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-top: 2px;
}

/* ── Legacy hold banner (kept for backward compat) ─────── */
.bkp-hold-banner {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fcd34d;
    border-radius: 12px;
    padding: 14px 18px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 18px;
}
.bkp-hold-banner > i { font-size: 1.3rem; color: #d97706; flex-shrink: 0; margin-top: 2px; }
.bkp-hold-banner__title { font-size: .85rem; font-weight: 700; color: #92400e; margin-bottom: 4px; }
.bkp-hold-banner__text  { font-size: .76rem; color: #78350f; line-height: 1.55; }

/* ── Breadcrumb ──────────────────────────────────────────── */
.bkp-crumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .78rem;
    color: #8492a6;
    margin-bottom: 16px;
}

.bkp-crumb a {
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--c-brand, #cd1b4f);
    font-weight: 600;
    text-decoration: none;
}

.bkp-crumb a:hover { text-decoration: underline; }

.bkp-crumb span { font-weight: 600; color: #1a2540; }

/* ── Detail grid ─────────────────────────────────────────── */
.bkpd-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 16px;
    align-items: start;
}

/* ── Detail cards ────────────────────────────────────────── */
.bkpd-card {
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 12px;
    overflow: hidden;
}

.mb-3 { margin-bottom: 14px; }

.bkpd-card__head {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px 12px;
    border-bottom: 2px solid #f0f3f8;
    flex-wrap: wrap;
    background: linear-gradient(to right, #fafbff, #fff);
}

.bkpd-card__title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #1a2540;
    letter-spacing: .02em;
    margin: 0;
}

.bkpd-card__sub { font-size: .73rem; color: #8492a6; margin-top: 2px; }

.bkpd-card__section-head {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--c-brand, #cd1b4f);
    padding: 10px 18px 8px;
    border-bottom: 1px solid #f0f3f8;
    background: #fdf1f4;
}

.bkpd-card__section-head i { font-size: 1rem; }

/* Section head color variants */
.bkpd-card__section-head--blue   { color: #1d4ed8; background: #eff6ff; }
.bkpd-card__section-head--green  { color: #15803d; background: #f0fdf4; }
.bkpd-card__section-head--purple { color: #6d28d9; background: #f5f3ff; }
.bkpd-card__section-head--slate  { color: #4a5568; background: #f8fafc; }

/* PNR row */
.bkpd-pnr-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: linear-gradient(135deg, #f8faff, #f0f7ff);
    border-top: 1px solid #e0e7ff;
}

.bkpd-pnr-label { font-size: .62rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #8492a6; margin-bottom: 4px; }

.bkpd-pnr-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.7rem;
    font-weight: 800;
    color: var(--c-brand, #cd1b4f);
    letter-spacing: .06em;
    line-height: 1;
}

.bkpd-pnr-copy {
    width: 34px;
    height: 34px;
    border: 1px solid #e4e9f0;
    border-radius: 8px;
    background: #fff;
    color: #8492a6;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .15s;
    font-size: .95rem;
    flex-shrink: 0;
}

.bkpd-pnr-copy:hover { background: #4f46e5; color: #fff; border-color: #4f46e5; }
.bkpd-pnr-copy.copied { background: #10b981; color: #fff; border-color: #10b981; }

/* Flight leg visual */
.bkpd-leg { padding: 16px 18px; background: #fff; }
.bkpd-leg--border {
    border-top: 1px solid #bfdbfe;
    background: linear-gradient(135deg, #eff6ff 0%, #fff 60%);
}

.bkpd-leg__label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #8492a6;
    margin-bottom: 14px;
}

.bkpd-leg__date { font-size: .7rem; font-weight: 600; color: #4a5568; }

.bkpd-leg__visual {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.bkpd-leg__logo {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    object-fit: contain;
    border: 1px solid #e4e9f0;
    padding: 2px;
    background: #fafbff;
    flex-shrink: 0;
}

.bkpd-leg__dep, .bkpd-leg__arr { min-width: 56px; }
.bkpd-leg__arr { text-align: right; }

.bkpd-leg__clock {
    font-size: 1.15rem;
    font-weight: 800;
    color: #1a2540;
    letter-spacing: .01em;
}

.bkpd-leg__city {
    font-size: .85rem;
    font-weight: 700;
    color: #1a2540;
    letter-spacing: .03em;
}

.bkpd-leg__city-name { font-size: .65rem; color: #8492a6; }

.bkpd-leg__bridge {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.bkpd-leg__dur { font-size: .7rem; font-weight: 700; color: #4a5568; }

.bkpd-leg__track {
    display: flex;
    align-items: center;
    width: 100%;
}

.bkpd-leg__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #cbd5e0;
    flex-shrink: 0;
}

.bkpd-leg__line {
    flex: 1;
    height: 1px;
    background: #cbd5e0;
}

.bkpd-leg__via {
    font-size: .6rem;
    font-weight: 700;
    background: #f5f7fa;
    border: 1px solid #e4e9f0;
    border-radius: 4px;
    padding: 1px 5px;
    color: #4a5568;
    margin: 0 3px;
}

.bkpd-leg__stops { font-size: .68rem; color: #8492a6; font-weight: 600; }
.bkpd-leg__stops--direct { color: #10b981; }

/* Segment breakdown */
.bkpd-segs { margin-top: 10px; border-top: 1px dashed #e4e9f0; padding-top: 10px; display: flex; flex-direction: column; gap: 6px; }

.bkpd-seg {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: .74rem;
    color: #4a5568;
    background: #f8faff;
    border: 1px solid #e4e9f0;
    border-radius: 6px;
    padding: 6px 10px;
    flex-wrap: wrap;
}

.bkpd-seg__flight { font-weight: 700; color: #1a2540; }
.bkpd-seg__route  { font-weight: 600; }
.bkpd-seg__time   { color: #8492a6; margin-left: auto; }
.bkpd-seg__cabin  { background: #e8f4fd; color: #1976d2; font-size: .62rem; font-weight: 700; padding: 1px 6px; border-radius: 4px; text-transform: uppercase; }

.bkpd-seg-single {
    font-size: .74rem;
    color: #4a5568;
    margin-top: 8px;
    padding: 7px 10px;
    background: #f8faff;
    border: 1px solid #e4e9f0;
    border-radius: 6px;
}

/* Passengers */
.bkpd-pax-list { padding: 12px 18px; display: flex; flex-direction: column; gap: 8px; }

.bkpd-pax {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    background: #f5f3ff;
    border: 1px solid #ddd6fe;
    border-radius: 9px;
    border-left: 3px solid #7c3aed;
}

.bkpd-pax__avatar {
    width: 32px;
    height: 32px;
    background: var(--c-brand, #cd1b4f);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .72rem;
    font-weight: 800;
    flex-shrink: 0;
}

.bkpd-pax__name { font-size: .82rem; font-weight: 700; color: #1a2540; }
.bkpd-pax__meta { font-size: .68rem; color: #8492a6; margin-top: 2px; }

.bkpd-pax__passport {
    margin-left: auto;
    font-size: .68rem;
    color: #4a5568;
    font-family: monospace;
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

/* Fare */
.bkpd-fare { padding: 14px 18px; display: flex; flex-direction: column; gap: 8px; }

.bkpd-fare__row {
    display: flex;
    justify-content: space-between;
    font-size: .82rem;
    color: #4a5568;
}

.bkpd-fare__row--total {
    border-top: 2px solid #e4e9f0;
    padding-top: 10px;
    margin-top: 4px;
    font-size: .95rem;
    font-weight: 800;
    color: #1a2540;
    background: #f0fdf4;
    margin-left: -18px;
    margin-right: -18px;
    padding-left: 18px;
    padding-right: 18px;
    padding-bottom: 10px;
    border-top: 2px solid #86efac;
    color: #15803d;
}

/* Info rows */
.bkpd-info-rows { padding: 12px 18px; display: flex; flex-direction: column; gap: 0; }

.bkpd-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f3f8;
    gap: 12px;
}

.bkpd-info-row:last-child { border-bottom: none; }

.bkpd-info-row__label {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: #8492a6;
    flex-shrink: 0;
}

.bkpd-info-row__val {
    font-size: .82rem;
    color: #1a2540;
    font-weight: 500;
    text-align: right;
}

/* Actions */
.bkpd-actions { padding: 14px 18px; }
.bkpd-no-action { font-size: .8rem; color: #8492a6; margin: 0; display: flex; align-items: center; gap: 6px; }

/* Hotel stay visual */
.bkpd-stay {
    display: flex;
    align-items: center;
    padding: 18px 18px 16px;
    gap: 0;
    background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);
    border-top: 1px solid #bfdbfe;
}

.bkpd-stay__col { flex: 1; }
.bkpd-stay__col--right { text-align: right; }
.bkpd-stay__label { font-size: .62rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #3b82f6; margin-bottom: 4px; }
.bkpd-stay__date { font-size: 2rem; font-weight: 800; color: #1a2540; line-height: 1; }
.bkpd-stay__month { font-size: .72rem; color: #4a5568; font-weight: 600; }

.bkpd-stay__mid {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 0 12px;
}

.bkpd-stay__nights { font-size: .72rem; font-weight: 700; color: #4a5568; }
.bkpd-stay__line   { width: 100%; height: 1px; background: #cbd5e0; }

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 960px) {
    .bkp-shell    { grid-template-columns: 1fr; }
    .bk-nav       { position: static; }
    .bkpd-grid    { grid-template-columns: 1fr; }
    .bkp-row__main { flex-wrap: wrap; }
}
</style>
