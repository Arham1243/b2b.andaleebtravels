.hp-ac-wrap { position: relative; }
.hp-ac-dropdown {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 4px);
    z-index: 40;
    max-height: 240px;
    overflow-y: auto;
    background: #fff;
    border: 1.5px solid var(--c-line);
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
}
.hp-ac-dropdown[hidden] { display: none !important; }
.hp-ac-item {
    display: block;
    width: 100%;
    text-align: left;
    border: 0;
    background: transparent;
    padding: .55rem .75rem;
    font: inherit;
    font-size: .82rem;
    color: var(--c-ink);
    cursor: pointer;
    border-bottom: 1px solid rgba(226, 232, 240, .8);
}
.hp-ac-item:last-child { border-bottom: 0; }
.hp-ac-item:hover,
.hp-ac-item.is-active { background: rgba(205, 27, 79, .06); }
.hp-ac-item__title { font-weight: 600; display: block; }
.hp-ac-item__sub {
    display: block;
    font-size: .72rem;
    color: var(--c-muted);
    margin-top: .08rem;
}
.hp-ac-empty {
    padding: .65rem .75rem;
    font-size: .76rem;
    color: var(--c-muted);
}
