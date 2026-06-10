.hp-date-field {
    position: relative;
}
.hp-date-field__display {
    cursor: pointer;
    padding-right: 2.2rem;
}
.hp-date-field__icon {
    position: absolute;
    right: .75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.05rem;
    color: var(--c-muted);
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
.hp-date-field .daterangepicker td.active,
.hp-date-field .daterangepicker td.active:hover {
    background-color: var(--c-brand, #cd1b4f);
    border-color: var(--c-brand, #cd1b4f);
}
