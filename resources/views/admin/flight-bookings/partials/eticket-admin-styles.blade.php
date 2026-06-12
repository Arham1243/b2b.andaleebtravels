{{-- Admin E-Ticket Details layout — scoped to .bkpd-eticket-admin --}}
.bkpd-eticket-admin {
    --eta-gap: .65rem;
    --eta-radius: 10px;
    --eta-surface: #fafbfd;
    --eta-surface-2: #f4f6f9;
}
.bkpd-eticket-admin__section {
    padding: .75rem 1rem;
    border-top: 1px solid var(--c-line-inner);
}
.bkpd-eticket-admin__section:first-of-type { border-top: 0; }
.bkpd-eticket-admin__section-title {
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--c-muted);
    margin-bottom: .5rem;
}
.bkpd-eticket-admin__section-title::before {
    content: '';
    width: 3px;
    height: 12px;
    border-radius: 2px;
    background: var(--c-brand, #cd1b4f);
    flex-shrink: 0;
}

/* Highlight stats row */
.eta-stat-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: var(--eta-gap);
    margin-bottom: .75rem;
}
.eta-stat {
    padding: .55rem .7rem;
    background: var(--eta-surface);
    border: 1px solid var(--c-line-inner);
    border-radius: var(--eta-radius);
    min-width: 0;
}
.eta-stat--primary {
    background: linear-gradient(135deg, #fff5f8 0%, #fff 100%);
    border-color: #f5c2d4;
}
.eta-stat--earn {
    background: linear-gradient(135deg, #ecfdf5 0%, #fff 100%);
    border-color: #a7f3d0;
}
.eta-stat__label {
    display: block;
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--c-muted);
    margin-bottom: .2rem;
}
.eta-stat__val {
    display: block;
    font-size: .95rem;
    font-weight: 700;
    color: var(--c-ink, #1a2540);
    line-height: 1.2;
    word-break: break-word;
}
.eta-stat__val--discount { color: #059669; }

/* Multi-column key-value grid */
.eta-kv-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
    gap: var(--eta-gap);
}
.eta-kv {
    padding: .4rem .55rem;
    background: var(--eta-surface);
    border: 1px solid var(--c-line-inner);
    border-radius: 8px;
    min-width: 0;
}
.eta-kv--wide { grid-column: 1 / -1; }
.eta-kv__label {
    display: block;
    font-size: .6rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--c-muted);
    margin-bottom: .15rem;
    line-height: 1.2;
}
.eta-kv__val {
    display: block;
    font-size: .78rem;
    font-weight: 600;
    color: var(--c-ink, #1a2540);
    line-height: 1.35;
    word-break: break-word;
}
.eta-kv__val--mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .74rem;
    letter-spacing: .02em;
}

.eta-group {
    margin-bottom: .65rem;
}
.eta-group:last-child { margin-bottom: 0; }
.eta-group__title {
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: .35rem;
    padding-left: .1rem;
}

/* PNR cards */
.bkpd-eticket-admin__pnr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--eta-gap);
}
.bkpd-eticket-admin__pnr-card {
    padding: .55rem .7rem;
    background: var(--eta-surface);
    border: 1px solid var(--c-line-inner);
    border-radius: var(--eta-radius);
}
.bkpd-eticket-admin__pnr-label {
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--c-muted);
    margin-bottom: .2rem;
}
.bkpd-eticket-admin__pnr-value {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .92rem;
    font-weight: 700;
    letter-spacing: .06em;
    color: #0f172a;
}

/* Side-by-side fare tables */
.eta-tables-duo {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: var(--eta-gap);
    margin-top: .5rem;
}

/* Stored fare cards grid */
.eta-fare-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--eta-gap);
}
.bkpd-eticket-admin__fare-block {
    border: 1px solid var(--c-line-inner);
    border-radius: var(--eta-radius);
    background: #fff;
    overflow: hidden;
}
.bkpd-eticket-admin__fare-head {
    display: flex;
    align-items: center;
    gap: .4rem;
    flex-wrap: wrap;
    padding: .55rem .7rem;
    font-size: .8rem;
    background: var(--eta-surface-2);
    border-bottom: 1px solid var(--c-line-inner);
}

/* Ticket cards — 2-up on wide screens */
.eta-ticket-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--eta-gap);
    padding: .65rem 1rem 1rem;
}
@media (min-width: 1200px) {
    .eta-ticket-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
.bkpd-eticket-admin__ticket {
    border: 1px solid var(--c-line-inner);
    border-radius: var(--eta-radius);
    background: #fff;
    overflow: hidden;
    border-top: none;
}
.bkpd-eticket-admin .bkpd-ticket__head {
    padding: .6rem .75rem;
    background: linear-gradient(90deg, var(--eta-surface-2) 0%, #fff 100%);
    border-bottom: 1px solid var(--c-line-inner);
    gap: .5rem;
}
.bkpd-eticket-admin .bkpd-ticket__number {
    font-size: .88rem;
}
.bkpd-eticket-admin .bkpd-ticket__body {
    padding: .65rem .75rem .75rem;
}
.bkpd-eticket-admin__subblock {
    margin: 0 .75rem .65rem;
    padding: .55rem .65rem;
    background: var(--eta-surface);
    border: 1px solid var(--c-line-inner);
    border-radius: 8px;
    font-size: .74rem;
    line-height: 1.4;
}
.bkpd-eticket-admin__subblock .bkpd-eticket-admin__section-title {
    margin-bottom: .4rem;
}
.bkpd-eticket-admin__subblock .bkpd-eticket-admin__section-title::before {
    height: 10px;
}
.bkpd-eticket-admin .bkpd-ticket__coupons {
    padding: 0 .75rem .75rem;
}
.bkpd-eticket-admin .bkpd-ticket__coupons-title {
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--c-muted);
    margin-bottom: .35rem;
}

/* Denser tables */
.bkpd-eticket-admin__table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.bkpd-eticket-admin__table {
    width: 100%;
    border-collapse: collapse;
    font-size: .72rem;
}
.bkpd-eticket-admin__table th,
.bkpd-eticket-admin__table td {
    padding: .35rem .45rem;
    border-bottom: 1px solid var(--c-line-inner);
    text-align: left;
    vertical-align: top;
}
.bkpd-eticket-admin__table th {
    font-size: .6rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--c-muted);
    background: var(--eta-surface-2);
    white-space: nowrap;
}
.bkpd-eticket-admin__table tbody tr:last-child td { border-bottom: none; }

.eta-fare-info-chip {
    padding: .45rem .55rem;
    background: #fff;
    border: 1px dashed var(--c-line);
    border-radius: 6px;
    font-size: .72rem;
    line-height: 1.45;
    margin-top: .45rem;
}
.eta-fare-info-chip strong {
    font-size: .62rem;
    letter-spacing: .03em;
    text-transform: uppercase;
    color: var(--c-muted);
    font-weight: 700;
}
.eta-fare-info-chip code {
    font-size: .72rem;
    background: var(--eta-surface-2);
    padding: .1rem .3rem;
    border-radius: 4px;
}

.bkpd-eticket-admin .bkpd-info-rows { padding: 0; }
.bkpd-eticket-admin .bkpd-info-rows--compact { padding: 0; }
