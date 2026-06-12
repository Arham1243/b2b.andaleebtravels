<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/css/intlTelInput.css">

<style>
    .hp-phone-field .iti {
        width: 100%;
        display: block;
    }

    .hp-phone-field .iti__tel-input,
    .hp-phone-field .iti input[type=tel] {
        width: 100%;
        padding: .55rem .75rem;
        padding-left: 52px;
        border: 1.5px solid var(--c-line, #e2e8f0);
        border-radius: 8px;
        font: inherit;
        font-size: .86rem;
        color: var(--c-ink, #0f172a);
        background: #fff;
        transition: border-color .14s, box-shadow .14s;
        outline: none;
        min-height: 42px;
    }

    .hp-phone-field .iti__tel-input:focus,
    .hp-phone-field .iti input[type=tel]:focus {
        border-color: var(--c-brand, #cd1b4f);
        box-shadow: 0 0 0 3px rgba(205, 27, 79, .1);
    }

    .hp-phone-field.is-invalid .iti__tel-input,
    .hp-phone-field.is-invalid .iti input[type=tel] {
        border-color: #dc2626;
    }

    .hp-phone-field .iti__country-container {
        font-size: .82rem;
    }
</style>
