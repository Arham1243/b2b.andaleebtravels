<style>
        /* ===== Theme tokens (vivid B2B palette) ===== */
        .fs-pro-enterprise {
            --fs-brand: #cd1b4f;
            --fs-brand-2: #b41642;
            --fs-ink: #15233f;
            --fs-ink-2: #1f2937;
            --fs-slate: #475569;
            --fs-slate-2: #64748b;
            --fs-muted: #94a3b8;
            --fs-line: #e5e9f1;
            --fs-line-soft: #eef2f7;
            --fs-surface: #ffffff;
            --fs-surface-2: #f7f9fc;
            --fs-canvas: #f1f4f9;
            /* Primary UI accent  -  pink SA / brand (maps former “lime” tokens for less churn) */
            --fs-lime: #ee5b8f;
            --fs-lime-2: #cd1b4f;
            --fs-lime-soft: #fce7ef;
            --fs-green: #cd1b4f;
            --fs-green-soft: #fdf2f7;
            --fs-accent-dark: #861043;
            --fs-emerald: #047857;
            --fs-emerald-soft: #ecfdf5;
            --fs-amber: #d97706;
            --fs-amber-soft: #fff8e8;
            --fs-blue: #2563eb;
            --fs-blue-soft: #e8f1ff;
            --fs-sky: #38bdf8;
            --fs-sky-soft: #e0f3ff;
            --fs-mint: #7ed8c6;
            --fs-mint-soft: #ddf6ef;
            --fs-pink: #ec4899;
            --fs-pink-soft: #fde7f3;
            --fs-rose: #f43f5e;
            --fs-rose-soft: #ffe4e6;
            --fs-violet: #8b5cf6;
            --fs-violet-soft: #ede7fe;
            --fs-lavender: #eef0ff;
            --fs-lavender-2: #e0e4ff;
            --fs-shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.05);
            --fs-shadow-md: 0 6px 18px rgba(15, 23, 42, 0.06);
            --fs-shadow-lg: 0 18px 38px -22px rgba(15, 23, 42, 0.28);

            font-family: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
                Helvetica, Arial, sans-serif;
            color: var(--fs-ink);
            letter-spacing: -0.005em;
        }

        .fs-pro-enterprise.hotel-search-redesign {
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .fs-pro-enterprise *,
        .fs-pro-enterprise *::before,
        .fs-pro-enterprise *::after {
            box-sizing: border-box;
        }

        .fs-pro-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 308px;
            gap: 1.25rem;
            align-items: start;
        }

        /* Main search column must stack above sibling grid tracks (aside + promo strip)
           when absolutely positioned overlays extend past the card. */
        .fs-pro-layout__main {
            position: relative;
            z-index: 40;
        }

        .fs-pro-layout > .fs-pro-aside {
            position: relative;
            z-index: 2;
        }

        .fs-pro-layout > .fs-pro-promos {
            position: relative;
            z-index: 1;
        }

        @media (max-width: 1100px) {
            .fs-pro-layout {
                grid-template-columns: 1fr;
            }
        }

        /* ===== Primary card ===== */
        .fs-pro-card {
            position: relative;
            border-radius: 16px;
            border: 1px solid var(--fs-line);
            background: #ffffff;
            box-shadow: var(--fs-shadow-md);
            padding: 1.35rem 1.5rem;
            overflow: visible;
        }

        .fs-pro-card__head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.85rem;
        }

        .fs-pro-eyebrow {
            display: none;
        }

        .fs-pro-card__title {
            margin: 0;
            font-size: 1.62rem;
            line-height: 1.15;
            font-weight: 600;
            color: var(--fs-ink);
            letter-spacing: -0.015em;
        }

        .fs-pro-card__subtitle {
            display: none;
        }

        .fs-pro-specials {
            display: flex;
            flex-wrap: wrap;
            gap: 1.1rem;
        }

        .fs-pro-special-chip {
            display: inline-flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.28rem;
            padding: 0;
            border-radius: 0;
            border: none;
            background: transparent;
            font-weight: 600;
            font-size: 0.92rem;
            color: var(--fs-brand);
            text-decoration: none;
            cursor: pointer;
            transition: opacity 0.15s ease;
            box-shadow: none;
        }

        .fs-pro-special-chip__badge-row {
            display: flex;
            justify-content: flex-end;
            width: 100%;
            min-height: 1.05rem;
        }

        .fs-pro-special-chip__main {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            align-self: flex-start;
        }

        .fs-pro-special-chip:hover {
            opacity: 0.85;
        }

        .fs-pro-special-chip__icon {
            width: 32px;
            height: 22px;
            border-radius: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            background: none !important;
        }

        .fs-pro-special-chip__icon--a2a {
            color: var(--fs-brand);
        }

        .fs-pro-special-chip__icon--ata {
            color: var(--fs-blue);
        }

        .fs-pro-special-chip:has(.fs-pro-special-chip__icon--ata) {
            color: var(--fs-blue);
        }

        .fs-pro-special-chip__text {
            letter-spacing: -0.005em;
        }

        .fs-pro-special-chip .fs-pro-badge-new {
            position: static;
            flex-shrink: 0;
            display: inline-block;
            vertical-align: middle;
        }

        .fs-pro-badge-new {
            font-size: 0.58rem;
            font-weight: 700;
            padding: 0.12rem 0.44rem;
            border-radius: 999px;
            background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
            line-height: 1.2;
            letter-spacing: 0.04em;
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.4);
        }

        .fs-pro-badge-new--blue {
            background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.4);
        }

        .fs-pro-enterprise .mono {
            font-variant-numeric: tabular-nums;
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .fs-pro-enterprise .options-dropdown-wrapper--from {
            left: auto;
            right: auto;
        }

        /* ===== Controls row (trip-type + meta) ===== */
        .fs-pro-controls-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 0.9rem;
        }

        .fs-pro-trip-types {
            margin-bottom: 0 !important;
            gap: 0.32rem !important;
            background: transparent;
            padding: 0;
            border-radius: 0;
            border: none;
        }

        .fs-pro-meta-row {
            display: none;
        }

        /* ===== Route + dates one-row layout ===== */
        .fs-pro-route-sheet {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
            margin-bottom: 0.85rem;
            align-items: stretch;
        }

        .fs-pro-route-pair {
            position: relative;
            display: flex;
            flex: 1 1 280px;
            min-width: 0;
            gap: 0.7rem;
            align-items: stretch;
        }

        .fs-pro-date-pair {
            position: relative;
            display: flex;
            flex: 1 1 280px;
            min-width: 0;
            gap: 0.7rem;
            align-items: stretch;
        }

        .fs-pro-route-pair > .fs-pro-route-field {
            flex: 1 1 0;
            min-width: 0;
        }

        .fs-pro-date-pair > .fs-pro-date-cell {
            flex: 1 1 0;
            min-width: 0;
        }

        @media (max-width: 640px) {
            .fs-pro-route-pair,
            .fs-pro-date-pair {
                flex-direction: column;
                flex-basis: 100%;
            }

            .fs-pro-swap-wrap {
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) rotate(90deg) !important;
            }
        }

        .fs-pro-route-field {
            position: relative;
            overflow: visible;
            min-width: 0;
        }

        .fs-pro-route-field__shell {
            background: var(--fs-surface-2);
            border: 1px solid var(--fs-line);
            border-radius: 10px;
            transition: border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
            min-height: 88px;
            display: flex;
            align-items: stretch;
        }

        .fs-pro-route-field__shell:hover {
            border-color: #c8d1e0;
            background: #fff;
            box-shadow: var(--fs-shadow-sm);
        }

        .fs-pro-route-field .hs-field__inner {
            cursor: pointer;
            padding: 0.7rem 0.95rem !important;
            width: 100%;
        }

        /* Swap sits at route-pair center; halo + diameter intrude ~22px each side  -  keep text clear */
        .fs-pro-route-field.fs-pro-route-field--from .hs-field__inner {
            padding-right: calc(0.95rem + 22px) !important;
        }

        .fs-pro-route-field.fs-pro-route-field--to .hs-field__inner {
            padding-left: calc(0.95rem + 22px) !important;
        }

        .fs-pro-route-field__label {
            font-weight: 500;
            font-size: 0.72rem !important;
            letter-spacing: 0 !important;
            text-transform: uppercase;
            color: #94a3b8 !important;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .fs-pro-route-chosen {
            margin-top: 0.15rem;
        }

        .fs-pro-route-chosen__city {
            display: block;
            font-size: 1.7rem !important;
            font-weight: 700 !important;
            color: #111827;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .fs-pro-route-chosen__airport {
            margin-top: 0.18rem;
            font-size: 0.78rem;
            font-weight: 400;
            color: #6b7280;
            line-height: 1.35;
            display: flex;
            align-items: baseline;
            gap: 0.32rem;
            flex-wrap: wrap;
        }

        .fs-pro-route-chosen__airport .mono {
            color: #374151;
            font-weight: 500;
            font-size: 0.76rem;
            background: transparent;
            padding: 0;
            border-radius: 0;
            border: none;
            letter-spacing: 0;
            font-family: inherit;
        }

        .fs-pro-route-input {
            padding-left: 0 !important;
            font-weight: 600 !important;
            background: transparent !important;
        }

        .fs-pro-route-inline-icon {
            display: none;
        }

        /* Swap button  -  sits between FROM and TO */
        .fs-pro-swap-wrap {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 4;
            pointer-events: none;
        }

        .fs-pro-swap-btn {
            pointer-events: auto;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background: #fff;
            color: var(--fs-lime-2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 0 0 6px #fff, 0 4px 10px rgba(15, 23, 42, 0.12);
            font-size: 1.1rem;
            transition: transform 0.25s cubic-bezier(.2, .8, .2, 1), color 0.18s ease;
        }

        .fs-pro-swap-btn:hover {
            color: var(--fs-accent-dark);
            transform: rotate(180deg);
        }

        .fs-pro-swap-btn:active {
            transform: rotate(180deg) scale(0.94);
        }

        /* ===== Dates (match route field tile) ===== */
        .fs-pro-date-cell {
            position: relative;
            min-width: 0;
        }

        .fs-pro-date-cell .hs-field__inner {
            position: relative;
            border-radius: 10px;
            border: 1px solid var(--fs-line);
            background: var(--fs-surface-2);
            cursor: pointer;
            min-height: 88px;
            padding: 0.7rem 0.95rem !important;
            transition: background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .fs-pro-date-cell:hover .hs-field__inner {
            background: #fff;
            border-color: #c8d1e0;
            box-shadow: var(--fs-shadow-sm);
        }

        .fs-pro-date-label {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.32rem;
            font-weight: 500 !important;
            font-size: 0.72rem !important;
            letter-spacing: 0 !important;
            text-transform: uppercase;
            color: #94a3b8 !important;
        }

        .fs-pro-date-label i {
            font-size: 0.85rem;
            color: var(--fs-slate-2);
        }

        .fs-pro-date-chevron {
            font-size: 10px !important;
            opacity: 0.55;
            margin-left: 0 !important;
            margin-top: 0 !important;
        }

        .fs-pro-enterprise .hs-date-display {
            display: flex;
            align-items: baseline;
            gap: 0.45rem;
            margin-top: 0.18rem;
        }

        .fs-pro-enterprise .hs-date-display__day {
            font-variant-numeric: tabular-nums;
            font-size: 1.7rem !important;
            font-weight: 700 !important;
            color: #111827 !important;
            line-height: 1 !important;
            letter-spacing: -0.02em !important;
        }

        .fs-pro-enterprise .hs-date-display__meta {
            display: flex;
            flex-direction: column;
            gap: 1px;
            padding-bottom: 0.1rem;
        }

        .fs-pro-enterprise .hs-date-display__month {
            font-size: 0.86rem !important;
            font-weight: 600 !important;
            color: #374151 !important;
            text-transform: none;
            letter-spacing: 0;
            line-height: 1.1;
        }

        .fs-pro-enterprise .hs-date-display__weekday {
            font-size: 0.74rem !important;
            font-weight: 400 !important;
            color: #6b7280 !important;
            line-height: 1.2;
        }

        .fs-pro-return-inner {
            padding-right: 2rem !important;
        }

        .fs-pro-return-cell--soft .hs-field__inner {
            background: #f8fafc;
            opacity: 0.85;
        }

        .fs-pro-return-cell--soft .hs-date-display__day,
        .fs-pro-return-cell--soft .hs-date-display__month {
            color: var(--fs-muted) !important;
        }

        .fs-pro-return-clear {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 22px;
            height: 22px;
            padding: 0;
            border: none;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            font-size: 0.9rem;
            transition: color 0.15s ease, background 0.15s ease;
        }

        .fs-pro-return-clear:hover {
            color: #fff;
            background: #6b7280;
        }

        /* ===== Footer (pax / cabin / airlines / submit) ===== */
        .fs-pro-footer {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            margin-top: 0.85rem;
            padding-top: 0;
            border-top: none;
            overflow: visible;
        }

        .fs-pro-pax-cabin-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1fr);
            gap: 0;
            align-items: stretch;
            background: var(--fs-surface-2);
            border: 1px solid var(--fs-line);
            border-radius: 10px;
            overflow: visible;
            position: relative;
        }

        /* Lift entire row above airline bar + filter row when a menu is open (no isolation: isolate  -  that trapped z-index). */
        .fs-pro-pax-cabin-row:has(.options-dropdown-wrapper.open),
        .fs-pro-pax-cabin-row:has(.fs-pro-cabin-dropdown.is-open) {
            z-index: 500;
        }

        @media (max-width: 720px) {
            .fs-pro-pax-cabin-row {
                grid-template-columns: 1fr;
            }
        }

        .fs-pro-travellers,
        .fs-pro-select-group {
            min-width: 0;
            padding: 0.6rem 0.95rem;
            position: relative;
            border-right: 1px solid var(--fs-line);
            z-index: 1;
        }

        /* Ensure passenger & cabin overlays stack above neighbouring cells */
        .fs-pro-enterprise .fs-pro-travellers:has(.options-dropdown-wrapper.open) {
            z-index: 600;
        }

        .fs-pro-select-group.fs-pro-select-group--cabin:has(.fs-pro-cabin-dropdown.is-open) {
            z-index: 600;
        }

        /* Sibling sections after the cabin row paint later in DOM  -  pin them behind open overlays */
        .fs-pro-airline-pref,
        .fs-pro-actions-footer,
        .fs-pro-trust-strip {
            position: relative;
            z-index: 0;
        }

        .fs-pro-pax-cabin-row > :last-child {
            border-right: none;
        }

        @media (max-width: 720px) {
            .fs-pro-travellers,
            .fs-pro-select-group {
                border-right: none;
                border-bottom: 1px solid var(--fs-line);
            }
        }

        .fs-pro-travellers__inner.hs-field__inner {
            cursor: pointer;
            border-radius: 0 !important;
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            min-height: auto;
            box-shadow: none;
        }

        .fs-pro-travellers__inner.hs-field__inner:hover {
            background: transparent !important;
            box-shadow: none;
        }

        .fs-pro-label {
            font-weight: 400 !important;
            letter-spacing: 0 !important;
            font-size: 0.78rem !important;
            text-transform: none;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.3rem;
            color: #6b7280 !important;
        }

        .fs-pro-chevron {
            font-size: 11px !important;
            opacity: 0.55;
            margin-left: 0 !important;
            color: #6b7280;
        }

        .fs-pro-pax-line {
            font-size: 0.98rem !important;
            font-weight: 700 !important;
            color: #111827 !important;
            margin-top: 0.12rem;
            display: block;
        }

        .fs-pro-select-group {
            display: flex;
            flex-direction: column;
        }

        .fs-pro-select-group__label {
            font-weight: 400;
            letter-spacing: 0;
            font-size: 0.78rem;
            text-transform: none;
            color: #6b7280;
            margin-bottom: 0;
            padding: 0;
        }

        .fs-pro-select-wrap {
            position: relative;
            border-radius: 0;
            border: none;
            background: transparent;
            overflow: visible;
            display: flex;
            align-items: center;
            min-height: 0;
            box-shadow: none;
            transition: none;
            margin-top: 0.12rem;
        }

        .fs-pro-select-wrap:hover,
        .fs-pro-select-wrap:focus-within {
            border: none;
            box-shadow: none;
        }

        .fs-pro-select-wrap__icon {
            display: none;
        }

        .fs-pro-select-el {
            appearance: none;
            width: 100%;
            padding: 0 1.4rem 0 0 !important;
            border: none;
            margin: 0;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.98rem;
            font-weight: 700 !important;
            color: #111827;
            outline: none;
            background: transparent;
        }

        .fs-pro-select-el-chevron {
            position: absolute;
            right: 0;
            font-size: 0.95rem;
            color: #6b7280;
            pointer-events: none;
        }

        /* Cabin class  -  custom menu (replacing native <select>) */
        .fs-pro-select-group.fs-pro-select-group--cabin {
            position: relative;
        }

        .fs-pro-cabin-trigger {
            position: relative;
            width: 100%;
            margin-top: 0.12rem;
            padding: 0.15rem 1.55rem 0.15rem 0;
            border: none;
            background: transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
            font-family: inherit;
            font-size: 0.98rem;
            font-weight: 700;
            color: #111827;
            text-align: left;
        }

        .fs-pro-cabin-trigger__chev {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.95rem;
            color: #6b7280;
            pointer-events: none;
        }

        .fs-pro-cabin-trigger:focus-visible {
            outline: 2px solid var(--fs-brand);
            outline-offset: 2px;
            border-radius: 4px;
        }

        .fs-pro-cabin-dropdown {
            position: absolute;
            left: -0.4rem;
            right: -0.4rem;
            top: calc(100% + 6px);
            background: #fff;
            border: 1px solid var(--fs-line);
            border-radius: 10px;
            box-shadow:
                0 4px 6px rgba(15, 23, 42, 0.04),
                0 14px 32px rgba(15, 23, 42, 0.1);
            padding: 0.35rem 0;
            z-index: 55;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-4px);
            transition:
                opacity 0.18s ease,
                transform 0.18s ease,
                visibility 0.18s;
        }

        .fs-pro-cabin-dropdown.is-open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0);
        }

        .fs-pro-cabin-option {
            width: 100%;
            padding: 0.55rem 0.95rem;
            border: none;
            background: transparent;
            cursor: pointer;
            text-align: left;
            font-family: inherit;
            font-size: 0.87rem;
            font-weight: 600;
            color: #374151;
            transition: background 0.12s ease, color 0.12s ease;
        }

        .fs-pro-cabin-option:hover {
            background: var(--fs-surface-2);
            color: #111827;
        }

        .fs-pro-cabin-option.is-active {
            background: rgba(205, 27, 79, 0.12);
            color: var(--fs-brand-2);
            font-weight: 700;
        }

        .fs-pro-enterprise .options-dropdown-wrapper--pax.open {
            z-index: 6000 !important;
        }

        /* date / airport overlays inside the redesigned card  -  stay above promo strip siblings */
        .fs-pro-enterprise .options-dropdown-wrapper--from.open {
            z-index: 5500 !important;
        }

        /* ═══════════════════════════════════════════════════════════
           DATERANGEPICKER - full reset + redesign
           style.css uses `body .daterangepicker` (lower specificity).
           Nested: `.flight-search-redesign .daterangepicker …`
           Body-mounted (hotels): `.daterangepicker.flight-search-redesign …`
           ═══════════════════════════════════════════════════════════ */

        /* ── Picker container ── */
        .flight-search-redesign .daterangepicker,
        .daterangepicker.flight-search-redesign {
            /* Body-mounted pickers (hotels: parentEl body) sit outside .fs-pro-enterprise; define tokens here
               so rules like `border-bottom: solid var(--fs-brand)` are valid. Nested flights inherit either chain. */
            --fs-brand: #cd1b4f;
            --fs-brand-2: #b41642;
            z-index: 5600 !important;
            background: #fff !important;
            border: 1px solid #dde3ef !important;
            border-radius: 16px !important;
            box-shadow: 0 20px 60px rgba(15,23,42,.18) !important;
            font-family: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif !important;
            width: auto !important;
            min-width: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        /* Suppress the default caret arrows */
        .flight-search-redesign .daterangepicker::before,
        .daterangepicker.flight-search-redesign::before {
            display: none !important;
        }

        /* ── Two calendar panels side by side ── */
        .flight-search-redesign .daterangepicker,
        .daterangepicker.flight-search-redesign {
            white-space: nowrap !important;
        }

        .flight-search-redesign .daterangepicker .drp-calendar,
        .daterangepicker.flight-search-redesign .drp-calendar {
            display: inline-block !important;
            vertical-align: top !important;
            float: none !important;
            clear: none !important;
            width: auto !important;
            min-width: 0 !important;
            max-width: none !important;
            white-space: normal !important;
            padding: 14px 12px 10px !important;
        }

        .flight-search-redesign .daterangepicker .drp-calendar.left,
        .daterangepicker.flight-search-redesign .drp-calendar.left {
            border-right: 1px solid #edf0f7 !important;
        }

        .flight-search-redesign .daterangepicker .drp-buttons,
        .daterangepicker.flight-search-redesign .drp-buttons {
            display: none !important;
        }

        /* ── Cell sizing - override style.css 44px ── */
        .flight-search-redesign .daterangepicker .calendar-table th,
        .flight-search-redesign .daterangepicker .calendar-table td,
        .daterangepicker.flight-search-redesign .calendar-table th,
        .daterangepicker.flight-search-redesign .calendar-table td {
            width:  38px !important;
            height: 36px !important;
            min-width: 38px !important;
            line-height: 36px !important;
            font-size: 0.82rem !important;
            font-weight: 500 !important;
            font-family: inherit !important;
            color: #1a2540 !important;
            border-radius: 6px !important;
        }

        /* ── Month header row ── */
        .flight-search-redesign .daterangepicker .calendar-table th,
        .daterangepicker.flight-search-redesign .calendar-table th {
            color: #8492a6 !important;
            font-size: 0.68rem !important;
            font-weight: 700 !important;
            letter-spacing: .04em !important;
            text-transform: uppercase !important;
        }

        .flight-search-redesign .daterangepicker .calendar-table th.month,
        .daterangepicker.flight-search-redesign .calendar-table th.month {
            color: #1a2540 !important;
            font-size: 0.84rem !important;
            font-weight: 700 !important;
            letter-spacing: 0 !important;
            text-transform: none !important;
            position: static !important;
        }

        .flight-search-redesign .daterangepicker .drp-calendar:not(.single) .calendar-table th.month::after,
        .daterangepicker.flight-search-redesign .drp-calendar:not(.single) .calendar-table th.month::after {
            display: none !important;
            content: none !important;
        }

        /* ── Navigation arrows ── */
        .flight-search-redesign .daterangepicker th.prev,
        .flight-search-redesign .daterangepicker th.next,
        .daterangepicker.flight-search-redesign th.prev,
        .daterangepicker.flight-search-redesign th.next {
            border-radius: 8px !important;
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            transition: background .13s !important;
            color: #cd1b4f !important;
        }

        .flight-search-redesign .daterangepicker th.prev:hover,
        .flight-search-redesign .daterangepicker th.next:hover,
        .daterangepicker.flight-search-redesign th.prev:hover,
        .daterangepicker.flight-search-redesign th.next:hover {
            background: rgba(205,27,79,.1) !important;
        }

        .flight-search-redesign .daterangepicker .prev span,
        .flight-search-redesign .daterangepicker .next span,
        .daterangepicker.flight-search-redesign .prev span,
        .daterangepicker.flight-search-redesign .next span {
            border-color: #cd1b4f !important;
        }

        .flight-search-redesign .daterangepicker .drp-calendar.left th.next,
        .flight-search-redesign .daterangepicker .drp-calendar.right th.prev,
        .daterangepicker.flight-search-redesign .drp-calendar.left th.next,
        .daterangepicker.flight-search-redesign .drp-calendar.right th.prev {
            visibility: hidden !important;
        }

        /* ── Month / Year selects ── */
        .flight-search-redesign .daterangepicker select.monthselect,
        .flight-search-redesign .daterangepicker select.yearselect,
        .daterangepicker.flight-search-redesign select.monthselect,
        .daterangepicker.flight-search-redesign select.yearselect {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            border: 1.5px solid #dde3ef !important;
            border-radius: 7px !important;
            padding: 4px 22px 4px 9px !important;
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            color: #1a2540 !important;
            background-color: #fff !important;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath fill='%238492a6' d='M5 8l5 5 5-5z'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 4px center !important;
            background-size: 16px !important;
            cursor: pointer !important;
            outline: none !important;
            height: auto !important;
            line-height: 1.4 !important;
            transition: border-color .15s !important;
        }

        .flight-search-redesign .daterangepicker select.monthselect,
        .daterangepicker.flight-search-redesign select.monthselect {
            width: 58% !important;
        }

        .flight-search-redesign .daterangepicker select.yearselect,
        .daterangepicker.flight-search-redesign select.yearselect {
            width: 38% !important;
        }

        .flight-search-redesign .daterangepicker select.monthselect:hover,
        .flight-search-redesign .daterangepicker select.yearselect:hover,
        .daterangepicker.flight-search-redesign select.monthselect:hover,
        .daterangepicker.flight-search-redesign select.yearselect:hover {
            border-color: #cd1b4f !important;
        }

        .flight-search-redesign .daterangepicker select.monthselect:focus,
        .flight-search-redesign .daterangepicker select.yearselect:focus,
        .daterangepicker.flight-search-redesign select.monthselect:focus,
        .daterangepicker.flight-search-redesign select.yearselect:focus {
            border-color: #cd1b4f !important;
            box-shadow: 0 0 0 2px rgba(205,27,79,.14) !important;
        }

        /* ── Calendar table ── */
        .flight-search-redesign .daterangepicker .calendar-table,
        .daterangepicker.flight-search-redesign .calendar-table {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
        }

        .flight-search-redesign .daterangepicker .calendar-table table,
        .daterangepicker.flight-search-redesign .calendar-table table {
            border-spacing: 1px !important;
        }

        /* ── Day states ── */
        .flight-search-redesign .daterangepicker td.available,
        .daterangepicker.flight-search-redesign td.available {
            border-radius: 6px !important;
            transition: background .1s, color .1s !important;
        }

        .flight-search-redesign .daterangepicker td.available:hover,
        .daterangepicker.flight-search-redesign td.available:hover {
            background: rgba(205,27,79,.1) !important;
            color: #cd1b4f !important;
        }

        .flight-search-redesign .daterangepicker td.off,
        .daterangepicker.flight-search-redesign td.off {
            color: #c9d0dc !important;
        }

        .flight-search-redesign .daterangepicker td.in-range,
        .daterangepicker.flight-search-redesign td.in-range {
            background: #fdeef3 !important;
            color: #cd1b4f !important;
            border-radius: 0 !important;
        }

        .flight-search-redesign .daterangepicker td.in-range:not(.active),
        .daterangepicker.flight-search-redesign td.in-range:not(.active) {
            background: #fdeef3 !important;
            color: #cd1b4f !important;
        }

        .flight-search-redesign .daterangepicker td.start-date,
        .flight-search-redesign .daterangepicker td.active,
        .flight-search-redesign .daterangepicker td.active:hover,
        .daterangepicker.flight-search-redesign td.start-date,
        .daterangepicker.flight-search-redesign td.active,
        .daterangepicker.flight-search-redesign td.active:hover {
            background: #cd1b4f !important;
            border-color: transparent !important;
            color: #fff !important;
            border-radius: 6px !important;
        }

        .flight-search-redesign .daterangepicker td.start-date,
        .daterangepicker.flight-search-redesign td.start-date {
            border-radius: 6px 0 0 6px !important;
        }

        .flight-search-redesign .daterangepicker td.end-date,
        .daterangepicker.flight-search-redesign td.end-date {
            border-radius: 0 6px 6px 0 !important;
        }

        .flight-search-redesign .daterangepicker td.start-date.end-date,
        .daterangepicker.flight-search-redesign td.start-date.end-date {
            border-radius: 6px !important;
        }

        /* Today when not range endpoints: pink bottom border only (no fill).
           Placed after .in-range / .active so it wins. Hotels + flights use same picker skin. */
        .flight-search-redesign .daterangepicker td.today:not(.start-date):not(.end-date),
        .daterangepicker.flight-search-redesign td.today:not(.start-date):not(.end-date) {
            background: transparent !important;
            color: #111827 !important;
            font-weight: 700 !important;
            border-bottom: 3px solid var(--fs-brand) !important;
            border-radius: 6px !important;
        }

        .flight-search-redesign .daterangepicker td.today:not(.start-date):not(.end-date):hover,
        .daterangepicker.flight-search-redesign td.today:not(.start-date):not(.end-date):hover {
            background: rgba(205,27,79,.08) !important;
            color: #111827 !important;
            border-bottom-color: var(--fs-brand) !important;
        }

        /* ── Apply / Cancel buttons ── */
        .flight-search-redesign .daterangepicker .applyBtn,
        .daterangepicker.flight-search-redesign .applyBtn {
            background: linear-gradient(180deg, #cd1b4f 0%, #a8173f 100%) !important;
            border-color: #cd1b4f !important;
            border-radius: 8px !important;
            font-weight: 700 !important;
            font-size: .82rem !important;
            padding: .4rem 1rem !important;
            color: #fff !important;
        }

        .flight-search-redesign .daterangepicker .cancelBtn,
        .daterangepicker.flight-search-redesign .cancelBtn {
            border-radius: 8px !important;
            border-color: #dde3ef !important;
            color: #4a5568 !important;
            font-weight: 600 !important;
            font-size: .82rem !important;
            background: #fff !important;
        }

        .flight-search-redesign .daterangepicker .cancelBtn:hover,
        .daterangepicker.flight-search-redesign .cancelBtn:hover {
            background: #f3f5fb !important;
        }

        /* Airline preference — neutral strip (matches cabin / field chrome) */
        .fs-pro-airline-pref {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            flex-wrap: wrap;
            padding: 0.7rem 0.95rem;
            background: var(--fs-surface);
            border: 1px solid var(--fs-line);
            border-radius: 10px;
        }

        .fs-pro-airline-pref__label {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.76rem;
            font-weight: 400;
            letter-spacing: 0;
            text-transform: none;
            color: #6b7280;
            padding-right: 0;
            border-right: none;
        }

        .fs-pro-airline-pref__label i {
            display: none;
        }

        .fs-pro-airline-pref__toggles {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            flex: 1;
        }

        .fs-air-chip {
            border: 1px solid transparent;
            background: rgba(255, 255, 255, 0.75);
            color: #475569;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 500 !important;
            padding: 0.35rem 0.78rem !important;
            border-radius: 999px !important;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            transition: border-color 0.15s ease, background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
        }

        .fs-air-chip__indicator {
            width: 13px;
            height: 13px;
            border-radius: 4px;
            border: 1.5px solid #cbd5e1;
            background: #fff;
            position: relative;
            flex-shrink: 0;
            transition: inherit;
        }

        .fs-air-chip:hover {
            background: rgba(255, 255, 255, 0.95);
            color: #1f2937;
        }

        .fs-air-chip.is-active {
            border-color: rgba(205, 27, 79, 0.42);
            background: #fff;
            color: var(--fs-accent-dark);
            box-shadow: var(--fs-shadow-sm);
        }

        .fs-air-chip.is-active .fs-air-chip__indicator {
            border-color: var(--fs-brand-2);
            background: var(--fs-brand);
        }

        .fs-air-chip.is-active .fs-air-chip__indicator::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 50%;
            width: 3px;
            height: 6px;
            margin-top: -0.6px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            box-sizing: border-box;
            transform: translate(-52%, -55%) rotate(45deg);
        }

        /* "All Airlines" — pink when on, neutral when off */
        .fs-air-chip--all {
            border: 1px solid var(--fs-line);
            background: #fff;
            color: #475569;
            padding-right: 0.32rem !important;
        }

        .fs-air-chip--all.is-active {
            border-color: rgba(205, 27, 79, 0.42);
            background: var(--fs-green-soft);
            color: var(--fs-accent-dark);
            box-shadow: var(--fs-shadow-sm);
        }

        .fs-air-chip__close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--fs-brand-2);
            color: #fff;
            font-size: 0.85rem;
        }

        /* Actions footer */
        .fs-pro-actions-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            padding-top: 0.3rem;
        }

        .fs-pro-actions-footer .fs-search-filters {
            margin-bottom: 0 !important;
            gap: 1rem !important;
            margin-right: auto;
        }

        .fs-pro-search-btn {
            border-radius: 10px !important;
            padding: 0.78rem 1.65rem !important;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            letter-spacing: 0 !important;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #fff !important;
            background: linear-gradient(
                180deg,
                #f472a6 0%,
                var(--fs-brand) 48%,
                var(--fs-brand-2) 100%
            ) !important;
            box-shadow:
                0 6px 18px rgba(205, 27, 79, 0.38),
                inset 0 1px 0 rgba(255, 255, 255, 0.45) !important;
            transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
            position: relative;
        }

        .fs-pro-search-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow:
                0 10px 24px rgba(180, 22, 66, 0.45),
                inset 0 1px 0 rgba(255, 255, 255, 0.55) !important;
        }

        .fs-pro-search-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: var(--fs-shadow-sm) !important;
        }

        .fs-pro-search-btn__arrow {
            display: none;
        }

        /* Trust strip  -  hidden in vivid reference layout */
        .fs-pro-trust-strip {
            display: none;
        }

        /* ===== Aside ===== */
        .fs-pro-aside {
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
        }

        .fs-pro-aside-card {
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 0;
            box-shadow: none;
        }

        .fs-pro-aside-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.55rem;
        }

        .fs-pro-aside-card__label {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0;
            text-transform: none;
            color: #1f2937;
        }

        .fs-pro-aside-card__action {
            font-size: 0.72rem;
            font-weight: 500;
            color: var(--fs-slate-2);
            text-decoration: none;
        }

        .fs-pro-aside-card__action:hover {
            color: var(--fs-brand);
        }

        /* First card is the action tiles  -  hide the "Workspace" header */
        .fs-pro-aside > .fs-pro-aside-card:first-child .fs-pro-aside-card__head {
            display: none;
        }

        .fs-pro-tile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
        }

        .fs-pro-tile {
            border-radius: 14px;
            padding: 0.85rem 0.85rem 0.9rem !important;
            text-decoration: none !important;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0.55rem;
            color: var(--fs-ink) !important;
            line-height: 1.25;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.6);
            position: relative;
            min-height: 102px;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .fs-pro-tile:hover {
            transform: translateY(-2px);
            box-shadow: var(--fs-shadow-md);
        }

        .fs-pro-tile__icon {
            align-self: flex-start;
            width: 38px;
            height: 38px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem !important;
            background: rgba(255, 255, 255, 0.55);
            border: none;
        }

        .fs-pro-tile__meta {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }

        .fs-pro-tile__title {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .fs-pro-tile__hint {
            display: none;
        }

        /* Tile color variants  -  soft pastel cards (reference) */
        .fs-pro-tile--offline {
            background: linear-gradient(160deg, #fff7d6 0%, #fff1b5 100%);
            color: #7a5a0c !important;
        }
        .fs-pro-tile--offline .fs-pro-tile__icon {
            background: rgba(255, 255, 255, 0.65);
            color: #b06f0d;
        }

        .fs-pro-tile--import {
            background: linear-gradient(160deg, #ffe3d3 0%, #ffd4be 100%);
            color: #8c3e16 !important;
        }
        .fs-pro-tile--import .fs-pro-tile__icon {
            background: rgba(255, 255, 255, 0.65);
            color: #c2410c;
        }

        .fs-pro-tile--hold {
            background: linear-gradient(160deg, #fce7ef 0%, #fad4e5 100%);
            color: #831843 !important;
        }
        .fs-pro-tile--hold .fs-pro-tile__icon {
            background: rgba(255, 255, 255, 0.65);
            color: var(--fs-brand);
        }

        .fs-pro-tile--calendar {
            background: linear-gradient(160deg, #fde6f3 0%, #f8d2e7 100%);
            color: #832661 !important;
        }
        .fs-pro-tile--calendar .fs-pro-tile__icon {
            background: rgba(255, 255, 255, 0.65);
            color: #be185d;
        }

        /* Tailwind-scale helper (scoped): Recent panel Clear */
        .fs-pro-enterprise .text-sm {
            font-size: 0.875rem !important;
            line-height: 1.25rem !important;
        }

        .fs-pro-recent-panel .fs-pro-clear-recent {
            font-weight: 700 !important;
            color: var(--fs-brand) !important;
        }

        .fs-pro-recent-panel .fs-pro-clear-recent:hover {
            color: var(--fs-brand-2) !important;
        }

        /* Recent panel  -  single pill row */
        .fs-pro-recent-panel .fs-pro-aside-card__head {
            margin-bottom: 0.45rem;
        }

        .fs-pro-recent-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.55rem 0.9rem;
            border-radius: 999px;
            text-decoration: none !important;
            color: var(--fs-ink);
            background: #fff;
            border: 1px solid var(--fs-line);
            box-shadow: var(--fs-shadow-sm);
            cursor: pointer;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            margin-bottom: 0.45rem;
        }

        .fs-pro-recent-row:last-child {
            margin-bottom: 0;
        }

        .fs-pro-recent-row:hover {
            border-color: #c8d1e0;
            box-shadow: var(--fs-shadow-md);
        }

        .fs-pro-recent-row__route {
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
            font-weight: 500;
            font-size: 0.86rem;
            color: var(--fs-ink);
        }

        .fs-pro-recent-row__city {
            font-size: 0.86rem;
            font-weight: 500;
            color: var(--fs-ink);
            background: transparent;
            border: none;
            padding: 0;
        }

        .fs-pro-recent-row__arrow {
            font-size: 0.95rem;
            color: var(--fs-lime-2);
            transform: translateY(0);
        }

        .fs-pro-recent-row__meta {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-left: auto;
            font-feature-settings: "tnum" 1, "lnum" 1;
        }

        .fs-pro-recent-row__dates {
            font-size: 0.74rem;
            font-weight: 400;
            color: var(--fs-slate-2);
            white-space: nowrap;
        }

        .fs-pro-recent-row__pax {
            display: none;
        }

        .fs-pro-aside-card__action--btn {
            background: none;
            border: none;
            padding: 0;
            font: inherit;
            cursor: pointer;
        }

        /* ===== Promotions strip ===== */
        .fs-pro-promos {
            grid-column: 1 / -1;
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-top: 0.25rem;
        }

        .fs-promo {
            position: relative;
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            text-decoration: none !important;
            border-radius: 14px;
            padding: 1.1rem 1.15rem !important;
            cursor: pointer;
            min-height: 96px;
            color: inherit;
            box-shadow: var(--fs-shadow-md);
            border: 1px solid var(--fs-line);
            overflow: hidden;
            isolation: isolate;
        }

        .fs-promo__body {
            display: flex;
            flex-direction: column;
            gap: 0.18rem;
            z-index: 1;
        }

        .fs-promo__kicker {
            font-size: 0.62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            opacity: 0.78;
        }

        .fs-promo__title {
            display: block !important;
            font-size: 1.02rem;
            font-weight: 700;
            letter-spacing: -0.015em;
            line-height: 1.25;
        }

        .fs-promo__cta {
            display: inline-flex;
            align-items: center;
            gap: 0.32rem;
            margin-top: 0.5rem;
            font-size: 0.74rem;
            font-weight: 600;
            opacity: 0.88;
            letter-spacing: 0;
            text-transform: none;
        }

        .fs-promo__art {
            font-size: 4rem;
            line-height: 1;
            opacity: 0.14;
            position: absolute;
            right: -0.5rem;
            bottom: -0.6rem;
            transform: rotate(-12deg);
            z-index: 0;
        }

        .fs-promo--gold {
            background: linear-gradient(135deg, #f59e0b 0%, #ea7c0c 50%, #c2410c 100%);
            color: #fff7ed;
            border-color: rgba(234, 124, 12, 0.5);
        }

        .fs-promo--gold .fs-promo__kicker {
            color: #fde6a3;
            font-style: italic;
            text-transform: none;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0;
        }

        .fs-promo--gold .fs-promo__title {
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 0.95rem;
        }

        .fs-promo--gold .fs-promo__art {
            color: rgba(255, 255, 255, 0.65);
        }

        .fs-promo--ocean {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 60%, #4f46e5 100%);
            color: #f5f3ff;
            border-color: rgba(139, 92, 246, 0.45);
        }

        .fs-promo--ocean .fs-promo__kicker {
            color: #ddd6fe;
        }

        .fs-promo--ocean .fs-promo__title {
            font-size: 0.95rem;
        }

        .fs-promo--ocean .fs-promo__art {
            color: rgba(255, 255, 255, 0.6);
        }

        .fs-promo--night {
            background: linear-gradient(135deg, #0e2247 0%, #142e63 60%, #1e3a8a 100%);
            color: #e0f2fe;
            border-color: rgba(30, 58, 138, 0.5);
        }

        .fs-promo--night .fs-promo__kicker {
            display: none;
        }

        .fs-promo--night .fs-promo__title {
            color: #fff;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .fs-promo--night .fs-promo__cta {
            color: #bae6fd;
        }

        .fs-promo--night .fs-promo__art {
            color: rgba(255, 255, 255, 0.18);
            font-size: 5rem;
        }

        /* ===== Trip type pills (reference style) ===== */
        .fs-pro-enterprise .fs-trip-types {
            display: inline-flex;
            flex-wrap: wrap;
        }

        .fs-pro-enterprise .fs-trip-types__item {
            --fs-trip-pad-top: 0.45rem;
            border: none;
            background: transparent;
            color: #6b7280;
            border-radius: 999px;
            padding: var(--fs-trip-pad-top) 0.72rem 0.42rem;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 500;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.28rem;
            min-width: 0;
            cursor: pointer;
            transition: color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
            position: relative;
            letter-spacing: -0.005em;
            text-align: center;
            line-height: 1.15;
        }

        .fs-pro-enterprise .fs-trip-types__item::before {
            content: "";
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #fff;
            border: 1.5px solid #cbd5e1;
            flex-shrink: 0;
            transition: inherit;
        }

        .fs-pro-enterprise .fs-trip-types__item .fs-trip-types__icon {
            display: none;
        }

        .fs-pro-enterprise .fs-trip-types__item:hover {
            color: #374151;
            background: #f3f4f6;
        }

        .fs-pro-enterprise .fs-trip-types__item:hover::before {
            border-color: #94a3b8;
        }

        .fs-pro-enterprise .fs-trip-types__item.active {
            color: var(--fs-accent-dark);
            background: var(--fs-lime-soft);
            box-shadow: inset 0 0 0 1px rgba(205, 27, 79, 0.28);
        }

        .fs-pro-enterprise .fs-trip-types__item.active::before {
            background: var(--fs-green);
            border-color: var(--fs-green);
            position: relative;
        }

        .fs-pro-enterprise .fs-trip-types__item.active::after {
            content: "\2713";
            position: absolute;
            left: 50%;
            top: calc(var(--fs-trip-pad-top) + 7px);
            width: 14px;
            height: 14px;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            pointer-events: none;
        }

        /* Legacy dot (no longer used but kept hidden if rendered) */
        .fs-trip-types__dot { display: none; }

        /* ===== Quick filter chips (Direct / Nearby / Student)  -  checkbox style ===== */
        .fs-search-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 0 !important;
        }

        .fs-pro-enterprise .fs-filter-chip {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
            min-height: auto;
            padding: 0;
            border: none;
            border-radius: 0;
            background: transparent;
            color: #374151;
            font-size: 0.86rem;
            font-weight: 500;
            cursor: pointer;
            box-shadow: none;
            transition: color 0.15s ease;
        }

        .fs-pro-enterprise .fs-filter-chip input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .fs-pro-enterprise .fs-filter-chip:hover {
            color: var(--fs-ink);
            background: transparent;
            box-shadow: none;
        }

        .fs-pro-enterprise .fs-filter-chip.active {
            color: var(--fs-ink);
            background: transparent;
            box-shadow: none;
        }

        .fs-pro-enterprise .fs-filter-chip__box {
            width: 15px;
            height: 15px;
            border: 1.5px solid #cbd5e1;
            border-radius: 3px;
            background: #fff;
            position: relative;
            flex-shrink: 0;
            transition: inherit;
        }

        .fs-pro-enterprise .fs-filter-chip.active .fs-filter-chip__box {
            border-color: var(--fs-lime-2);
            background: var(--fs-lime);
            box-shadow: none;
        }

        .fs-pro-enterprise .fs-filter-chip.active .fs-filter-chip__box::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 50%;
            width: 3px;
            height: 7px;
            margin-top: -0.75px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            box-sizing: border-box;
            transform: translate(-52%, -55%) rotate(45deg);
        }

        .hs-field--disabled {
            background: #f8fafc;
            pointer-events: none;
        }

        .hs-field__note {
            font-size: 0.82rem;
            line-height: 1.35;
            color: var(--fs-slate-2);
            font-weight: 600;
            max-width: 150px;
        }

        /* ===== Multi-city ===== */
        .fs-multicity {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            margin-bottom: 0.95rem;
        }

        .fs-multicity__row {
            border: 1px solid var(--fs-line);
            border-radius: 12px;
            background: #fff;
            box-shadow: var(--fs-shadow-sm);
            overflow: visible;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .fs-multicity__row:hover {
            border-color: #c8d1e0;
            box-shadow: var(--fs-shadow-md);
        }

        .fs-multicity__grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.2fr) minmax(160px, 0.9fr) 48px;
            align-items: stretch;
        }

        .fs-multicity__field {
            border-right: 1px solid var(--fs-line);
        }

        .fs-multicity__field .hs-field__label,
        .fs-multicity__date .hs-field__label {
            font-weight: 700 !important;
            font-size: 0.66rem !important;
            letter-spacing: 0.16em !important;
            color: var(--fs-muted) !important;
            text-transform: uppercase;
        }

        .fs-multicity__field .hs-field__inner {
            padding: 0.85rem 1rem !important;
        }

        .fs-multicity__date {
            border-right: 1px solid var(--fs-line);
            min-width: 0;
        }

        .fs-multicity__date .hs-field__inner {
            padding: 0.85rem 1rem;
            cursor: pointer;
        }

        .fs-multicity__date-input {
            border: none;
            background: transparent;
            padding: 0;
            width: 100%;
            height: 0;
            min-height: 0;
            opacity: 0;
            position: absolute;
            pointer-events: none;
            outline: none;
        }

        .fs-multicity__remove {
            border: none;
            background: transparent;
            color: var(--fs-slate-2);
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: color 0.15s ease, background 0.15s ease;
        }

        .fs-multicity__remove:hover {
            color: var(--fs-rose);
            background: var(--fs-rose-soft);
        }

        .fs-multicity__actions {
            display: flex;
            justify-content: flex-end;
        }

        .fs-add-city-btn {
            border: 1px dashed var(--fs-line);
            background: #fff;
            color: var(--fs-brand-2);
            border-radius: 10px;
            padding: 0.55rem 0.95rem;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
        }

        .fs-add-city-btn:hover:not(:disabled) {
            border-color: rgba(205, 27, 79, 0.45);
            background: var(--fs-lime-soft);
            color: var(--fs-brand);
        }

        .fs-add-city-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        @media (max-width: 991px) {
            .fs-multicity__grid {
                grid-template-columns: 1fr;
            }

            .fs-multicity__field,
            .fs-multicity__date {
                border-right: none;
                border-bottom: 1px solid var(--fs-line);
            }

            .fs-multicity__remove {
                min-height: 46px;
            }
        }

        /* ===== Dropdown polish ===== */
        .fs-pro-enterprise .options-dropdown {
            border-radius: 12px;
            border: 1px solid var(--fs-line);
            box-shadow: var(--fs-shadow-lg);
        }

        .fs-pro-enterprise .options-dropdown__header {
            background: var(--fs-surface-2);
            border-bottom: 1px solid var(--fs-line);
            padding: 0.5rem 0.85rem;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--fs-muted);
        }

        .fs-pro-enterprise .options-dropdown-list__item {
            padding: 0.65rem 0.85rem;
            cursor: pointer;
            transition: background 0.12s ease;
            border-radius: 0;
        }

        .fs-pro-enterprise .options-dropdown-list__item:hover {
            background: var(--fs-canvas);
        }

        .fs-pro-enterprise .options-dropdown-list__item .name {
            font-weight: 600;
            font-size: 0.82rem;
            color: var(--fs-ink);
        }

        .fs-pro-enterprise .options-dropdown-list__item .sub-text {
            font-size: 0.72rem;
            color: var(--fs-slate-2);
            font-weight: 500;
        }

        .fs-pro-enterprise .quantity-counter__btn {
            border: 1px solid var(--fs-line);
            background: #fff;
            color: var(--fs-ink);
            transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease;
        }

        .fs-pro-enterprise .quantity-counter__btn:hover {
            border-color: rgba(205, 27, 79, 0.45);
            color: var(--fs-brand);
        }

        .fs-pro-enterprise .quantity-counter__btn--quantity {
            color: var(--fs-ink);
            font-weight: 700;
            background: var(--fs-canvas);
        }

        /* (td.active + td.in-range rules moved to the unified daterangepicker block above) */

        /*
         * Results listing (/flights/search): compact reference-style toolbar
         *  -  one slim bar (trip / pax / cabins / airlines · route / dates · filters / search).
         */
        .fs-mount--flight-listing .fs-pro-layout {
            grid-template-columns: minmax(0, 1fr);
            gap: 0;
            align-items: stretch;
        }

        .fs-mount--flight-listing .fs-pro-aside,
        .fs-mount--flight-listing .fs-pro-promos {
            display: none !important;
        }

        .fs-mount--flight-listing .fs-pro-layout__main {
            z-index: 40;
        }

        .fs-mount--flight-listing .fs-pro-card__head {
            display: none !important;
        }

        .fs-mount--flight-listing .fs-pro-card:not(:has(.fs-multicity)) .fs-pro-footer {
            display: contents;
        }

        .fs-mount--flight-listing .fs-pro-card {
            padding: 0.42rem 0.62rem !important;
            border-radius: 12px !important;
            box-shadow:
                var(--fs-shadow-sm),
                0 1px 0 rgba(255, 255, 255, 0.9) inset !important;
            display: flex;
            flex-flow: row wrap;
            align-items: center;
            justify-content: flex-start;
            gap: 0.35rem 0.55rem !important;
            background:
                repeating-linear-gradient(
                    -10deg,
                    #f8fafc 0,
                    #f8fafc 11px,
                    #f4f6f9 11px,
                    #f4f6f9 22px
                );
        }

        .fs-mount--flight-listing .fs-pro-card:not(:has(.fs-multicity)) > .fs-pro-controls-row {
            order: 1;
        }

        .fs-mount--flight-listing .fs-pro-card:not(:has(.fs-multicity)) > .fs-pro-pax-cabin-row {
            order: 2;
        }

        .fs-mount--flight-listing .fs-pro-card:not(:has(.fs-multicity)) > .fs-pro-airline-pref {
            order: 3;
        }

        .fs-mount--flight-listing .fs-pro-card:not(:has(.fs-multicity)) > .fs-pro-route-sheet {
            order: 4;
        }

        .fs-mount--flight-listing .fs-pro-card:not(:has(.fs-multicity)) > .fs-pro-actions-footer {
            order: 5;
        }

        .fs-mount--flight-listing .fs-pro-controls-row {
            margin-bottom: 0 !important;
            gap: 0.3rem !important;
            align-items: center;
            flex-shrink: 0;
        }

        .fs-mount--flight-listing .fs-pro-trip-types {
            gap: 0.15rem !important;
        }

        .fs-mount--flight-listing .fs-trip-types__item {
            --fs-trip-pad-top: 0.38rem;
            padding: 0.38rem 0.62rem !important;
            font-size: 0.72rem !important;
            gap: 0.22rem !important;
        }

        .fs-mount--flight-listing .fs-pro-pax-cabin-row {
            flex: 0 2 auto;
            max-width: 440px;
            min-width: 0;
            border-radius: 8px !important;
        }

        .fs-mount--flight-listing .fs-pro-travellers,
        .fs-mount--flight-listing .fs-pro-select-group {
            padding: 0.42rem 0.58rem !important;
        }

        .fs-mount--flight-listing .fs-pro-select-group__label {
            font-size: 0.6rem !important;
            letter-spacing: 0.06em !important;
        }

        .fs-mount--flight-listing .fs-pro-label {
            font-size: 0.66rem !important;
        }

        .fs-mount--flight-listing .fs-pro-pax-line {
            font-size: 0.86rem !important;
        }

        .fs-mount--flight-listing .fs-pro-cabin-trigger {
            font-size: 0.78rem !important;
            padding-block: 0.12rem;
        }

        /* Listing toolbar row only — flex-grow breaks multi-city footer (airline row blows up vertically) */
        .fs-mount--flight-listing .fs-pro-card:not(:has(.fs-multicity)) .fs-pro-airline-pref {
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-start;
            flex: 1 1 260px;
            width: auto;
            min-width: min(260px, 100%);
            max-width: 100%;
            margin-left: auto;
            padding: 0.42rem 0.55rem !important;
            border-radius: 8px !important;
            gap: 0.42rem 0.55rem !important;
        }

        .fs-mount--flight-listing .fs-pro-card:not(:has(.fs-multicity)) .fs-pro-airline-pref__toggles {
            flex: 1 1 180px;
            min-width: 0;
            flex-wrap: wrap;
            justify-content: flex-start;
            overflow-x: visible;
            gap: 0.35rem !important;
        }

        .fs-mount--flight-listing .fs-pro-airline-pref__label {
            flex: 0 0 auto;
            flex-shrink: 0;
            margin: 0;
            max-width: 100%;
        }

        .fs-mount--flight-listing .fs-air-chip {
            font-size: 0.7rem !important;
            padding: 0.3rem 0.62rem !important;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .fs-mount--flight-listing .fs-air-chip__indicator {
            width: 11px !important;
            height: 11px !important;
        }

        .fs-mount--flight-listing .fs-pro-route-sheet {
            flex-wrap: nowrap !important;
            gap: 0.35rem !important;
            align-items: stretch;
            margin-bottom: 0 !important;
            flex: 1 1 440px !important;
            min-width: 0 !important;
        }

        .fs-mount--flight-listing .fs-pro-route-pair,
        .fs-mount--flight-listing .fs-pro-date-pair {
            gap: 0.38rem !important;
        }

        .fs-mount--flight-listing .fs-pro-route-pair {
            flex: 2 1 160px !important;
        }

        .fs-mount--flight-listing .fs-pro-date-pair {
            flex: 1 1 130px !important;
            max-width: 280px !important;
        }

        .fs-mount--flight-listing .fs-pro-route-field__shell {
            min-height: 56px !important;
            border-radius: 8px !important;
            background: #fff !important;
        }

        .fs-mount--flight-listing .fs-pro-route-field .hs-field__inner {
            padding: 0.42rem 0.55rem !important;
        }

        .fs-mount--flight-listing .fs-pro-route-field.fs-pro-route-field--from .hs-field__inner {
            padding: 0.42rem calc(0.55rem + 22px) 0.42rem 0.55rem !important;
        }

        .fs-mount--flight-listing .fs-pro-route-field.fs-pro-route-field--to .hs-field__inner {
            padding: 0.42rem 0.55rem 0.42rem calc(0.55rem + 26px) !important;
        }

        .fs-mount--flight-listing .fs-pro-route-chosen__city {
            font-size: 1.18rem !important;
        }

        .fs-mount--flight-listing .fs-pro-route-chosen__airport {
            font-size: 0.64rem !important;
            margin-top: 0 !important;
            line-height: 1.2 !important;
        }

        .fs-mount--flight-listing .fs-pro-date-cell .hs-field__inner {
            min-height: 56px !important;
            padding: 0.42rem 0.55rem !important;
            border-radius: 8px !important;
            background: #fff !important;
        }

        .fs-mount--flight-listing .fs-pro-enterprise .hs-date-display__day {
            font-size: 1.15rem !important;
        }

        .fs-mount--flight-listing .fs-pro-enterprise .hs-date-display__month {
            font-size: 0.75rem !important;
        }

        .fs-mount--flight-listing .fs-pro-enterprise .hs-date-display__weekday {
            font-size: 0.64rem !important;
        }

        .fs-mount--flight-listing .fs-pro-swap-btn {
            width: 28px !important;
            height: 28px !important;
            font-size: 1rem !important;
            box-shadow:
                0 0 0 4px #fff,
                0 3px 8px rgba(15, 23, 42, 0.1) !important;
        }

        .fs-mount--flight-listing .fs-pro-actions-footer {
            flex: 0 0 auto !important;
            padding-top: 0 !important;
            gap: 0.48rem !important;
            justify-content: flex-end;
            align-self: stretch;
            display: flex !important;
            flex-wrap: nowrap !important;
        }

        .fs-mount--flight-listing .fs-pro-actions-footer .fs-search-filters {
            gap: 0.35rem !important;
            flex-wrap: nowrap !important;
        }

        .fs-mount--flight-listing .fs-filter-chip__label {
            font-size: 0.64rem !important;
        }

        .fs-mount--flight-listing .fs-pro-search-btn {
            padding: 0.5rem 1.05rem !important;
            font-size: 0.82rem !important;
            border-radius: 8px !important;
            white-space: nowrap;
            box-shadow: 0 3px 10px rgba(205, 27, 79, 0.28),
                inset 0 1px 0 rgba(255, 255, 255, 0.42) !important;
        }

        /* Multi-city on listing: full-width stacked flow (compact toolbar rules off) */
        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) {
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem !important;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-pro-controls-row {
            width: 100%;
            justify-content: space-between;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-multicity {
            margin-bottom: 0 !important;
            gap: 0.45rem;
            width: 100%;
            min-width: 0;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-multicity__row {
            border-radius: 10px;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-multicity__grid {
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 1.15fr) minmax(132px, 0.92fr) 44px;
            align-items: stretch;
            min-height: 0;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-multicity__field .hs-field__inner,
        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-multicity__date .hs-field__inner {
            padding: 0.52rem 0.72rem !important;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-multicity__actions {
            justify-content: flex-end;
            width: 100%;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-pro-footer {
            display: flex !important;
            flex-direction: column;
            gap: 0.5rem !important;
            margin-top: 0 !important;
            padding-top: 0 !important;
            border-top: none !important;
            width: 100%;
            flex: none;
            min-height: 0;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) > * {
            order: unset !important;
            max-width: 100%;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-pro-pax-cabin-row {
            flex: none !important;
            max-width: none !important;
            width: 100% !important;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-pro-airline-pref {
            margin-left: 0 !important;
            justify-content: flex-start !important;
            align-items: center !important;
            width: 100% !important;
            flex: none !important;
            align-self: flex-start !important;
            min-height: 0 !important;
            flex-wrap: wrap !important;
            padding: 0.4rem 0.55rem !important;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-pro-airline-pref__label {
            padding-top: 0;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-pro-airline-pref__toggles {
            flex-wrap: wrap !important;
            justify-content: flex-start !important;
            overflow-x: visible !important;
            flex: 0 1 auto !important;
            min-width: 0;
        }

        .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-pro-actions-footer {
            width: 100% !important;
            align-self: stretch !important;
            flex-wrap: wrap !important;
            justify-content: space-between !important;
            gap: 0.5rem !important;
        }

        /* Multicity breakpoints: stacked grid already in base  -  keep readable on listing */
        @media (max-width: 991px) {
            .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-multicity__grid {
                grid-template-columns: 1fr !important;
            }

            .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-multicity__field {
                border-right: none !important;
                border-bottom: 1px solid var(--fs-line);
            }

            .fs-mount--flight-listing .fs-pro-card:has(.fs-multicity) .fs-multicity__date {
                border-right: none !important;
                border-bottom: 1px solid var(--fs-line);
            }
        }

        /* Narrow / tablet: readable wrap */
        @media (max-width: 991px) {
            .fs-mount--flight-listing .fs-pro-card:not(:has(.fs-multicity)) .fs-pro-footer {
                display: flex !important;
                flex-direction: column !important;
                gap: 0.52rem !important;
                margin-top: 0 !important;
                width: 100%;
                overflow: visible;
            }

            .fs-mount--flight-listing .fs-pro-card:not(:has(.fs-multicity)) {
                flex-direction: column;
                align-items: stretch;
                gap: 0.48rem !important;
            }

            .fs-mount--flight-listing .fs-pro-card:not(:has(.fs-multicity)) > * {
                order: unset !important;
                width: 100%;
            }

            .fs-mount--flight-listing .fs-pro-airline-pref {
                margin-left: 0 !important;
                justify-content: flex-start !important;
                flex-wrap: wrap !important;
            }

            .fs-mount--flight-listing .fs-pro-airline-pref__toggles {
                justify-content: flex-start;
                flex-wrap: wrap;
                overflow-x: visible;
                white-space: normal;
            }

            .fs-mount--flight-listing .fs-pro-route-sheet {
                flex-wrap: wrap !important;
            }

            .fs-mount--flight-listing .fs-pro-actions-footer {
                flex-wrap: wrap !important;
                justify-content: flex-start !important;
            }

            .fs-mount--flight-listing .fs-pro-actions-footer .fs-search-filters {
                margin-right: 0 !important;
                flex-wrap: wrap !important;
            }
        }

        /* Stack route pair + dates vertically on phones (same as enterprise base) */
        @media (max-width: 640px) {
            .fs-mount--flight-listing .fs-pro-route-pair,
            .fs-mount--flight-listing .fs-pro-date-pair {
                flex-basis: 100% !important;
                max-width: none !important;
            }

            /* Stacked columns: rotated swap overlaps lower FROM / upper TO vertically */
            .fs-pro-route-field.fs-pro-route-field--from .hs-field__inner {
                padding-right: 0.95rem !important;
                padding-bottom: calc(0.7rem + 26px) !important;
            }

            .fs-pro-route-field.fs-pro-route-field--to .hs-field__inner {
                padding-left: 0.95rem !important;
                padding-top: calc(0.7rem + 26px) !important;
            }

            .fs-mount--flight-listing .fs-pro-route-field.fs-pro-route-field--from .hs-field__inner {
                padding-bottom: calc(0.42rem + 24px) !important;
                padding-left: 0.55rem !important;
                padding-right: 0.55rem !important;
                padding-top: 0.42rem !important;
            }

            .fs-mount--flight-listing .fs-pro-route-field.fs-pro-route-field--to .hs-field__inner {
                padding-top: calc(0.42rem + 24px) !important;
                padding-left: 0.55rem !important;
                padding-right: 0.55rem !important;
                padding-bottom: 0.42rem !important;
            }
        }

        /*
         * Hotel listing / compact toolbar (/hotels/search, hotel details)
         * Mirrors flight listing: one flex row, workspace aside hidden, trust strip hidden.
         */
        .hs-mount--hotel-listing .fs-pro-layout {
            grid-template-columns: minmax(0, 1fr);
            gap: 0;
            align-items: stretch;
        }

        .hs-mount--hotel-listing .fs-pro-aside--hotel {
            display: none !important;
        }

        .hs-mount--hotel-listing .fs-pro-layout__main {
            z-index: 40;
        }

        .hs-mount--hotel-listing .fs-pro-card__head {
            display: none !important;
        }

        .hs-mount--hotel-listing .fs-pro-trust-strip {
            display: none !important;
        }

        .hs-mount--hotel-listing .fs-pro-footer {
            display: contents !important;
        }

        .hs-mount--hotel-listing .fs-pro-footer.fs-pro-footer {
            margin-top: 0 !important;
            padding-top: 0 !important;
            border-top: none !important;
        }

        .hs-mount--hotel-listing .fs-pro-card {
            padding: 0.42rem 0.62rem !important;
            border-radius: 12px !important;
            box-shadow:
                var(--fs-shadow-sm),
                0 1px 0 rgba(255, 255, 255, 0.9) inset !important;
            display: flex;
            flex-flow: row wrap;
            align-items: stretch;
            justify-content: flex-start;
            gap: 0.35rem 0.55rem !important;
            background:
                repeating-linear-gradient(
                    -10deg,
                    #f8fafc 0,
                    #f8fafc 11px,
                    #f4f6f9 11px,
                    #f4f6f9 22px
                );
        }

        .hs-mount--hotel-listing .fs-pro-card > .fs-pro-route-sheet {
            display: contents;
        }

        /* One row: (1) destination (2) check-in/out (3) rooms & guests (4) search */
        .hs-mount--hotel-listing .fs-pro-route-sheet > .fs-pro-route-pair--hotel-destination-only {
            order: 1;
            flex: 1 1 min(200px, 26%);
            min-width: 0;
            margin-bottom: 0 !important;
        }

        .hs-mount--hotel-listing .fs-pro-route-sheet > .fs-pro-date-pair--hotel-range {
            order: 2;
            flex: 1 1 min(260px, 38%);
            max-width: 340px !important;
            min-width: 0 !important;
            margin-bottom: 0 !important;
        }

        /* Footer uses display:contents — pax/actions are flex items of .fs-pro-card, not direct children */
        .hs-mount--hotel-listing .fs-pro-pax-cabin-row--hotel-only {
            order: 3;
            flex: 0 1 220px;
            min-width: 0;
            max-width: 440px;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border-radius: 8px !important;
        }

        .hs-mount--hotel-listing .fs-pro-actions-footer--hotel-only {
            order: 4;
            margin-left: auto;
            flex: 0 0 auto;
            padding-top: 0 !important;
            gap: 0.48rem !important;
            justify-content: flex-end;
            align-self: center;
            display: flex !important;
            flex-wrap: nowrap !important;
        }

        .hs-mount--hotel-listing .fs-pro-actions-footer--hotel-only {
            padding-top: 0 !important;
            margin-top: 0 !important;
            border-top: none !important;
        }

        .hs-mount--hotel-listing .fs-pro-travellers {
            padding: 0.42rem 0.58rem !important;
        }

        .hs-mount--hotel-listing .fs-pro-label {
            font-size: 0.66rem !important;
        }

        .hs-mount--hotel-listing .fs-pro-pax-line {
            font-size: 0.86rem !important;
        }

        .hs-mount--hotel-listing .fs-pro-route-pair,
        .hs-mount--hotel-listing .fs-pro-date-pair {
            gap: 0.38rem !important;
            flex-wrap: nowrap !important;
        }

        .hs-mount--hotel-listing .fs-pro-route-pair--hotel-destination-only {
            min-width: 0 !important;
        }

        .hs-mount--hotel-listing .fs-pro-date-pair--hotel-range {
            min-width: 0 !important;
        }

        .hs-mount--hotel-listing .fs-pro-route-field__shell {
            min-height: 52px !important;
            border-radius: 8px !important;
            background: #fff !important;
        }

        .hs-mount--hotel-listing .hotel-search-redesign .fs-pro-route-pair--hotel-destination-only .fs-pro-route-field__shell {
            min-height: 52px !important;
        }

        .hs-mount--hotel-listing .fs-pro-route-field .hs-field__inner {
            padding: 0.38rem 0.52rem !important;
        }

        .hs-mount--hotel-listing .fs-pro-route-pair--hotel-destination-only .fs-pro-route-field--from .hs-field__inner {
            padding-right: 0.75rem !important;
        }

        .hs-mount--hotel-listing .fs-pro-date-cell .hs-field__inner {
            min-height: 52px !important;
            padding: 0.38rem 0.52rem !important;
            border-radius: 8px !important;
            background: #fff !important;
        }

        .hs-mount--hotel-listing .fs-pro-stay-range-display {
            margin-top: 0 !important;
            gap: 0.3rem !important;
            flex-wrap: nowrap !important;
        }

        .hs-mount--hotel-listing .fs-pro-stay-range-display__day {
            font-size: 1.05rem !important;
        }

        .hs-mount--hotel-listing .fs-pro-stay-range-display__mid {
            flex-shrink: 0;
        }

        .hs-mount--hotel-listing .fs-pro-search-btn {
            padding: 0.5rem 1.05rem !important;
            font-size: 0.82rem !important;
            border-radius: 8px !important;
            white-space: nowrap;
            box-shadow: 0 3px 10px rgba(205, 27, 79, 0.28),
                inset 0 1px 0 rgba(255, 255, 255, 0.42) !important;
        }

        @media (max-width: 991px) {
            .hs-mount--hotel-listing .fs-pro-card {
                flex-direction: column;
                align-items: stretch;
            }

            .hs-mount--hotel-listing .fs-pro-route-sheet > .fs-pro-route-pair--hotel-destination-only,
            .hs-mount--hotel-listing .fs-pro-route-sheet > .fs-pro-date-pair--hotel-range,
            .hs-mount--hotel-listing .fs-pro-pax-cabin-row--hotel-only,
            .hs-mount--hotel-listing .fs-pro-actions-footer--hotel-only {
                order: unset !important;
                width: 100%;
                max-width: none !important;
                margin-left: 0 !important;
            }

            .hs-mount--hotel-listing .fs-pro-actions-footer--hotel-only {
                justify-content: stretch;
            }

            .hs-mount--hotel-listing .fs-pro-route-sheet > .fs-pro-route-pair--hotel-destination-only,
            .hs-mount--hotel-listing .fs-pro-route-sheet > .fs-pro-date-pair--hotel-range {
                flex: 1 1 auto !important;
            }

            .hs-mount--hotel-listing .fs-pro-route-pair,
            .hs-mount--hotel-listing .fs-pro-date-pair {
                flex-wrap: wrap !important;
            }

            .hs-mount--hotel-listing .fs-pro-search-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 640px) {
            .hs-mount--hotel-listing .fs-pro-route-pair--hotel-destination-only,
            .hs-mount--hotel-listing .fs-pro-date-pair--hotel-range {
                flex-basis: 100% !important;
                max-width: none !important;
            }
        }
</style>
