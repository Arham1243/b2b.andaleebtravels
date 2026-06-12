/* Shared fare rules (search modal, hold, checkout) */
.fd-rules { display: flex; flex-direction: column; gap: .85rem; }
.fd-rules__route {
    font-size: .95rem; font-weight: 700; color: var(--c-ink);
}
.fd-rules__summary,
.fd-rules__component {
    background: var(--c-bg);
    border: 1px solid var(--c-line);
    border-radius: 10px;
    padding: .75rem .9rem;
}
.fd-rules__row {
    display: flex; justify-content: space-between; gap: .75rem;
    padding: .28rem 0; font-size: .82rem;
}
.fd-rules__row + .fd-rules__row { border-top: 1px dashed var(--c-line-inner); margin-top: .15rem; padding-top: .45rem; }
.fd-rules__key { color: var(--c-muted); font-weight: 600; }
.fd-rules__val { color: var(--c-ink); font-weight: 700; text-align: right; }
.fd-rules__val--ref { color: var(--c-green); }
.fd-rules__val--nr { color: #c0143c; }
.fd-rules__section-title,
.fd-rules__component-route {
    font-size: .72rem; font-weight: 700; letter-spacing: .07em;
    text-transform: uppercase; color: var(--c-muted); margin-bottom: .55rem;
}
.fd-rules__component + .fd-rules__component { margin-top: .55rem; }
.fd-rules__component-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .55rem .75rem;
}
.fd-rules__component-grid div span {
    display: block; font-size: .64rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em; color: var(--c-muted);
}
.fd-rules__component-grid div strong {
    display: block; margin-top: .12rem; font-size: .82rem; color: var(--c-ink);
}
.fd-rules__notes {
    background: #fff8eb; border: 1px solid #fde6b3; border-radius: 10px;
    padding: .7rem .85rem;
}
.fd-rules__notes p {
    margin: 0; font-size: .78rem; color: #7a5b00; line-height: 1.45;
    display: flex; gap: .35rem; align-items: flex-start;
}
.fd-rules__list {
    margin: 0; padding-left: 1.1rem; display: flex; flex-direction: column; gap: .35rem;
}
.fd-rules__list li {
    font-size: .82rem; color: var(--c-ink); line-height: 1.45;
}
.fd-rules__policy + .fd-rules__policy { margin-top: .65rem; }
.fd-rules__notes p + p { margin-top: .45rem; }
.fd-rules__notes i { font-size: .95rem; margin-top: .05rem; flex-shrink: 0; }
.fd-rules__full {
    margin-top: .35rem;
    border-top: 1px solid var(--c-line-inner);
    padding-top: .75rem;
}
.fd-rules__full-status {
    color: var(--c-muted);
    font-size: .88rem;
    padding: .5rem 0;
}
.fd-rules__full-loading {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
}
.fd-rules__full-toolbar {
    display: flex;
    align-items: center;
    gap: .65rem;
    flex-wrap: wrap;
    margin: .35rem 0 .55rem;
}
.fd-rules--page .fd-rules__full-toolbar {
    margin: .5rem 0 .65rem;
    padding: .65rem .75rem;
    background: #fff;
    border: 1px solid var(--c-line);
    border-radius: 10px;
}
.fd-rules__full-filter-label {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--c-muted);
    margin: 0;
    flex-shrink: 0;
}
.fd-rules__full-filter {
    flex: 1 1 220px;
    min-width: 180px;
    max-width: 100%;
    padding: .45rem .65rem;
    font-size: .82rem;
    font-weight: 600;
    color: var(--c-ink);
    background: #fff;
    border: 1px solid var(--c-line);
    border-radius: 8px;
    cursor: pointer;
}
.fd-rules__full-filter:focus {
    outline: none;
    border-color: var(--c-brand, #cd1b4f);
    box-shadow: 0 0 0 2px rgba(205, 27, 79, .12);
}
.fd-rules--page .fd-rules__full-filter {
    flex: 1 1 100%;
    min-width: 0;
    padding: .55rem .75rem;
    font-size: .86rem;
    border: 1.5px solid var(--c-line);
    border-radius: 8px;
    background: #fff;
}
.fd-rules--page .fd-rules__full-filter:focus {
    box-shadow: 0 0 0 3px rgba(205, 27, 79, .1);
}
.fd-rules--page .fd-rules__full-body {
    margin-bottom: 1rem;
}
.fd-rules__full-body {
    max-height: min(52vh, 420px);
    overflow: auto;
    border: 1px solid var(--c-line-inner);
    border-radius: 10px;
    background: #fafbfc;
    padding: .85rem 1rem;
    font-size: .78rem;
    line-height: 1.55;
    color: #2a3142;
}
.fd-rules--page .fd-rules__full-body {
    max-height: min(70vh, 560px);
}
.fd-rules__full-route {
    font-weight: 700;
    font-size: .82rem;
    color: var(--c-ink);
    margin-bottom: .55rem;
    padding-bottom: .45rem;
    border-bottom: 1px dashed var(--c-line-inner);
}
.fd-rules__full-component + .fd-rules__full-component {
    margin-top: 1rem;
    padding-top: .85rem;
    border-top: 1px solid var(--c-line-inner);
}
.fd-rules__full-section h4 {
    margin: .65rem 0 .35rem;
    font-size: .8rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #5a6478;
}
.fd-rules__full-section h4:first-child { margin-top: 0; }
.fd-rules__full-section p {
    margin: 0 0 .55rem;
    white-space: pre-wrap;
    word-break: break-word;
}
.fd-rules__full-error,
.fd-rules__full-empty {
    color: #8a3b12;
    margin: 0;
}
.hp-fare-rules-card .fd-rules {
    padding: .85rem 1.1rem 1rem;
}
