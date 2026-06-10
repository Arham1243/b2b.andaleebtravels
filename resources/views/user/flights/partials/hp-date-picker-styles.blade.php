.hp-date-field {
    position: relative;
}
.hp-date-field__display {
    width: 100%;
    cursor: pointer;
    padding-right: 2.2rem;
    background: #fff !important;
    color: var(--c-ink, #1a2540) !important;
}
.hp-date-field__display::placeholder {
    color: var(--c-muted, #8492a6);
}
.hp-date-field__display:focus {
    border-color: var(--c-brand, #cd1b4f);
    box-shadow: 0 0 0 3px rgba(205, 27, 79, .1);
}
.hp-date-field__display.is-invalid {
    border-color: #dc2626;
}
.hp-date-field__icon {
    position: absolute;
    right: .75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.05rem;
    color: var(--c-muted, #8492a6);
    pointer-events: none;
}
.hp-date-field .daterangepicker {
    font-family: var(--sans, "Inter", sans-serif);
    border-radius: 12px;
    border-color: var(--c-line, #dde3ef);
    box-shadow: 0 8px 28px rgba(26, 37, 64, .14);
    z-index: 10050;
}
.hp-date-field .daterangepicker .calendar-table th,
.hp-date-field .daterangepicker .calendar-table td {
    font-size: .82rem;
}
.hp-date-field .daterangepicker td.available,
.hp-date-field .daterangepicker td.active,
.hp-date-field .daterangepicker td.active:hover,
.hp-date-field .daterangepicker td.available:hover {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}
.hp-date-field .daterangepicker td.active,
.hp-date-field .daterangepicker td.active:hover {
    background-color: var(--c-brand, #cd1b4f) !important;
    color: #fff !important;
}
